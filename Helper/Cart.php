<?php

namespace Billmate\NwtBillmateCheckout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Data\Form\FormKey;

/**
 * Class Cart
 * @package Billmate\NwtBillmateCheckout\Helper
 */
class Cart extends AbstractHelper
{
    /**
     * @var FormKey
     */
    protected $formKey;

    /**
     * Cart constructor.
     * @param Context $context
     * @param FormKey $formKey
     */
    public function __construct(
        Context $context,
        FormKey $formKey
    )
    {
        $this->formKey = $formKey;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function getFormKey()
    {
        return $this->formKey;
    }
}