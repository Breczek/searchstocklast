<?php

namespace SearchStockLast\Product;

use Context;
use SearchStockLast\Adapter\SearchStockLastMySQL;

class Search extends \PrestaShop\Module\FacetedSearch\Product\Search
{
    public function __construct(Context $context, $adapterType = SearchStockLastMySQL::TYPE)
    {
        parent::__construct($context, $adapterType);

        $this->searchAdapter = new SearchStockLastMySQL();
    }
}
