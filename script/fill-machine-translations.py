#!/usr/bin/env python3
"""Fill untranslated gettext entries with machine translations.

For every ``languages/*.po`` file, each untranslated entry's ``msgid`` (and
plural form) is translated into the file's locale and written to ``msgstr``.
Machine translations are shipped as final (the fuzzy flag is cleared) per
project decision.

Engine:
 - If ``GOOGLE_TRANSLATE_API_KEY`` is set, the Google Cloud Translation v2
   REST API is used (reliable from CI, covers all of this plugin's locales).
 - Otherwise it falls back to the keyless ``trans`` CLI (translate-shell),
   which is best-effort and often rate-limited from datacenter IPs.

Safety:
 - Entries whose translation does not preserve the exact set of printf-style
   placeholders (``%s``, ``%d``, ``%1$s``) and HTML tags are skipped and left
   untranslated, so a mangled string never reaches the UI.
 - Failures / rate-limits leave the entry untranslated; a later run retries
   it. Nothing is overwritten that already has a translation.

Requires: polib. The ``trans`` binary (translate-shell) is only needed for the
keyless fallback. Compiling the .mo files is done separately (msgfmt) by the
workflow.
"""

import glob
import json
import os
import re
import subprocess
import sys
import time
import urllib.parse
import urllib.request

try:
    import polib
except ImportError:  # pragma: no cover
    sys.exit("polib is required: pip install polib")

_GOOGLE_KEY = os.environ.get("GOOGLE_TRANSLATE_API_KEY", "").strip()
_GOOGLE_ENDPOINT = "https://translation.googleapis.com/language/translate/v2"

# printf placeholders (%s, %d, %1$s, %02d, …) and HTML tags.
_TOKEN_RE = re.compile(r"%[0-9]*\$?[0-9]*[sd]|<[^>]+>")


def tokens(text):
    """Sorted multiset of placeholders/HTML tags that must survive translation."""
    return sorted(_TOKEN_RE.findall(text))


def locale_of(path):
    """`wp-document-revisions-pt_BR.po` -> `pt-BR`."""
    m = re.search(r"-([a-z]{2,3}(?:_[A-Za-z]+)?)\.po$", os.path.basename(path))
    if not m:
        return None
    return m.group(1).replace("_", "-")


def target_code(locale):
    """Map a PO locale to an MT target code (base language, keeping zh region)."""
    base, _, region = locale.partition("-")
    if base == "zh":
        return "zh-TW" if region == "TW" else "zh-CN"
    return base


def _translate_google(text, target):
    """Google Cloud Translation v2 REST; '' on failure."""
    data = urllib.parse.urlencode(
        {"q": text, "target": target, "source": "en", "format": "text", "key": _GOOGLE_KEY}
    ).encode()
    for _ in range(3):
        try:
            req = urllib.request.Request(_GOOGLE_ENDPOINT, data=data)
            with urllib.request.urlopen(req, timeout=30) as resp:
                payload = json.load(resp)
            return payload["data"]["translations"][0]["translatedText"].strip()
        except Exception:  # noqa: BLE001 - best-effort
            time.sleep(2)
    return ""


def _translate_trans(text, target):
    """Keyless translate-shell fallback; '' on failure."""
    for _ in range(3):
        try:
            proc = subprocess.run(
                ["trans", "-b", "-no-autocorrect", ":" + target, text],
                capture_output=True,
                text=True,
                timeout=30,
                check=False,
            )
            out = proc.stdout.strip()
            if out:
                return out
        except Exception:  # noqa: BLE001 - best-effort
            pass
        time.sleep(2)
    return ""


def translate(text, target):
    """Translate `text` into `target` with the configured engine; '' on failure."""
    if not text.strip():
        return ""
    if _GOOGLE_KEY:
        return _translate_google(text, target)
    return _translate_trans(text, target)


def safe(original, translated):
    """True when the translation preserves all placeholders/HTML from source."""
    return bool(translated) and tokens(translated) == tokens(original)


def fill_entry(entry, target):
    """Translate one entry in place; return True if it was updated."""
    if entry.msgid_plural:
        if all(v.strip() for v in entry.msgstr_plural.values()):
            return False
        singular = translate(entry.msgid, target)
        plural = translate(entry.msgid_plural, target)
        if not (safe(entry.msgid, singular) and safe(entry.msgid_plural, plural)):
            return False
        for key in entry.msgstr_plural:
            entry.msgstr_plural[key] = plural if int(key) != 0 else singular
    else:
        if entry.msgstr.strip():
            return False
        translated = translate(entry.msgid, target)
        if not safe(entry.msgid, translated):
            return False
        entry.msgstr = translated

    if "fuzzy" in entry.flags:
        entry.flags.remove("fuzzy")
    return True


def main():
    engine = "Google Cloud Translation" if _GOOGLE_KEY else "translate-shell (keyless)"
    print(f"Machine-translation engine: {engine}")

    # Preflight: fail fast (seconds) if the engine can't translate at all —
    # e.g. a bad/rotated key, the Cloud Translation API not enabled, or billing
    # not linked — rather than grinding through retries for hours.
    probe = translate("Document", "es")
    if not probe:
        sys.exit(
            "Machine-translation engine is not responding to a test request.\n"
            + (
                "Check GOOGLE_TRANSLATE_API_KEY, that the Cloud Translation API is "
                "enabled, and that billing is linked to the project."
                if _GOOGLE_KEY
                else "The keyless translate-shell endpoint appears blocked/rate-limited."
            )
        )
    print(f"Preflight OK (Document -> es: {probe!r}).")

    total = 0
    for path in sorted(glob.glob("languages/*.po")):
        locale = locale_of(path)
        if not locale:
            continue
        target = target_code(locale)
        po = polib.pofile(path)
        filled = 0
        for entry in po:
            if entry.obsolete:
                continue
            if fill_entry(entry, target):
                filled += 1
            if not _GOOGLE_KEY:
                time.sleep(0.3)  # be gentle on the keyless public endpoint
        if filled:
            po.save(path)
            total += filled
            print(f"{os.path.basename(path)}: filled {filled} entries ({target})")
    print(f"Machine translation pass complete — {total} entries filled.")


if __name__ == "__main__":
    main()
