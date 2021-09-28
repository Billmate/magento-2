<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Quote\Model;

use Magento\Quote\Model\BillingAddressManagement;
use Magento\Quote\Model\QuoteRepository;
use Magento\Quote\Model\Quote;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;

/**
 * Plugin for: Magento\Quote\Model\BillingAddressManagement::assign
 */
class BillingAddressManagementPlugin
{
    /**
     * @var Config
     */
    private Config $config;

    /**
     * @var QuoteRepository
     */
    private $quoteRepo;

    public function __construct(
        Config $config,
        QuoteRepository $quoteRepo
    ) {
        $this->config = $config;
        $this->quoteRepo = $quoteRepo;
    }


    /**
     * Plugin for Magento\Quote\Model\BillingAddressManagement::assign
     *
     * @param BillingAddressManagement $subject
     * @param int $cartId
     * @param AddressInterface $address
     * @param boolean $useForShipping
     * @throws NoSuchEntityException
     * @return void
     */
    public function beforeAssign(
        BillingAddressManagement $subject,
        $cartId,
        AddressInterface $address,
        $useForShipping = false
    ) {
        if (!$this->config->getEnabled()) {
            return;
        }

        $email = $address->getEmail();
        $quote = $this->quoteRepo->getActive($cartId);
        /** @var Quote $quote */
        if ($quote->getPayment()->getMethod() !== Config::METHOD_CODE) {
            return;
        }

        $quote->getPayment()->setAdditionalInformation('billmate_order_email', $email);
    }
}