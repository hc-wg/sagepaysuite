/**
 * Copyright © 2015 eBizmarts. All rights reserved.
 * See LICENSE.txt for license details.
 */

/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
        ) {
        'use strict';
        rendererList.push(
//            {
//                type: 'sagepaysuitedirect',
//                component: 'Ebizmarts_SagePaySuite/js/view/payment/method-renderer/checkmo-method'
//            },
            {
                type: 'sagepaysuiteform',
                component: 'Ebizmarts_SagePaySuite/js/view/payment/method-renderer/form-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
