<?php

namespace SearchStockLast\Adapter;

use PrestaShop\Module\FacetedSearch\Adapter\MySQL;

/**
 * Adds the missing stock-priority clause to search relevance ordering.
 */
class SearchStockLastMySQL extends MySQL
{
    /**
     * @param array $filterToTableMapping
     *
     * @return string
     */
    protected function computeOrderByField(array $filterToTableMapping)
    {
        $orderBy = parent::computeOrderByField($filterToTableMapping);

        if (!$this->isSearchRelevanceOrder($orderBy)) {
            return $orderBy;
        }

        // Defensive guard for a future ps_facetedsearch release containing the
        // upstream correction. Never prepend the availability expression twice.
        if (strpos($orderBy, 'IFNULL(') !== false) {
            return $orderBy;
        }

        $quantityField = $this->computeFieldName('quantity', $filterToTableMapping);
        $outOfStockField = $this->computeFieldName('out_of_stock', $filterToTableMapping);
        $defaultAllowsBackorders = \Product::isAvailableWhenOutOfStock(2) ? 1 : 0;

        $this->addSelectField('out_of_stock');

        // out_of_stock: 0 = deny, 1 = allow, 2 = inherit shop policy.
        // The boolean expression is 1 only for a product that cannot currently
        // be purchased, so ascending order places those products last while
        // preserving relevance for every purchasable product.
        $effectiveBackorders = sprintf(
            'IF(IFNULL(%s, 2) = 2, %d, IFNULL(%s, 2))',
            $outOfStockField,
            $defaultAllowsBackorders,
            $outOfStockField
        );
        $unavailable = sprintf(
            '(IFNULL(%s, 0) <= 0 AND %s = 0)',
            $quantityField,
            $effectiveBackorders
        );

        return $unavailable . ' ASC, ' . $orderBy;
    }

    private function isSearchRelevanceOrder($orderBy)
    {
        if ($this->getOrderField() !== 'position'
            || $this->getInitialPopulation() === null
            || strpos($orderBy, 'FIELD(p.id_product,') === false
        ) {
            return false;
        }

        $filters = $this->getInitialPopulation()->getFilters();

        return !empty($filters['id_product']['='][0]);
    }
}
