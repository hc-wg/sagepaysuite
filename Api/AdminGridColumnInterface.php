<?php

namespace Ebizmarts\SagePaySuite\Api;

interface AdminGridColumnInterface
{

    /**
     * @param array $dataSource
     * @param $index
     * @param $fieldName
     * @return mixed
     */
    function prepareColumn(array $dataSource, $index, $fieldName);
}
