<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO;

use Magento\Framework\DataObject;
use Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Customer\Address;

/**
 * @method int getNr() Get customer number
 * @method string getPno() Get customer personal number
 * @method Address\Shipping getShipping()
 * @method Address\Billing getBilling()
 * @method Address\BillingFactory getBillingFactory()
 * @method Address\ShippingFactory getShippingFactory()
 *
 * @method $this setNr(int $nr)
 * @method $this setPno(string $pno)
 * @method $this setBilling(Address\Billing $billing)
 * @method $this setShipping(Address\Shipping $shipping)
 */
class Customer extends DataObject
{

}
