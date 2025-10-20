## Translations

WP Document Revisions is available in 32 languages. We welcome translation contributions from the community!

### How to Contribute Translations

The easiest way to contribute translations is through [Crowdin](https://crowdin.com/project/wordpress-document-revisions), our translation management platform. Crowdin provides a user-friendly interface for translating strings and reviewing existing translations.

**To contribute via Crowdin:**
1. Visit the [WP Document Revisions project on Crowdin](https://crowdin.com/project/wordpress-document-revisions)
2. Select your language
3. Start translating untranslated strings or improve existing translations
4. Your contributions will be reviewed and periodically merged into the plugin

**To contribute via GitHub:**
1. Fork this repository
2. Edit the `.po` file for your language in the `languages/` directory
3. Use a PO editor like [Poedit](https://poedit.net/) for best results
4. Compile the `.mo` file: `msgfmt -o your-language.mo your-language.po`
5. Submit a pull request with your changes

### Translation Status

As of the latest update, we have partial or complete translations for:

**Well-translated (>50% complete):**
- German (de_DE) - 61%
- Spanish (es_ES) - 53%
- Norwegian Bokmål (nb_NO) - 51%
- Italian (it_IT) - 50%
- Russian (ru_RU) - 50%

**Partially translated (20-50% complete):**
- Indonesian (id_ID), Portuguese (Brazil) (pt_BR), Czech (cs_CZ), Finnish (fi_FI), Swedish (sv_SE), Dutch (nl_NL), Chinese Simplified (zh_CN), French (fr_FR), Danish (da_DK)

**Needs translation:**
- Japanese (ja_JP), Turkish (tr_TR), Polish (pl_PL), Arabic (ar_SA), Hebrew (he_IL), Greek (el_GR), Korean (ko_KR), Portuguese (pt_PT), Romanian (ro_RO), Serbian (sr_SP), Ukrainian (uk_UA), Vietnamese (vi_VN), Chinese Traditional (zh_TW), Afrikaans (af_ZA), Catalan (ca_ES), Hungarian (hu_HU), Norwegian (no_NO)

### For Developers

**Update translation template (POT file):**
```bash
./script/generate-pot
```

**Update all .po files from POT and compile .mo files:**
```bash
./script/update-translations
```

**Pull latest translations from Crowdin:**
```bash
# Requires Crowdin CLI and API credentials
crowdin download
```

### Translation Credits

Many thanks to our translation contributors:

- French - [Hubert CAMPAN](http://omnimaki.com/)
- Spanish - [IBIDEM GROUP](https://www.ibidemgroup.com), [TradiArt](http://www.tradiart.com/), and [elarequi](http://www.labitacoradeltigre.com)
- Norwegian - Daniel Haugen
- German - [Konstantin Obenland](http://en.wp.obenland.it/)
- Chinese - Tim Ren
- Swedish - Daniel Kroon, [Examinare AB](http://www.examinare.biz/), Sweden
- Czech - Hynek Šťavík
- Italian - @guterboit
- Russian - Evgeny Vlasov
- Dutch - @tijscruysen
- And many contributors via [Crowdin](https://crowdin.com/project/wordpress-document-revisions)
