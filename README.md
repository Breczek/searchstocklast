# Search Stock Last

`searchstocklast` is a small compatibility module for PrestaShop 8 and 9. It
corrects the affected `ps_facetedsearch` releases that ignore stock priority when
storefront search results use relevance ordering — see
[ps_facetedsearch#1236](https://github.com/PrestaShop/ps_facetedsearch/issues/1236)
for the documented upstream bug report.

## Why this exists

- *Why do out-of-stock products show up above the ones I can actually sell, only on the search page?*
- *`PS_LAYERED_FILTER_SHOW_OUT_OF_STOCK_LAST` works everywhere except search — why?*
- *Can this be fixed without patching `ps_facetedsearch` itself, so the next update doesn't wipe it out?*

This module answers all three: it hooks in front of `ps_facetedsearch` on
`productSearchProvider`, reorders only `search` query results, and leaves the
faceted-search module's files untouched.

## Behavior

For `search` listings only, the module orders purchasable products before products
that cannot be ordered. The original relevance order is retained inside both
groups, and the ordering is applied by the SQL adapter before pagination.

Category and other product listings are untouched.

## Requirements

- PrestaShop 8.x or 9.x;
- the official `ps_facetedsearch` module, enabled and configured for the search
  controller;
- this module positioned before `ps_facetedsearch` on `productSearchProvider`.

Use `ps_facetedsearch` 4.0.4 or newer in production. Earlier releases are affected
by a [publicly disclosed security issue](https://build.prestashop-project.org/news/2026/security-update-faceted-search-module-ps-facetedsearch/)
unrelated to the ordering bug this module fixes.

The ordering bug itself is confirmed present in every `ps_facetedsearch` release
checked — 4.0.3 through the current 5.1.0 — and in its unreleased `dev` branch, so
no version yet ships a fix. If a future release adds one, this module detects it
and reports on its configuration page that it can be removed.

Installation places it first on that hook automatically. The configuration page
reports a warning if another module later changes the position.

## Installation

Upload `dist/searchstocklast.zip` in Back Office → Module Manager, then install and
enable it. Its configuration page reports whether the correction is active.

Disabling the module removes the runtime behavior. Uninstalling it leaves products,
stock, search settings, and `ps_facetedsearch` files unchanged.

## Development checks

```bash
composer install
./scripts/check.sh
./scripts/package.sh
```

`check.sh` runs PHP syntax checks, PHP_CodeSniffer with the PSR-2 standard, and
the PHPUnit suite. The package script writes `dist/searchstocklast.zip`.

## Scope

- **Verified against:** PrestaShop 8.2 with `ps_facetedsearch` 4.0.4, and PrestaShop
  9.0 with the image-bundled `ps_facetedsearch` 4.0.3 — see [TESTING.md](TESTING.md)
  for the full validation report.
- **What is solid** — the hook integration, the availability-before-relevance
  ordering, and the pre-pagination behavior are covered by the PHPUnit suite and
  by manual verification against real Docker installs.
- **What genuinely varies:** exact behavior depends on the installed
  `ps_facetedsearch` version having faceted search configured for the search
  controller; if it's absent, disabled, or already fixes ordering upstream, this
  module becomes a no-op and says so on its configuration page.

## Contributing

Corrections and additions welcome — PrestaShop and `ps_facetedsearch` keep moving.
Open an issue or PR with the version you observed.

## License

[MIT](LICENSE).

## Author

**Marcin Bręczewski** ([@Breczek](https://github.com/Breczek)) — WordPress & PrestaShop developer.
[breczek-koduje.pl](https://breczek-koduje.pl) · [kontakt@breczek-koduje.pl](mailto:kontakt@breczek-koduje.pl)

## Support

If this saved you time:

- ⭐ Star the repo — it helps others find it
- ☕ [Buy me a coffee](https://buycoffee.to/breczek-koduje.pl) to fuel the next one
- 🌐 More at [breczek-koduje.pl](https://breczek-koduje.pl) · follow [@Breczek](https://github.com/Breczek) on GitHub
