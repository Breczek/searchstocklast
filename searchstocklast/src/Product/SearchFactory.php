<?php

namespace SearchStockLast\Product;

use Context;

class SearchFactory extends \PrestaShop\Module\FacetedSearch\Product\SearchFactory
{
    public function build(Context $context)
    {
        return new Search($context);
    }
}
