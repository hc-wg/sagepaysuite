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
        $status = $this->getStatus($additional, $index);
        if ($index == "3DSecureStatus") {
            $image = $this->getThreeDStatus($status);
        } else {
            $image = $this->getStatusImage($status);
        }

        return $image;
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
            case 'OK':
                $threeDStatus = 'check.png';
                break;
            case 'NOTCHECKED':
            case 'NOTAUTHENTICATED':
            case 'CARDNOTENROLLED':
            case 'ISSUERNOTENROLLED':
            case 'ATTEMPTONLY':
            case 'NOTAVAILABLE':
            case 'NOTAUTHED':
            default:
                $threeDStatus = 'outline.png';
                break;
            case 'INCOMPLETE':
                $threeDStatus = 'zebra.png';
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
        $status = strtoupper($status);

        switch($status){
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
