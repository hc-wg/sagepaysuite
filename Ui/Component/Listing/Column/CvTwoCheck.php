<?php
/**
 * Copyright © 2017 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Ui\Component\Listing\Column;

use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;
use \Ebizmarts\SagePaySuite\Model\OrderGridInfo;

class CvTwoCheck extends Column
{

    /**
     * @var \Ebizmarts\SagePaySuite\Model\OrderGridInfo
     */
    private $orderGridInfo;

    /**
     * CvTwoCheck constructor.
     * @param OrderGridInfo $orderGridInfo
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        OrderGridInfo $orderGridInfo,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->orderGridInfo = $orderGridInfo;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $fieldName = $this->getFieldName();
        return $this->orderGridInfo->prepareColumn($dataSource, "avsCvcCheckSecurityCode", $fieldName);
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->getData('name');
    }
}
