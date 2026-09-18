<?php

namespace SearchStockLast\Tests;

use PHPUnit\Framework\TestCase;
use Product;
use SearchStockLast\Tests\Support\AdapterHarness;

final class AdapterTest extends TestCase
{
    protected function setUp(): void
    {
        Product::$defaultAllowsBackorders = false;
    }

    public function testAvailabilityIsPrimaryAndRelevanceIsSecondary(): void
    {
        $adapter = new AdapterHarness();
        $sql = $adapter->compute();

        self::assertStringStartsWith('(IFNULL(p.quantity, 0) <= 0 AND ', $sql);
        self::assertStringContainsString('FIELD(p.id_product,3,2,1) DESC', $sql);
        self::assertSame(['out_of_stock'], $adapter->selectedFields);
    }

    public function testInheritedDenyOrdersPolicyIsRepresented(): void
    {
        $sql = (new AdapterHarness())->compute();

        self::assertStringContainsString('= 2, 0,', $sql);
    }

    public function testInheritedAllowOrdersPolicyIsRepresented(): void
    {
        Product::$defaultAllowsBackorders = true;

        $sql = (new AdapterHarness())->compute();

        self::assertStringContainsString('= 2, 1,', $sql);
    }

    public function testUpstreamFixedOrderIsNotModifiedTwice(): void
    {
        $adapter = new AdapterHarness();
        $alreadyFixed = 'IFNULL(p.quantity, 0) <= 0, FIELD(p.id_product,3,2,1) DESC';
        $adapter->setParentOrder($alreadyFixed);

        self::assertSame($alreadyFixed, $adapter->compute());
        self::assertSame([], $adapter->selectedFields);
    }

    public function testNonRelevanceOrderingIsUntouched(): void
    {
        $adapter = new AdapterHarness();
        $adapter->setOrderField('price');

        self::assertSame('FIELD(p.id_product,3,2,1) DESC', $adapter->compute());
        self::assertSame([], $adapter->selectedFields);
    }
}
