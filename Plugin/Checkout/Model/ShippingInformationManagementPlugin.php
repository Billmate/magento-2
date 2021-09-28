<?php

namespace Billmate\NwtBillmateCheckout\Plugin\Checkout\Model;

use Magento\Checkout\Model\ShippingInformationManagement;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteRepository;
use Magento\Framework\Exception\NoSuchEntityException;
use Billmate\NwtBillmateCheckout\Gateway\Config\Config;

/**
 * Plugin for: Magento\Checkout\Model\ShippingInformationManagement::saveAddressInformation
 */
class ShippingInformationManagementPlugin
{
    /**
     * @var Config
     */
    private $config;

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
     * Plugin for Magento\Checkout\Model\ShippingInformationManagement::saveAddressInformation
     *
     * @param ShippingInformationManagement $subject
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @throws NoSuchEntityException
     * @return void
     */
    public function beforeSaveAddressInformation(
        ShippingInformationManagement $subject,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {
        if (!$this->config->getEnabled()) {
            return;
        }

        $billingAddress = $addressInformation->getBillingAddress();
        $shippingAddress = $addressInformation->getShippingAddress();
        $sourceAddress = $billingAddress ?? $shippingAddress;

        if (!$sourceAddress) {
            return;
        }

        $email = $addressInformation->getBillingAddress()->getEmail();
        $quote = $this->quoteRepo->getActive($cartId);

        /** @var Quote $quote */
        if ($quote->getPayment()->getMethod() !== Config::METHOD_CODE) {
            return;
        }

        $quote->getPayment()->setAdditionalInformation('billmate_order_email', $email);
    }
}
