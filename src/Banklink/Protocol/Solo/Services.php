<?php

namespace Banklink\Protocol\Solo;

/**
 * Solo services helper class
 *
 * @author Roman Marintsenko <inoryy@gmail.com>
 * @since  25.11.2012
 */
final class Services
{
    /**
     * Get array of mandatory fields for payment request
     *
     * @return array
     */
    public static function getPaymentFields()
    {
        return array(
            Fields::PROTOCOL_VERSION,
            Fields::ORDER_ID,
            Fields::SELLER_ID,
            Fields::SUM,
            Fields::ORDER_REFERENCE,
            Fields::TRANSACTION_DATE,
            Fields::CURRENCY,
        );
    }

    /**
     * Get array of mandatory fields for successful payment response
     *
     * @return array
     */
    public static function getPaymentResponseSuccessFields()
    {
        return array(
            Fields::PROTOCOL_VERSION_RESPONSE,
            Fields::ORDER_ID_RESPONSE,
            Fields::ORDER_REFERENCE_RESPONSE,
            Fields::PAYMENT_CODE,
        );
    }

    /**
     * Get array of mandatory fields for cancelled payment response
     *
     * @return array
     */
    public static function getPaymentResponseCancelFields()
    {
        return array(
            Fields::PROTOCOL_VERSION_RESPONSE,
            Fields::ORDER_ID_RESPONSE,
            Fields::ORDER_REFERENCE_RESPONSE,
        );
    }
    
    public static function getAuthRequestFields(){
    	return array(
    			Fields::A01Y_ACTION_ID,
    			Fields::A01Y_VERS,
    			Fields::A01Y_RCVID,
    			Fields::A01Y_LANGCODE,
    			Fields::A01Y_STAMP,
    			Fields::A01Y_IDTYPE,
    			Fields::A01Y_RETLINK,
    			Fields::A01Y_CANLINK,
    			Fields::A01Y_REJLINK,
    			Fields::A01Y_KEYVERS,
    			Fields::A01Y_ALG
    	);
    }

    public static function getAuthResponseFields()
    {
    	return array(
			Fields::B02K_VERS,
			Fields::B02K_TIMESTMP,
			Fields::B02K_IDNBR,
			Fields::B02K_STAMP,
			Fields::B02K_CUSTNAME,
			Fields::B02K_KEYVERS,
			Fields::B02K_ALG,
			Fields::B02K_CUSTID,
			Fields::B02K_CUSTTYPE
    	);
    }
    
    /**
     * Can't instantiate this class
     */
    private function __construct() {}
}