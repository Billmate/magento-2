<?php

namespace Billmate\NwtBillmateCheckout\Model\Api\Client\DTO;

use Magento\Framework\DataObject;

/**
 * Same data structure as Billmate\NwtBillmateCheckout\Model\Api\Client\DTO\Article,
 * This should be used when handling a response
 * Use Article when building an API request
 *
 * @method $this setArtnr(string $artnr)
 * @method $this setTitle(string $title)
 * @method $this setQuantity(int|float $quantity)
 * @method $this setAprice(int $aprice)
 * @method $this setTax(int $tax)
 * @method $this setDiscount(int $discount)
 * @method $this setWithouttax(int $withouttax)
 * @method $this setTaxrate(int $taxrate)
 *
 * @method string getArtnr()
 * @method string getTitle()
 * @method int|float getQuantity()
 * @method int getAprice()
 * @method int getTax()
 * @method int getDiscount()
 * @method int getWithouttax()
 * @method int getTaxrate()
 */
class ResponseArticle extends DataObject
{

}
