<?php

namespace Banklink\Protocol\Solo;

/**
 * List of all fields used by Solo protocol
 *
 * @author Roman Marintsenko <inoryy@gmail.com>
 * @since  25.11.2011
 */
final class Fields
{
    // Order data
    const SUM               = 'SOLOPMT_AMOUNT';
    const ORDER_ID          = 'SOLOPMT_STAMP';
    const ORDER_REFERENCE   = 'SOLOPMT_REF';
    const CURRENCY          = 'SOLOPMT_CUR';
    const DESCRIPTION       = 'SOLOPMT_MSG';
    const USER_LANG         = 'SOLOPMT_LANGUAGE';

    // Seller (site owner) info
    const SELLER_ID                = 'SOLOPMT_RCV_ID';
    const SELLER_NAME              = 'SOLOPMT_RCV_NAME';
    const SELLER_BANK_ACC          = 'SOLOPMT_RCV_ACCOUNT';

    const TRANSACTION_DATE         = 'SOLOPMT_DATE';
    const TRANSACTION_CONFIRM      = 'SOLOPMT_CONFIRM';

    // data provided in response
    const PROTOCOL_VERSION_RESPONSE = 'SOLOPMT_RETURN_VERSION';
    const ORDER_ID_RESPONSE         = 'SOLOPMT_RETURN_STAMP';
    const PAYMENT_CODE              = 'SOLOPMT_RETURN_PAID';
    const ORDER_REFERENCE_RESPONSE  = 'SOLOPMT_RETURN_REF';
    const SIGNATURE_RESPONSE        = 'SOLOPMT_RETURN_MAC';

    // Callback URLs
    const SUCCESS_URL       = 'SOLOPMT_RETURN';
    const CANCEL_URL        = 'SOLOPMT_CANCEL';
    const REJECT_URL        = 'SOLOPMT_REJECT';

    // Request configs
    // This data will most likely be static
    const PROTOCOL_VERSION  = 'SOLOPMT_VERSION';
    const MAC_KEY_VERSION   = 'SOLOPMT_KEYVERS';

    const SIGNATURE         = 'SOLOPMT_MAC';
    
    // auth request
    const A01Y_ACTION_ID	= 'A01Y_ACTION_ID';
    const A01Y_VERS			= 'A01Y_VERS';
    const A01Y_RCVID		= 'A01Y_RCVID';
    const A01Y_LANGCODE		= 'A01Y_LANGCODE';
    const A01Y_STAMP		= 'A01Y_STAMP';
    const A01Y_IDTYPE		= 'A01Y_IDTYPE';
    const A01Y_RETLINK		= 'A01Y_RETLINK';
    const A01Y_CANLINK		= 'A01Y_CANLINK';
    const A01Y_REJLINK		= 'A01Y_REJLINK';
    const A01Y_KEYVERS		= 'A01Y_KEYVERS';
    const A01Y_ALG			= 'A01Y_ALG';
    const A01Y_MAC			= 'A01Y_MAC';

    // auth response
    const B02K_VERS			= 'B02K_VERS';
    const B02K_TIMESTMP		= 'B02K_TIMESTMP';
    const B02K_IDNBR		= 'B02K_IDNBR';
    const B02K_STAMP		= 'B02K_STAMP';
    const B02K_CUSTNAME		= 'B02K_CUSTNAME';
    const B02K_KEYVERS		= 'B02K_KEYVERS';
    const B02K_ALG			= 'B02K_ALG';
    const B02K_CUSTID		= 'B02K_CUSTID';
    const B02K_CUSTTYPE		= 'B02K_CUSTTYPE';
    const B02K_MAC			= 'B02K_MAC';
    
    /**
     * Can't instantiate this class
     */
    private function __construct() {}
}