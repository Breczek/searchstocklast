# Decisions: Search Stock Last

## Status

Accepted for implementation on 2026-08-22.

## Problem

On storefront search-result pages, products that are genuinely unavailable can be
listed before products that can be purchased. The `ps_facetedsearch` setting
`PS_LAYERED_FILTER_SHOW_OUT_OF_STOCK_LAST` already handles other product listings,
but affected releases bypass that rule when search results are ordered by relevance.

## Product scope

- Deliver an installable PrestaShop module compatible with PrestaShop 8 and 9.
- Apply the correction only to storefront queries whose query type is `search`.
- Integrate with `ps_facetedsearch`; do not replace the shop's search engine or
  faceting system.
- Preserve the original relevance order within the available and unavailable groups.
- Apply availability ordering before pagination so every result page is consistent.
- Leave category, manufacturer, supplier, new-products, price-drop, and other
  product listings unchanged.

## Availability semantics

Follow the native `ps_facetedsearch` semantics rather than treating every quantity
of zero as unavailable:

- positive quantity: available;
- zero or negative quantity with backorders allowed: available for ordering;
- zero or negative quantity with backorders denied: unavailable and moved last.

This must respect the product's effective out-of-stock behavior, including the
shop-wide stock policy when the product inherits it.

## Ordering contract

The effective order is:

1. purchasable products;
2. unavailable products;
3. the search engine's existing relevance order inside each group.

The module must not sort only the products already rendered on the current page.

## Lifecycle and safety

- Enabling the module enables only this compatibility correction.
- Disabling the module removes its runtime effect.
- Uninstalling it removes all module-owned state and leaves catalog data unchanged.
- The module must not edit files belonging to PrestaShop core or
  `ps_facetedsearch` during installation.
- Unsupported or ambiguous integrations must fail open: retain the original search
  behavior instead of breaking the result page.
- When the installed `ps_facetedsearch` version already contains an equivalent
  upstream fix, the module should become a no-op and inform the merchant that it can
  be removed.

## Compatibility dependency

`ps_facetedsearch` is an explicit runtime dependency. If it is missing or disabled,
the module performs no search-provider modification and reports that state in its
configuration page.

## Integration design

- Register on `productSearchProvider` before `ps_facetedsearch`.
- For `search` queries configured in Faceted Search, construct the official
  `SearchProvider` with a custom `SearchFactory`.
- The custom factory supplies a subclass of the official MySQL adapter and changes
  only its search-relevance `ORDER BY` expression.
- Continue using the official provider for filters, counts, cache, AJAX rendering,
  pagination, and available sort options.
- Return `null` for every unsupported state so PrestaShop continues through the
  normal provider chain.
- Detect the equivalent upstream implementation from the installed adapter and
  return `null` when the compatibility correction is no longer required.

## Validation requirements

- PrestaShop 8 and 9 compatibility checks.
- Search results containing both available and unavailable products.
- More results than one page, proving that ordering happens before pagination.
- Preservation of relevance inside both stock groups.
- Product combinations and inherited/explicit backorder policies.
- Normal behavior when this module is disabled or removed.
- Normal behavior when `ps_facetedsearch` is absent, disabled, unsupported, or
  already fixed upstream.

## Out of scope

- Changing category ordering already handled by `ps_facetedsearch`.
- Adding a new search engine.
- Reimplementing faceted filters.
- Modifying product stock, visibility, or purchasability.
- Patching third-party module files on disk.
