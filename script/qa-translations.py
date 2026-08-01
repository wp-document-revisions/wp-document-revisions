#!/usr/bin/env python3
"""QA-review existing gettext translations with an LLM; emit an advisory report.

Reviews every translated (non-fuzzy) entry in ``languages/*.po`` against its
English source and flags only genuine defects:

 - Deterministic (no API): printf/HTML token drift (a placeholder or tag present
   in the source is missing/extra in the translation), and translations that are
   byte-identical to a non-trivial English source (likely never translated).
 - LLM (Azure OpenAI): mistranslation / wrong meaning and terminology drift vs
   the project glossary. Idiomatic or merely stylistic variation is explicitly
   NOT flagged — precision is preferred over recall so the report stays reviewable.

Output is advisory only. Findings are written to ``languages/qa-report.md`` (human
review) and ``languages/qa-suggestions.json`` (machine-readable); the ``.po``/``.mo``
files are never modified. Any LLM suggestion that would drop a placeholder/HTML tag
is discarded before it reaches the report.

Requires: polib and the same ``AZURE_OPENAI_*`` env vars as
``script/fill-machine-translations.py``.
"""

import concurrent.futures
import glob
import json
import os
import re
import sys
import urllib.request

try:
    import polib
except ImportError:  # pragma: no cover
    sys.exit("polib is required: pip install polib")

_AZURE_ENDPOINT = os.environ.get("AZURE_OPENAI_ENDPOINT", "").strip().rstrip("/")
_AZURE_KEY = os.environ.get("AZURE_OPENAI_API_KEY", "").strip()
_AZURE_DEPLOYMENT = os.environ.get("AZURE_OPENAI_DEPLOYMENT", "gpt-4.1").strip()
_AZURE_API_VERSION = os.environ.get("AZURE_OPENAI_API_VERSION", "2024-10-21").strip()

# printf placeholders (%s, %d, %1$s, %02d, …) and HTML tags — must survive.
_TOKEN_RE = re.compile(r"%[0-9]*\$?[0-9]*[sd]|<[^>]+>")

_LANG_NAMES = {
    "af": "Afrikaans", "ar": "Arabic", "ca": "Catalan", "cs": "Czech",
    "da": "Danish", "de": "German", "el": "Greek", "es": "Spanish",
    "fi": "Finnish", "fr": "French", "he": "Hebrew", "hu": "Hungarian",
    "id": "Indonesian", "it": "Italian", "ja": "Japanese", "ko": "Korean",
    "nb": "Norwegian Bokmål", "nl": "Dutch", "no": "Norwegian", "pl": "Polish",
    "pt": "Portuguese", "ro": "Romanian", "ru": "Russian", "sr": "Serbian",
    "sv": "Swedish", "tr": "Turkish", "uk": "Ukrainian", "vi": "Vietnamese",
    "zh-CN": "Simplified Chinese", "zh-TW": "Traditional Chinese",
}

_GLOSSARY = (
    "document, revision, workflow state, check out / check in (version-control "
    "sense), attachment, permalink, feed, taxonomy, post type"
)

_SYSTEM = (
    "You are a senior localization QA reviewer for the WordPress plugin "
    '"WP Document Revisions" (document management, version control, editorial '
    "workflow). You review machine translations from English into {language}.\n"
    "You are given numbered items, each with the English source and its current "
    "translation. Identify ONLY genuine defects:\n"
    "- mistranslation or wrong meaning (a user would be confused or misled),\n"
    "- a printf placeholder (%s, %1$s) or HTML tag that is wrong/missing/extra,\n"
    "- terminology inconsistent with the glossary or with standard WordPress "
    "conventions for this locale.\n"
    "Glossary (translate consistently): " + _GLOSSARY + "\n"
    "Do NOT flag idiomatic, stylistic, or equally-valid alternative phrasings. "
    "When in doubt, do NOT flag. Expect to flag well under 10% of items.\n"
    "Reply with a JSON object: {\"issues\": [{\"i\": <item number>, "
    "\"severity\": \"high\"|\"medium\", \"type\": <short label>, "
    "\"reason\": <one sentence>, \"suggestion\": <improved translation, "
    "preserving every placeholder and HTML tag exactly>}]}. "
    "Return an empty list if nothing is genuinely wrong."
)

_BATCH = 20            # items per LLM request
_WORKERS = 8           # concurrent requests
_MIN_WORDS_IDENTICAL = 2  # flag identical msgstr only for multi-word sources


def tokens(text):
    """Sorted multiset of placeholders/HTML tags that must survive translation."""
    return sorted(_TOKEN_RE.findall(text))


def safe(original, translated):
    """True when `translated` preserves all placeholders/HTML from `original`."""
    return bool(translated) and tokens(translated) == tokens(original)


def locale_of(path):
    m = re.search(r"-([a-z]{2,3}(?:_[A-Za-z]+)?)\.po$", os.path.basename(path))
    return m.group(1).replace("_", "-") if m else None


def target_code(locale):
    base, _, region = locale.partition("-")
    if base == "zh":
        return "zh-TW" if region == "TW" else "zh-CN"
    return base


def _azure_review(system, user):
    """One JSON-mode chat completion; returns parsed dict or {} on failure."""
    body = json.dumps(
        {
            "messages": [
                {"role": "system", "content": system},
                {"role": "user", "content": user},
            ],
            "temperature": 0,
            "max_tokens": 4096,
            "response_format": {"type": "json_object"},
        }
    ).encode()
    url = (
        f"{_AZURE_ENDPOINT}/openai/deployments/{_AZURE_DEPLOYMENT}"
        f"/chat/completions?api-version={_AZURE_API_VERSION}"
    )
    headers = {"Content-Type": "application/json", "api-key": _AZURE_KEY}
    for _ in range(3):
        try:
            req = urllib.request.Request(url, data=body, headers=headers)
            with urllib.request.urlopen(req, timeout=90) as resp:
                payload = json.load(resp)
            content = payload["choices"][0]["message"]["content"]
            return json.loads(content)
        except Exception:  # noqa: BLE001 - best-effort
            continue
    return {}


def deterministic_findings(entries):
    """Objective, no-API defects: token drift and untranslated-but-final."""
    out = []
    for e in entries:
        # Placeholder/HTML tag drift — always a real defect.
        if tokens(e.msgid) != tokens(e.msgstr):
            out.append(
                {
                    "source": e.msgid,
                    "current": e.msgstr,
                    "severity": "high",
                    "type": "placeholder/tag drift",
                    "reason": "Placeholders or HTML tags differ from the source.",
                    "suggestion": "",
                    "kind": "deterministic",
                }
            )
            continue
        # Translation identical to the English source — likely never translated.
        # Skip short/atomic strings ("OK", "%s", brand names) and HTML-comment
        # markers, which are legitimately unchanged across locales.
        if e.msgstr == e.msgid:
            if len(re.findall(r"[A-Za-z]+", e.msgid)) < _MIN_WORDS_IDENTICAL:
                continue
            if e.msgid.strip().startswith("<!--"):
                continue
            out.append(
                {
                    "source": e.msgid,
                    "current": e.msgstr,
                    "severity": "medium",
                    "type": "possibly untranslated",
                    "reason": "Translation is identical to the English source.",
                    "suggestion": "",
                    "kind": "deterministic",
                }
            )
    return out


def llm_findings(entries, language):
    """Batch entries through the LLM; return validated findings."""
    system = _SYSTEM.replace("{language}", language)
    batches = [entries[i : i + _BATCH] for i in range(0, len(entries), _BATCH)]

    def run(batch):
        lines = []
        for n, e in enumerate(batch, 1):
            lines.append(f"{n}. EN: {e.msgid}\n   {language}: {e.msgstr}")
        result = _azure_review(system, "\n".join(lines))
        found = []
        for issue in result.get("issues", []):
            try:
                idx = int(issue["i"]) - 1
            except (KeyError, ValueError, TypeError):
                continue
            if not 0 <= idx < len(batch):
                continue
            e = batch[idx]
            suggestion = (issue.get("suggestion") or "").strip()
            # Discard suggestions that would break placeholders/HTML.
            if suggestion and not safe(e.msgid, suggestion):
                suggestion = ""
            found.append(
                {
                    "source": e.msgid,
                    "current": e.msgstr,
                    "severity": issue.get("severity", "medium"),
                    "type": issue.get("type", "mistranslation"),
                    "reason": issue.get("reason", ""),
                    "suggestion": suggestion,
                    "kind": "llm",
                }
            )
        return found

    findings = []
    with concurrent.futures.ThreadPoolExecutor(max_workers=_WORKERS) as pool:
        for batch_found in pool.map(run, batches):
            findings.extend(batch_found)
    return findings


def review_locale(path):
    locale = locale_of(path)
    if not locale or target_code(locale) == "en":
        return locale, []
    language = _LANG_NAMES.get(target_code(locale), locale)
    po = polib.pofile(path)
    entries = [
        e for e in po
        if e.translated() and not e.obsolete and "fuzzy" not in e.flags
    ]
    findings = deterministic_findings(entries)
    findings += llm_findings(entries, language)
    # Sort: high severity first, then by kind.
    order = {"high": 0, "medium": 1, "low": 2}
    findings.sort(key=lambda f: (order.get(f["severity"], 3), f["kind"]))
    return locale, findings


def write_report(results):
    total = sum(len(f) for _, f in results)
    n_llm = sum(1 for _, fs in results for f in fs if f["kind"] == "llm")
    n_det = total - n_llm
    n_high = sum(1 for _, fs in results for f in fs if f["severity"] == "high")
    # Objective, high-confidence defects: placeholder/HTML drift (a tag or printf
    # arg is broken/missing/extra). These are shipped bugs, not style opinions.
    objective = [
        (loc, f) for loc, fs in results for f in fs
        if f["kind"] == "deterministic" and f["type"] == "placeholder/tag drift"
    ]
    lines = [
        "# Translation QA report",
        "",
        f"Reviewed with Azure OpenAI ({_AZURE_DEPLOYMENT}). "
        f"**{total} findings** ({n_high} high severity) across "
        f"{sum(1 for _, f in results if f)} locales — "
        f"{n_det} deterministic, {n_llm} LLM.",
        "",
        "Findings are **advisory**; no `.po`/`.mo` files were modified. Deterministic "
        "findings (placeholder/tag drift, untranslated) are objective. LLM findings "
        "are suggestions — verify with a native speaker before applying.",
        "",
        "## Objective defects: placeholder / HTML drift",
        "",
        "High-confidence — a printf placeholder or HTML tag is broken, missing, or "
        f"extra versus the source (e.g. `</ code>`, mismatched tags). {len(objective)} "
        "found; fix these first.",
        "",
    ]
    for loc, f in objective:
        lines.append(f"- **[{loc}]** {f['reason']}")
        lines.append(f"  - source: `{f['source']}`")
        lines.append(f"  - current: `{f['current']}`")
        lines.append("")
    lines.append("## Per-locale findings")
    lines.append("")
    for locale, findings in results:
        if not findings:
            continue
        lines.append(f"## {locale} ({len(findings)})")
        lines.append("")
        for f in findings:
            lines.append(f"- **[{f['severity']}] {f['type']}** — {f['reason']}")
            lines.append(f"  - source: `{f['source']}`")
            lines.append(f"  - current: `{f['current']}`")
            if f["suggestion"]:
                lines.append(f"  - suggested: `{f['suggestion']}`")
            lines.append("")
    # Outputs live outside languages/ (which ships to WordPress.org) and are
    # excluded from the distribution via .distignore.
    os.makedirs("i18n-qa", exist_ok=True)
    open("i18n-qa/qa-report.md", "w", encoding="utf-8").write("\n".join(lines))
    payload = {loc: fs for loc, fs in results if fs}
    open("i18n-qa/qa-suggestions.json", "w", encoding="utf-8").write(
        json.dumps(payload, ensure_ascii=False, indent=2)
    )
    print(f"Wrote i18n-qa/qa-report.md and qa-suggestions.json ({total} findings).")


def main():
    if not (_AZURE_ENDPOINT and _AZURE_KEY):
        sys.exit("AZURE_OPENAI_ENDPOINT and AZURE_OPENAI_API_KEY are required.")
    only = set(sys.argv[1:])  # optional locale filter, e.g. `qa-translations.py de_DE`
    paths = sorted(glob.glob("languages/*.po"))
    if only:
        paths = [p for p in paths if os.path.basename(p).split("-", 3)[-1][:-3] in only]
    print(f"Reviewing {len(paths)} locale(s) with {_AZURE_DEPLOYMENT}…")
    results = []
    for path in paths:
        locale, findings = review_locale(path)
        results.append((locale, findings))
        print(f"  {locale}: {len(findings)} findings")
    write_report(results)


if __name__ == "__main__":
    main()
