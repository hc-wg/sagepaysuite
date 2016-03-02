<?php
/**
 * Copyright © 2015 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Block\Paypal;


class Processing extends \Magento\Framework\View\Element\Template
{
    protected function _toHtml()
    {
        $html = '<html><head><title>PayPal - Processing payment...</title></head><body>';
        $html .= '<style>body {background-color: #F7F6F4;}' .
            ' .container {margin: 150px auto 350px; width: 50%; text-align: center;}' .
            ' .container p {font-family: pp-sans-big-light,Helvetica Neue,Arial,sans-serif; color: #444; margin-top: 30px; font-size: 14px;}' .
            ' .container img {width: 150px;}' .
            ' .loader {width: 20px !important; margin: 0px 10px 0 0; position: relative; top: 4px;}</style>';
        $html .= '<div class="container"><img src="' . $this->getViewFileUrl('Ebizmarts_SagePaySuite::images/paypal_checkout.png') . '">';

        //form POST
        $postData = $this->getData("paypal_post");
        if(!empty($postData) && is_object($postData) && $postData->Status){
            $html .= '<p><img class="loader" src="' . $this->getViewFileUrl('Ebizmarts_SagePaySuite::images/ajax-loader.gif') . '">Processing payment, please wait...</p></div>';
            $html .= '<form id="paypal_post_form" method="POST" action="' . $this->getUrl('sagepaysuite/paypal/callback', array('_secure' => true)) . '">';
            $postData = get_object_vars($postData);
            $keys = array_keys($postData);
            for($i = 0;$i < count($keys);$i++){
                $html .= '<input type="hidden" name="' . $keys[$i] . '" value="' . $postData[$keys[$i]] . '">';
            }
            $html .= '</form>';
            $html .= '<script>document.getElementById("paypal_post_form").submit();</script>';
        }else{
            $html .= '<p>ERROR: Invalid response from PayPal</p></div>';
        }
        $html .= '</body></html>';

        return $html;
    }
}

