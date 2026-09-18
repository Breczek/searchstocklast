# Validation report

Validation date: 2026-08-22.

## Automated checks

- PHP syntax validation for every module and test PHP file.
- PHP_CodeSniffer 4.0.4 validation against PSR-2 (14 PHP files).
- PHPUnit 12.5.33 adapter suite (5 tests, 9 assertions) covering stock priority,
  inherited backorder policy, preserved relevance, unrelated sort orders, and
  protection against applying an upstream fix twice.
- ZIP integrity and installable top-level module directory.

## PrestaShop integration

The packaged source was mounted and installed successfully in clean official Docker
images for:

- PrestaShop 8.2 with `ps_facetedsearch` 4.0.4;
- PrestaShop 9.0 with the image-bundled `ps_facetedsearch` 4.0.3.

In both installations:

- the module installed and was active as version 1.0.0;
- it moved to position 1 on `productSearchProvider`, immediately before
  `ps_facetedsearch`;
- the module returned the official Faceted Search `SearchProvider` configured with
  `SearchStockLast\\Product\\SearchFactory`;
- the full provider query completed and returned five matching products;
- direct adapter SQL placed the availability expression before the relevance
  `FIELD(...)` expression.

For the behavioral test, product 19 was initially the most relevant result and had
quantity 300. After changing only its test stock to quantity 0 with backorders
denied, the result order changed from:

```text
19, 6, 7, 8, 15
```

to:

```text
6, 7, 8, 15, 19
```

With two products per page, both PrestaShop versions returned:

```text
page 1: 6, 7
page 2: 8, 15
page 3: 19
```

This verifies that availability ordering is applied before pagination. All temporary
containers, databases, and the Docker network were removed after the test.
