<?php

namespace Banklink\Protocol;

use Banklink\Protocol\Solo\Fields,
    Banklink\Protocol\Solo\Services;

use Banklink\Protocol\Solo\Response\PaymentResponse,
	Banklink\Response\AuthResponse;

use Banklink\Protocol\Util\ProtocolUtils;

class Solo implements ProtocolInterface
{
    protected $privateKey;

    protected $sellerId;
    protected $sellerName;
    protected $sellerAccountNumber;

    protected $endpointUrl;

    protected $protocolVersion;

    protected $keyVersion;

    /**
     * Encoding algorithm that is used when calculating MAC signature
     *
     * @var string
     */
    protected $algorithm;


    public function __construct($sellerId, $privateKey, $endpointUrl, $sellerName = null, $sellerAccNum = null, $algorithm = 'md5', $version = '0003', $keyVersion = '0001')
    {
        $this->sellerId            = $sellerId;
        $this->sellerName          = $sellerName;
        $this->sellerAccountNumber = $sellerAccNum;
        $this->endpointUrl         = $endpointUrl;

        $this->privateKey          = $privateKey;

        $this->algorithm           = $algorithm;

        $this->protocolVersion     = $version;
        $this->keyVersion          = $keyVersion;
    }
    
    
    public function handleResponse(array $responseData, $inputEncoding)
    {
        $verification = $this->verifyResponseSignature($responseData, $inputEncoding);
        $responseData = ProtocolUtils::convertValues($responseData, $inputEncoding, 'UTF-8');

        return $this->handlePaymentResponse($responseData, $verification);
    }


    public function handleAuthResponse(array $responseData, $inputEncoding)
    {

    	$hash = $this->generateHash($responseData, Services::getAuthResponseFields());
    	$verification = $hash === $responseData[Fields::B02K_MAC];

    	$responseData = ProtocolUtils::convertValues($responseData, $inputEncoding, 'UTF-8');

        if ($verification) {
            $status = 1;
        } else {
            $status = 0;
        }

        $response = new AuthResponse($status, $responseData);

        if ($status == 1) {
            $response->setPersonalCode($responseData[Fields::B02K_CUSTID]);
            $fullname = $responseData[Fields::B02K_CUSTNAME];
            $response->setFirstname(substr($fullname, 0, strpos($fullname, ' ')));
            $response->setLastname(substr($fullname, strpos($fullname, ' ')+1));
        }

        return $response;
    }


    public function preparePaymentRequestData($orderId, $sum, $message, $outputEncoding, $language = 'EST', $currency = 'EUR')
    {
        $requestData = array(
            Fields::PROTOCOL_VERSION    => $this->protocolVersion,
            Fields::MAC_KEY_VERSION     => $this->keyVersion,
            Fields::SELLER_ID           => $this->sellerId,
            Fields::ORDER_ID            => $orderId,
            Fields::SUM                 => $sum,
            Fields::CURRENCY            => $currency,
            Fields::ORDER_REFERENCE     => ProtocolUtils::generateOrderReference($orderId),
            Fields::DESCRIPTION         => $message,
            Fields::SUCCESS_URL         => $this->endpointUrl,
            Fields::CANCEL_URL          => $this->endpointUrl,
            Fields::REJECT_URL          => $this->endpointUrl,
            Fields::USER_LANG           => $this->getLanguageCodeForString($language),
            Fields::TRANSACTION_DATE    => 'EXPRESS',
            Fields::TRANSACTION_CONFIRM => 'YES'
        );

        // Solo protocol doesn't require seller name/account, unless it's different than default one specified
        if ($this->sellerName && $this->sellerAccountNumber) {
            $requestData[Fields::SELLER_NAME]     = $this->sellerName;
            $requestData[Fields::SELLER_BANK_ACC] = $this->sellerAccountNumber;
        }

        $requestData = ProtocolUtils::convertValues($requestData, 'UTF-8', $outputEncoding);

        $requestData[Fields::SIGNATURE] = $this->getRequestSignature($requestData);

        return $requestData;
    }


    public function prepareAuthRequestData($language = 'ET')
    {
    	$requestData = array(
    		    Fields::A01Y_ACTION_ID	=> '701',
			    Fields::A01Y_VERS		=> '0002',
			    Fields::A01Y_RCVID		=> $this->sellerId,
			    Fields::A01Y_LANGCODE	=> $language,
			    Fields::A01Y_STAMP		=> date('YmdHis', time()).substr($this->sellerId, 0, 6),
			    Fields::A01Y_IDTYPE		=> '02',
			    Fields::A01Y_RETLINK	=> $this->endpointUrl,
			    Fields::A01Y_CANLINK	=> $this->endpointUrl,
			    Fields::A01Y_REJLINK	=> $this->endpointUrl,
			    Fields::A01Y_KEYVERS	=> $this->keyVersion,
                Fields::A01Y_ALG		=> $this->algorithm == 'md5' ? '01' : '02'

        );

    	$requestData = ProtocolUtils::convertValues($requestData, 'UTF-8', 'ISO-8859-1');

    	$requestData[Fields::A01Y_MAC] = $this->getAuthRequestSignature($requestData);

    	return $requestData;
    }


    protected function handlePaymentResponse(array $responseData, $verification)
    {
        // if response was verified, try to guess status by service id
        if ($verification) {
            $status = isset($responseData[Fields::PAYMENT_CODE]) ? PaymentResponse::STATUS_SUCCESS : PaymentResponse::STATUS_CANCEL;
        } else {
            $status = PaymentResponse::STATUS_ERROR;
        }

        $response = new PaymentResponse($status, $responseData);
        $response->setOrderId($responseData[Fields::ORDER_ID_RESPONSE]);

        if (PaymentResponse::STATUS_SUCCESS === $status) {
            $response->setPaymentCode($responseData[Fields::PAYMENT_CODE]);
        }

        return $response;
    }


    protected function getRequestSignature($data)
    {
        return $this->generateHash($data, Services::getPaymentFields());
    }


    protected function getAuthRequestSignature($data)
    {
        return $this->generateHash($data, Services::getAuthRequestFields());
    }


    protected function verifyResponseSignature(array $responseData, $encoding)
    {
        if (!isset($responseData[Fields::SIGNATURE_RESPONSE])) {
            return false;
        }

        $fields = isset($responseData[Fields::PAYMENT_CODE]) ? Services::getPaymentResponseSuccessFields() : Services::getPaymentResponseCancelFields();

        $hash = $this->generateHash($responseData, $fields);

        return $hash === $responseData[Fields::SIGNATURE_RESPONSE];
    }


    protected function generateHash(array $data, array $fields)
    {
        $string = '';
        foreach ($fields as $fieldName) {

            if (!isset($data[$fieldName])) {
                throw new \LogicException(sprintf('Cannot generate payment service hash without %s field', $fieldName));
            }

            $string .= $data[$fieldName].'&';
        }
        $string .= $this->privateKey.'&';

        return strtoupper(hash($this->algorithm, $string));
    }


    protected function getLanguageCodeForString($string)
    {
       $codes = array('ENG' => 3, 'EST' => 4, 'LAT' => 6, 'LIT' => 7);

       if (!isset($codes[$string])) {
           throw new \InvalidArgumentException(sprintf('This language string (%s) is not supported', $string));
       }

       return $codes[$string];
    }
}
