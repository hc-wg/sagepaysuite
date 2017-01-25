<?php

namespace Ebizmarts\SagePaySuite\Api;

/**
 *
 * @api
 */
interface PiManagementInterface
{
    /**
     * Set payment information and place order for a specified cart.
     *
     * @param mixed $cartId
     * @param \Ebizmarts\SagePaySuite\Api\Data\PiRequestInterface $requestData
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @return \Ebizmarts\SagePaySuite\Api\Data\PiResultInterface
     */
    public function savePaymentInformationAndPlaceOrder($cartId, \Ebizmarts\SagePaySuite\Api\Data\PiRequestInterface $requestData);

    /**
     * @param mixed $cartId
     * @return \Magento\Quote\Api\Data\CartInterface
     */
    public function getQuoteById($cartId);
}
