<?php

namespace SearchStockLast\Tests\Support;

class AdapterHarness extends \SearchStockLast\Adapter\SearchStockLastMySQL
{
    public function compute()
    {
        return $this->computeOrderByField([]);
    }

    public function setParentOrder($order)
    {
        $this->parentOrder = $order;
    }

    public function setOrderField($field)
    {
        $this->orderField = $field;
    }
}
