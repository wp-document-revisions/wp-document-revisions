# CLAUDE.md

@.github/copilot-instructions.md

## Releasing and deploying

Two workflows publish to the WordPress.org SVN repository, and nobody has to approve either run:

- [`deploy.yml`](.github/workflows/deploy.yml) runs on a push of any tag. It builds and ships the plugin to every site that auto-updates it, without running tests or checking that the tag matches the plugin version. Creating a GitHub Release creates its tag, so it deploys too.
- [`deploy-assets.yml`](.github/workflows/deploy-assets.yml) syncs [`.wordpress-org/`](.wordpress-org/) (icon, banner, screenshots) to the plugin directory whenever a change to it lands on `main`.

Releases happen only after the owner explicitly approves that release. Agents may prepare a version-bump pull request, but never push a tag, create a GitHub Release, or run an SVN deploy. Push branches with `git push --no-follow-tags`, so a local tag can't ride along and trigger a deploy.

A version-bump pull request changes:

- the version in [`wp-document-revisions.php`](wp-document-revisions.php), in all three places: the `Version:` header, the `@version` tag, and the `WPDR_VERSION` constant
- `Stable tag:` in [`docs/header.md`](docs/header.md)
- a new entry at the top of [`docs/changelog.md`](docs/changelog.md)
- [`readme.txt`](readme.txt), regenerated with `script/build-readme` and committed in the same pull request. [`build-readme.yml`](.github/workflows/build-readme.yml) only opens its "Update README" pull request after the docs reach `main`, so a tag pushed before that merges ships the old `Stable tag`.

Never edit `readme.txt` by hand; it's generated from [`docs/`](docs/).
