<?php

namespace Ebizmarts\SagePaySuite\Api\Data;

interface ScaTransType
{
    /**
     * Values derived from the 8583 ISO Standard.
     */

    const GOOD_SERVICE_PURCHASE = "01";
    const CHECK_ACCEPTANCE = "03";
    const ACCOUNT_FUNDING = "10";
    const QUASI_CASH_TRANSACTION = "11";
    const PREPAID_ACTIVATION_AND_LOAD = "28";
}
