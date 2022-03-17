<?php

namespace Billmate\NwtBillmateCheckout\Gateway\Http\Client;

/**
 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
 */
class TransactionInitialize extends AbstractTransaction
{
     /**
      * No transfer required for initialize, but we need this class anyway for the command to work.
      *
      * @param array $data
      * @return void
      */
    public function process(array $data)
    {
        return [];
    }
}
