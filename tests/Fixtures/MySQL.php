<?php

namespace PrestaShop\Module\FacetedSearch\Adapter;

class MySQL
{
    protected $orderField = 'position';
    protected $initialPopulation;
    protected $parentOrder = 'FIELD(p.id_product,3,2,1) DESC';
    public $selectedFields = [];

    public function __construct()
    {
        $this->initialPopulation = new TestPopulation([
            'id_product' => ['=' => [[3, 2, 1]]],
        ]);
    }

    protected function computeOrderByField(array $filterToTableMapping)
    {
        return $this->parentOrder;
    }

    protected function computeFieldName($field, array $filterToTableMapping)
    {
        return $field === 'quantity' ? 'p.quantity' : 'sa.out_of_stock';
    }

    public function addSelectField($field)
    {
        $this->selectedFields[] = $field;
    }

    public function getOrderField()
    {
        return $this->orderField;
    }

    public function getInitialPopulation()
    {
        return $this->initialPopulation;
    }
}
