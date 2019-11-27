<?php
/**
 * Created by PhpStorm.
 * User: juan
 * Date: 2019-11-26
 * Time: 16:59
 */

namespace Ebizmarts\SagePaySuite\Ui\Component\Listing\Column;


class FraudImage implements \Ebizmarts\SagePaySuite\Api\AdminGridColumnInterface
{
    const IMAGE_PATH = 'Ebizmarts_SagePaySuite::images/icon-shield-';

    /**
     * @param array $additional
     * @param string $index
     * @return mixed|string
     */
    public function getImage(array $additional, $index)
    {
        if ($this->checkTestModeConfiguration($additional)) {
            $image = $this->getTestImage();
        } else {
            $image = $this->getFraudImage($additional, $index);
        }
        return $image;
    }

    /**
     * @param array $additional
     * @return bool
     */
    public function checkTestModeConfiguration(array $additional)
    {
        return isset($additional["mode"]) && $additional["mode"] === Config::MODE_TEST;
    }

    /**
     * @return string
     */
    public function getTestImage()
    {
        return 'Ebizmarts_SagePaySuite::images/test.png';
    }

    /**
     * @return string
     */
    public function getWaitingImage()
    {
        return 'Ebizmarts_SagePaySuite::images/waiting.png';
    }

    /**
     * @param array $additional
     * @return string
     */
    public function getFraudImage(array $additional, $index)
    {
        if ($this->checkIfThirdMan($additional)) {
            $image = $this->getImageNameThirdman($additional[$index]);
        } elseif ($this->checkIfRed($additional)) {
            $image = $this->getImageNameRed($additional[$index]);
        } else {
            $image = $this->getWaitingImage();
        }
        return $image;
    }

    /**
     * @param array $additional
     * @return bool
     */
    public function checkIfThirdMan(array $additional)
    {
        return isset($additional['fraudcode']) && is_numeric($additional['fraudcode']);
    }

    /**
     * @param array $additional
     * @return bool
     */
    public function checkIfRed(array $additional)
    {
        return isset($additional['fraudcode']);
    }

    public function getImageNameThirdman($score)
    {
        $image = '';
        if (is_numeric($score)) {
            if ($score < 30) {
                $image = 'check.png';
            } else if ($score >= 30 && $score <= 49) {
                $image = 'zebra.png';
            } else if ($score > 49) {
                $image = 'cross.png';
            }
        }
        return self::IMAGE_PATH . $image;
    }

    public function getImageNameRed($status)
    {
        $status = strtoupper($status);
        $image = '';
        switch ($status) {
            case 'ACCEPT':
                $image = 'check.png';
                break;
            case 'DENY':
                $image = 'cross.png';
                break;
            case 'CHALLENGE':
                $image = 'zebra.png';
                break;
            case 'NOTCHECKED':
                $image = 'outline.png';
                break;
        }
        return self::IMAGE_PATH . $image;
    }
}
