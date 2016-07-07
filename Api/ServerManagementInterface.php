<?php

namespace Ebizmarts\SagePaySuite\Api;

/**
 *
 * @api
 */
interface ServerManagementInterface
{

    /**
     * Set payment information and place order for a specified cart.
     *
     * @param int $cartId
     * @param bool $save_token
     * @param string $token
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return \Ebizmarts\SagePaySuite\Api\Data\ResultInterface
     */
    public function savePaymentInformationAndPlaceOrder($cartId, $save_token, $token);

}