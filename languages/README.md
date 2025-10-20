# WP Document Revisions Translations

This directory contains the translation files for WP Document Revisions.

## File Types

- **`.pot`** - Portable Object Template. The master template file containing all translatable strings extracted from the plugin source code.
- **`.po`** - Portable Object. Human-readable translation files for each language, one per locale.
- **`.mo`** - Machine Object. Compiled binary translation files used by WordPress at runtime.

## Translation Workflow

### 1. Extract Translatable Strings

When plugin code changes, update the POT file to extract new translatable strings:

```bash
./script/generate-pot
```

This scans all PHP files and generates `wp-document-revisions.pot` with all translatable strings.

### 2. Update Translation Files

After updating the POT, merge changes into all language .po files:

```bash
./script/update-translations
```

This:
- Merges new strings from the POT into all .po files
- Marks changed strings as "fuzzy" (need review)
- Compiles .mo files for languages with translations

### 3. Translate Strings

Use one of these methods to translate:

**Via Crowdin (Recommended):**
Visit https://crowdin.com/project/wordpress-document-revisions

**Via PO Editor:**
1. Open the .po file in [Poedit](https://poedit.net/) or similar editor
2. Translate untranslated strings
3. Review and update fuzzy translations
4. Save (this updates both .po and .mo files)

**Manually:**
1. Edit the .po file in a text editor
2. Find `msgid` entries with empty `msgstr ""`
3. Add translations to `msgstr "your translation here"`
4. Compile: `msgfmt -o language-code.mo language-code.po`

### 4. Test Translations

1. Copy .mo file to WordPress plugin directory
2. Set WordPress language in Settings > General
3. Visit plugin pages to verify translations display correctly

## Current Translation Status

Run this command to see current translation statistics:

```bash
cd languages
for file in *.po; do
    echo -n "$file: "
    msgfmt --statistics "$file" 2>&1
done
```

## Contributing

We welcome translation contributions! The easiest way to contribute is through [Crowdin](https://crowdin.com/project/wordpress-document-revisions).

For more information, see [Translation Documentation](../docs/translations.md).

## Language Codes

Our translation files use WordPress locale codes:

- `es_ES` - Spanish (Spain)
- `fr_FR` - French (France)
- `de_DE` - German (Germany)
- `it_IT` - Italian (Italy)
- `pt_BR` - Portuguese (Brazil)
- `pt_PT` - Portuguese (Portugal)
- `ja_JP` - Japanese
- `zh_CN` - Chinese (Simplified)
- `zh_TW` - Chinese (Traditional)
- `ru_RU` - Russian
- And 22 more...

For the complete list, see the [WordPress Locale list](https://make.wordpress.org/polyglots/teams/).
