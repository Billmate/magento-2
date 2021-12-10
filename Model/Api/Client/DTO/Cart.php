<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO;

use Magento\Framework\DataObject;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart\HandlingFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart\Handling;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart\ShippingFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart\Shipping;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart\TotalFactory;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Cart\Total;

/**
 * @method Handling getHandling()
 * @method Shipping getShipping()
 * @method Total getTotal()
 * @method HandlingFactory getHandlingFactory()
 * @method ShippingFactory getShippingFactory()
 * @method TotalFactory getTotalFactory()
 *
 * @method $this setHandling(Handling $handling)
 * @method $this setShipping(Shipping $shipping)
 * @method $this setTotal(Total $total)
 */
class Cart extends DataObject
{
}
