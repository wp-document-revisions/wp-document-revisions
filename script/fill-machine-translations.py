#!/usr/bin/env python3
"""Fill untranslated gettext entries with machine translations.

For every ``languages/*.po`` file, each untranslated entry's ``msgid`` (and
plural form) is translated into the file's locale via the ``trans`` CLI
(translate-shell, a keyless public MT front-end) and written to ``msgstr``.
Machine translations are shipped as final (the fuzzy flag is cleared) per
project decision.

Safety:
 - Entries whose translation does not preserve the exact set of printf-style
   placeholders (``%s``, ``%d``, ``%1$s``) and HTML tags are skipped and left
   untranslated, so a mangled string never reaches the UI.
 - ``trans`` failures / rate-limits leave the entry untranslated; a later run
   retries it. Nothing is overwritten that already has a translation.

Requires: polib, and the ``trans`` binary (translate-shell) on PATH.
Compiling the .mo files is done separately (msgfmt) by the workflow.
"""

import glob
import os
import re
import subprocess
import sys
import time

try:
    import polib
except ImportError:  # pragma: no cover
    sys.exit("polib is required: pip install polib")

# printf placeholders (%s, %d, %1$s, %02d, …) and HTML tags.
_TOKEN_RE = re.compile(r"%[0-9]*\$?[0-9]*[sd]|<[^>]+>")


def tokens(text):
    """Sorted multiset of placeholders/HTML tags that must survive translation."""
    return sorted(_TOKEN_RE.findall(text))


def locale_of(path):
    """`wp-document-revisions-pt_BR.po` -> `pt-BR` (trans accepts either form)."""
    m = re.search(r"-([a-z]{2,3}(?:_[A-Za-z]+)?)\.po$", os.path.basename(path))
    if not m:
        return None
    return m.group(1).replace("_", "-")


def translate(text, target):
    """Translate `text` into `target` via translate-shell; '' on failure."""
    if not text.strip():
        return ""
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
        except Exception:  # noqa: BLE001 - best-effort, keep going
            pass
        time.sleep(2)
    return ""


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
    total = 0
    for path in sorted(glob.glob("languages/*.po")):
        target = locale_of(path)
        if not target:
            continue
        po = polib.pofile(path)
        filled = 0
        for entry in po:
            if entry.obsolete:
                continue
            if fill_entry(entry, target):
                filled += 1
            time.sleep(0.3)  # be gentle on the public endpoint
        if filled:
            po.save(path)
            total += filled
            print(f"{os.path.basename(path)}: filled {filled} entries ({target})")
    print(f"Machine translation pass complete — {total} entries filled.")


if __name__ == "__main__":
    main()
