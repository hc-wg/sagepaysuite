<?php

namespace Ebizmarts\SagePaySuite\Api;

/**
 *
 * @api
 */
interface ServerManagementInterface
{

    /**
     * @param $cartId
     * @param $save_token
     * @param $token
     * @return mixed
     */
    public function savePaymentInformationAndPlaceOrder($cartId, $save_token, $token);

    /**
     * @param mixed $cartId
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function getQuoteById($cartId);
}
