<?php

namespace Banklink\Protocol\iPizzaLatvia;

/**
 * List of all fields used by latvian SEB iPizza protocol
 */
final class FieldsIB
{
    // Order data
    const SERVICE_ID        = 'IB_SERVICE';
    const SUM               = 'IB_AMOUNT';
    const ORDER_ID          = 'IB_STAMP';
    const ORDER_REFERENCE   = 'IB_REF';
    const CURRENCY          = 'IB_CURR';
    const DESCRIPTION       = 'IB_MSG';
    const USER_LANG         = 'IB_LANG';

    // Seller (site owner) info
    const SELLER_ID                = 'IB_SND_ID';
    const SELLER_NAME              = 'IB_NAME';
    const SELLER_BANK_ACC          = 'IB_ACC';

    // data provided in response
    const SELLER_ID_RESPONSE       = 'IB_REC_ID';
    const SELLER_NAME_RESPONSE     = 'IB_REC_NAME';
    const SELLER_BANK_ACC_RESPONSE = 'IB_REC_ACC';
    const SENDER_NAME              = 'IB_SND_NAME';
    const SENDER_BANK_ACC          = 'IB_SND_ACC';
    const TRANSACTION_ID           = 'IB_T_NO';
    const TRANSACTION_DATE         = 'IB_T_DATE';
    const VK_REPLY	  			   = 'IB_REPLY';
    const VK_RID				   = 'IB_RID'; 
    const VK_NONCE				   = 'IB_NONCE'; 
    const VK_USER_NAME			   = 'IB_USER_NAME'; 
    const VK_USER_ID			   = 'IB_USER_ID'; 
    const VK_COUNTRY			   = 'IB_COUNTRY'; 
    const VK_OTHER			   = 'IB_OTHER'; 
    const VK_TOKEN			   = 'IB_TOKEN'; 
    const VK_USER			   = 'IB_USER'; 
    const VK_USER_INFO		   = 'IB_USER_INFO';

    // Callback URLs
    const SUCCESS_URL       = 'IB_RETURN';
    const CANCEL_URL        = 'IB_RETURN';

    // Request configs
    // This data will most likely be static
    const PROTOCOL_VERSION  = 'IB_VERSION';

    const SIGNATURE         = 'IB_CRC';

    const VK_INFO           = 'IB_INFO';
    const VK_DATE		    = 'IB_DATE';
    const VK_TIME		    = 'IB_TIME';
    const VK_DATETIME		= 'IB_DATETIME';
    const VK_AUTO			= 'IB_AUTO';

    /**
     * Can't instantiate this class
     */
    private function __construct() {}
}
