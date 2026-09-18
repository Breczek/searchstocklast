<?php

namespace PrestaShop\Module\FacetedSearch\Adapter;

class TestPopulation
{
    private $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function getFilters()
    {
        return $this->filters;
    }
}
