<?php
/**
 * Copyright © 2019 ebizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Ebizmarts\SagePaySuite\Ui\Component\Listing\Column;

use Ebizmarts\SagePaySuite\Model\OrderGridInfo;
use \Magento\Framework\View\Element\UiComponent\ContextInterface;
use \Magento\Framework\View\Element\UiComponentFactory;
use \Magento\Ui\Component\Listing\Columns\Column;

class AddressValidation extends Column
{
    /**
     * @var OrderGridInfo
     */
    private $orderGridColumns;

    /**
     * AddressValidation constructor.
     * @param OrderGridColumns $orderGridColumns
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        OrderGridColumns $orderGridColumns,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->orderGridColumns = $orderGridColumns;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $fieldName = $this->getFieldName();
        return $this->orderGridColumns->prepareColumn($dataSource, "avsCvcCheckAddress", $fieldName);
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->getData('name');
    }
}
