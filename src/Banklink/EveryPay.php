<?php
namespace Banklink;

use Banklink\Response\EverypayPaymentResponse;

class EveryPay
{

    protected $requestUrl;
    protected $apiSecret;
    protected $accountId;

    protected $requestFields = [
        'api_username'      => '', // Merchant API username. The value can be found in Merchant Portal in the Settings section.
        'account_id'        => '', // Processing account to be used for the transaction.
        'nonce'             => '', // Random unique value to prevent replay attacks
        'timestamp'         => '', // Time of creating the transaction. Expressed as seconds from January 1, 1970 UTC
        'callback_url'      => '', // Once EveryPay gateway has processed the transaction, processing result data is posted to this URL.
        'customer_url'      => '', // When the buyer clicks on the Back button, he will be redirected to this URL.
        'email'             => '', // E-mail address (buyer)
        'amount'            => '', // Payment amount
        'order_reference'   => '', // Order reference, must be unique for every payment attempt
        'user_ip'           => '', // IP-address (buyer)
        'billing_address'   => '',
        'billing_country'   => '',
        'billing_city'      => '',
        'billing_postcode'  => '',
//        'hmac'              => '',
        'hmac_fields'       => '',
        'transaction_type'  => '',
    ];

    public function __construct($apiUsername, $apiSecret, $accountId, $requestUrl, $customerUrl, $callbackUrl)
    {
        $this->apiSecret = $apiSecret;
        $this->accountId = $accountId;
        $this->requestUrl = $requestUrl;

        $this->requestFields['api_username'] = $apiUsername;
        $this->requestFields['account_id'] = $accountId;
        $this->requestFields['customer_url'] = $customerUrl;
        $this->requestFields['callback_url'] = $callbackUrl;
    }

    /**
     * @param $orderId
     * @param $sum
     * @param $message
     * @param string $language
     * @param string $currency
     * @return boolean
     */
    public function preparePaymentRequest(
        $orderId,
        $sum,
        $customerEmail,
        $userIp,
        $billingAddress,
        $billingCountry,
        $billingCity,
        $billingPostcode,
        $nonce,
        $language = 'et'
    )
    {
        $this->requestFields['nonce']           = $nonce;
        $this->requestFields['timestamp']       = time();
        $this->requestFields['email']           = $customerEmail;
        $this->requestFields['amount']          = $sum;
        $this->requestFields['order_reference'] = $orderId;
        $this->requestFields['user_ip']         = $userIp;
        $this->requestFields['billing_address'] = $billingAddress;
        $this->requestFields['billing_country'] = $billingCountry;
        $this->requestFields['billing_city']    = $billingCity;
        $this->requestFields['billing_postcode']= $billingPostcode;
        $this->requestFields['hmac_fields']     = '';
        $this->requestFields['transaction_type'] = 'charge';

        ksort($this->requestFields);

        $this->requestFields['hmac_fields'] = implode(',',  array_keys($this->requestFields));

        $this->requestFields['hmac'] = $this->signData($this->prepareData($this->requestFields));
        $this->requestFields['locale'] = $language;
    }

    public function buildRequestHtml()
    {
        $output = '';

        foreach ($this->requestFields as $key => $value) {
            $output .= sprintf('<input id="%s" name="%s" value="%s" type="hidden" />', strtolower($key), $key, $value);
        }

        return $output;
    }

    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    public function getRequestData()
    {
        return $this->requestFields;
    }

    /**
     * @param array $responseData
     *
     * @return EverypayPaymentResponse
     */
    public function handleResponse(array $responseData)
    {
        return new EverypayPaymentResponse($responseData, $this->apiSecret, $this->accountId);
    }

    protected function prepareData(array $fields)
    {
        $arr = array();
        foreach ($fields as $k => $v)
        {
            $arr[] = $k . '=' . $v;
        }
        return implode('&', $arr);
    }

    protected function signData($data)
    {
        return hash_hmac('sha1', $data, $this->apiSecret);
    }
}