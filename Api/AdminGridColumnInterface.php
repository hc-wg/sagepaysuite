<?php

namespace Ebizmarts\SagePaySuite\Api;

interface AdminGridColumnInterface
{

    /**
     * @param array $dataSource
     * @param string $index
     * @param string $fieldName
     * @return mixed
     */
    function prepareColumn(array $dataSource, $index, $fieldName);
}
