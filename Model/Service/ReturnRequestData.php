<?php

namespace Billmate\NwtBillmateCheckout\Model\Service;

use Magento\Framework\DataObject;

/**
 * Accessor service for data sent from Billmate to return URL (accepturl or cancelurl)
 */
class ReturnRequestData
{
    /**
     * @var DataObject|null
     */
    private $requestContent = null;

    /**
     * @param DataObject $requestData
     * @return void
     */
    public function setRequestContent(DataObject $requestData): void
    {
        $this->requestContent = $requestData;
    }

    /**
     * @return DataObject|null
     */
    public function getRequestContent(): ?DataObject
    {
        return $this->requestContent;
    }
}
