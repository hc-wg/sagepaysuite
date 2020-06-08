<?php
/**
 * Copyright © 2019 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Ui\Component\Listing\Column;

class OrderGridColumns extends \Ebizmarts\SagePaySuite\Model\OrderGridInfo
{
    const IMAGE_PATH = 'Ebizmarts_SagePaySuite::images/icon-shield-';

    /**
     * @param array $additional
     * @param string $index
     * @return mixed
     */
    public function getImage(array $additional, $index)
    {
        $integrationIndex = $this->getIndex($index);
        $status = $this->getStatus($additional, $integrationIndex);
        if ($index == "threeDStatus") {
            $image = $this->getThreeDStatus($status);
        } else {
            $image = $this->getStatusImage($status);
        }

        return $image;
    }

    /**
     * @param string $index
     * @return string
     */
    public function getIndex($index)
    {
        $paymentMethodCode = $this->getMethodCode();
        $integrationIndex = "";
        switch ($paymentMethodCode){
            case 'sagepaysuiteform':
                $integrationIndex = $this->getIndexForForm($index);
                break;
            case 'sagepaysuiteserver':
            case 'sagepaysuitepaypal':
                $integrationIndex = $this->getIndexForServerAndPayPal($index);
                break;
            case 'sagepaysuitepi':
            default:
                $integrationIndex = $index;
                break;
        }

        return $integrationIndex;
    }

    /**
     * @param string $index
     * @return string
     */
    public function getIndexForForm($index){
        //This function returns the index needed to use for FORM integration
        $integrationIndex = "";
        switch ($index){
            case 'avsCvcCheckAddress':
                $integrationIndex = 'AddressResult';
                break;
            case 'avsCvcCheckPostalCode':
                $integrationIndex = 'PostCodeResult';
                break;
            case 'avsCvcCheckSecurityCode':
                $integrationIndex = 'CV2Result';
                break;
            case 'threeDStatus':
                $integrationIndex = '3DSecureStatus';
                break;
        }
        error_log("Integration Index: ".$integrationIndex."\n", 3, '/Users/juan/Sites/m234/var/log/ebizmarts.log');

        return $integrationIndex;
    }

    /**
     * @param string $index
     * @return string
     */
    public function getIndexForServerAndPayPal($index){
        //This function returns the index needed to use for SERVER and PayPal integrations
        $integrationIndex = "";
        switch ($index){
            case 'avsCvcCheckAddress':
                $integrationIndex = 'AddressResult';
                break;
            case 'avsCvcCheckPostalCode':
                $integrationIndex = 'PostCodeResult';
                break;
            case 'avsCvcCheckSecurityCode':
                $integrationIndex = 'CV2Result';
                break;
            case 'threeDStatus':
            default:
                $integrationIndex = $index;
                break;
        }

        return $integrationIndex;
    }

    /**
     * @param $status
     * @return string
     */
    public function getThreeDStatus($status)
    {
        $status = strtoupper($status);
        switch($status){
            case 'AUTHENTICATED':
                $threeDStatus = 'check.png';
                break;
            case 'NOTCHECKED':
            case 'NOTAUTHENTICATED':
            case 'CARDNOTENROLLED':
            case 'ISSUERNOTENROLLED':
            case 'ATTEMPTONLY':
            case 'NOTAVAILABLE':
            case 'INCOMPLETE':
            default:
                $threeDStatus = 'outline.png';
                break;
            case 'ERROR':
            case 'MALFORMEDORINVALID':
                $threeDStatus = 'cross.png';
                break;
        }

        return self::IMAGE_PATH . $threeDStatus;
    }

    /**
     * @param $status
     * @return string
     */
    public function getStatusImage($status)
    {
        $statusWithOutSpaces = str_replace(' ', '', $status);

        switch($statusWithOutSpaces){
            case 'MATCHED':
                $imageUrl = 'check.png';
                break;
            case 'NOTCHECKED':
            case 'NOTPROVIDED':
            default:
                $imageUrl = 'outline.png';
                break;
            case 'NOTMATCHED':
                $imageUrl = 'cross.png';
                break;
            case 'PARTIAL':
                $imageUrl = 'zebra.png';
                break;
        }

        return self::IMAGE_PATH . $imageUrl;
    }

    /**
     * @param $additional
     * @param $index
     * @return string
     */
    public function getStatus($additional, $index)
    {
        if (isset($additional[$index])) {
            $status = $additional[$index];
        } else {
            $status = "NOTPROVIDED";
        }

        return $status;
    }
}
