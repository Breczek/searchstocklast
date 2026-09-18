<?php

class Product
{
    public static $defaultAllowsBackorders = false;

    public static function isAvailableWhenOutOfStock($outOfStock)
    {
        return $outOfStock === 2 && self::$defaultAllowsBackorders;
    }
}
