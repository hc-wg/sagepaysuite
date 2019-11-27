<?php

namespace Ebizmarts\SagePaySuite\Api;

interface AdminGridColumnInterface
{
    /**
     * @param array $additional
     * @param string $index
     * @return mixed
     */
    function getImage(array $additional, $index);
}
