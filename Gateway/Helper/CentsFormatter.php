<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Helper;

trait CentsFormatter
{
    /**
     * Convert price to cents value
     *
     * @param int|float $value
     * @return int
     */
    protected function toCents($value): int
    {
        return (int)100 * $value;
    }
}
