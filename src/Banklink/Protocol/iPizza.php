<?php

namespace Banklink\Protocol;

use Banklink\Protocol\iPizza\Fields,
    Banklink\Protocol\iPizza\Services;

use Banklink\Response\PaymentResponse;
use Banklink\Response\AuthResponse;

use Banklink\Protocol\Util\ProtocolUtils;


/**
 * This class implements iPizza protocol support
 *
 * @author Roman Marintsenko <inoryy@gmail.com>
 * @since  11.01.2012
 */
class iPizza implements ProtocolInterface
{
    protected $publicKey;
    protected $privateKey;

    protected $sellerId;
    protected $sellerName;
    protected $sellerAccountNumber;

    protected $endpointUrl;

    protected $protocolVersion;

    protected $mbStrlen;

    /**
     * initialize basic data that will be used for all issued service requests
     *
     * @param string  $sellerId
     * @param string  $sellerName
     * @param integer $sellerAccNum
     * @param string  $privateKey    Private key location
     * @param string  $publicKey     Public key (certificate) location
     * @param string  $endpointUrl
     * @param string  $version
     * @param boolean $mbStrlen      Use mb_strlen for string length calculation?
     */
    public function __construct($sellerId, $sellerName, $sellerAccNum, $privateKey, $publicKey, $endpointUrl, $mbStrlen = false, $version = '008')
    {
        $this->sellerId            = $sellerId;
        $this->sellerName          = $sellerName;
        $this->sellerAccountNumber = $sellerAccNum;
        $this->endpointUrl         = $endpointUrl;

        $this->publicKey           = $publicKey;
        $this->privateKey          = $privateKey;

        $this->mbStrlen            = $mbStrlen;

        $this->protocolVersion     = $version;
    }

    /**
     * @param integer  $orderId
     * @param float    $sum
     * @param string   $message
     * @param string   $outputEncoding
     * @param string   $language
     * @param string   $currency
     *
     * @return array
     */
    public function preparePaymentRequestData($orderId, $sum, $message, $outputEncoding, $language = 'EST', $currency = 'EUR')
    {

    	$datetime = new \DateTime('now', new \DateTimeZone('Europe/Tallinn'));

        $requestData = array(
            Fields::SERVICE_ID       => Services::PAYMENT_REQUEST,
            Fields::PROTOCOL_VERSION => $this->protocolVersion,
            Fields::SELLER_ID        => $this->sellerId,
            Fields::ORDER_ID         => $orderId,
            Fields::SUM              => $sum,
            Fields::CURRENCY         => $currency,
            Fields::SELLER_BANK_ACC  => $this->sellerAccountNumber,
            Fields::SELLER_NAME      => $this->sellerName,
            Fields::ORDER_REFERENCE  => ProtocolUtils::generateOrderReference($orderId),
            Fields::DESCRIPTION      => $message,
            Fields::SUCCESS_URL      => $this->endpointUrl,
            Fields::CANCEL_URL       => $this->endpointUrl,
            Fields::USER_LANG        => $language,
        	Fields::VK_DATETIME		 => $datetime->format(DATE_ISO8601) //date(DATE_ISO8601, time()),
        );

        $requestData = ProtocolUtils::convertValues($requestData, 'UTF-8', $outputEncoding);

        $requestData[Fields::SIGNATURE] = $this->getRequestSignature($requestData);

        return $requestData;
    }


    public function prepareAuthRequestData()
    {
    	$datetime = new \DateTime('now', new \DateTimeZone('Europe/Tallinn'));

    	$requestData = array(
            Fields::SERVICE_ID      => Services::AUTHENTICATE_REQUEST,
            Fields::PROTOCOL_VERSION=> $this->protocolVersion,
            Fields::SELLER_ID       => $this->sellerId,
    		Fields::VK_REPLY		=> 3012,
    		Fields::SUCCESS_URL		=> $this->endpointUrl,
    		Fields::VK_DATETIME		=> $datetime->format(DATE_ISO8601),
    		Fields::VK_RID			=> rand(1111,9999)
    	);

    	$requestData = ProtocolUtils::convertValues($requestData, 'UTF-8', 'UTF-8');

    	$requestData[Fields::SIGNATURE] = $this->getRequestSignature($requestData);

    	return $requestData;

    }

    /**
     * Determine which response exactly by service id, if it's supported then call related internal method
     *
     * @param array  $responseData
     * @param string $inputEncoding
     *
     * @return \Banklink\Response\Response
     *
     * @throws \InvalidArgumentException
     */
    public function handleResponse(array $responseData, $inputEncoding)
    {
        $verificationSuccess = $this->verifyResponseSignature($responseData, $inputEncoding);

        $responseData = ProtocolUtils::convertValues($responseData, $inputEncoding, 'UTF-8');

        $service = $responseData[Fields::SERVICE_ID];
        if (in_array($service, Services::getPaymentServices())) {
            return $this->handlePaymentResponse($responseData, $verificationSuccess);
        }
        if (in_array($service, Services::getAuthenticationServices())) {
            return $this->handleAuthResponse($responseData, $verificationSuccess);
        }

        throw new \InvalidArgumentException('Unsupported service with id: '.$service);
    }

    /**
     * Prepare payment response instance
     * Some data is only set if response is succesful
     *
     * @param array $responseData
     *
     * @return \Banklink\Response\PaymentResponse
     */
    protected function handlePaymentResponse(array $responseData, $verificationSuccess)
    {
        // if response was verified, try to guess status by service id
        if ($verificationSuccess) {
            $status = $responseData[Fields::SERVICE_ID] == Services::PAYMENT_SUCCESS ? PaymentResponse::STATUS_SUCCESS : PaymentResponse::STATUS_CANCEL;
        } else {
            $status = PaymentResponse::STATUS_ERROR;
        }

        $response = new PaymentResponse($status, $responseData);
        $response->setOrderId($responseData[Fields::ORDER_ID]);

        if (PaymentResponse::STATUS_SUCCESS === $status) {
            $response->setSum($responseData[Fields::SUM]);
            $response->setCurrency($responseData[Fields::CURRENCY]);
            $response->setSenderName($responseData[Fields::SENDER_NAME]);
            $response->setSenderBankAccount($responseData[Fields::SENDER_BANK_ACC]);
            $response->setTransactionId($responseData[Fields::TRANSACTION_ID]);
            $response->setTransactionDate(new \DateTime($responseData[Fields::TRANSACTION_DATE]));
        }

        return $response;
    }


    public function handleAuthResponse(array $responseData, $verificationSuccess)
    {
    	// if response was verified, try to guess status by service id
    	if ($verificationSuccess) {
    		$status = $responseData[Fields::SERVICE_ID] == Services::AUTHENTICATE_SUCCESS ? PaymentResponse::STATUS_SUCCESS : PaymentResponse::STATUS_CANCEL;
    	} else {
    		$status = PaymentResponse::STATUS_ERROR;
    	}

    	$response = new AuthResponse($status, $responseData);
    	$response->setPersonalCode($responseData[Fields::VK_USER_ID]);

    	if (AuthResponse::STATUS_SUCCESS === $status) {

    		$fullname = $responseData[Fields::VK_USER_NAME];
    		if(strpos($fullname, ',') !== false){
    			$response->setFirstname(substr($fullname, 0, strpos($fullname, ',')));
    			$response->setLastname(substr($fullname, strpos($fullname, ',')+1));
    		} else {
	    		$response->setFirstname(substr($fullname, 0, strpos($fullname, ' ')));
	    		$response->setLastname(substr($fullname, strpos($fullname, ' ')+1));
    		}

    	}

    	return $response;
    }

    /**
     * Generate request signature built with mandatory request data and private key
     *
     * @param array  $data
     * @param string $encoding
     *
     * @return string
     */
    protected function getRequestSignature($fields)
    {
        $hash = $this->generateHash($fields);

        $keyId = openssl_get_privatekey($this->privateKey);
        openssl_sign($hash, $signature, $keyId);
        openssl_free_key($keyId);

        $result = base64_encode($signature);
        return $result;
    }

    /**
     * Verify that response data is correctly signed
     *
     * @param array  $responseData
     * @param string $encoding Response data encoding
     *
     * @return boolean
     */
    protected function verifyResponseSignature(array $responseData, $encoding)
    {
        $hash = $this->generateHash($responseData, $encoding);

        $keyId = openssl_pkey_get_public($this->publicKey);

        $result = openssl_verify($hash, base64_decode($responseData[Fields::SIGNATURE]), $keyId);

        openssl_free_key($keyId);

        return $result === 1;
    }

    /**
     * Generate request/response hash based on mandatory fields
     *
     * @param array  $data
     * @param string $encoding Data encoding
     *
     * @return string
     *
     * @throws \LogicException
     */
    protected function generateHash(array $data, $encoding = 'UTF-8')
    {
        $id = $data[Fields::SERVICE_ID];

        $hash = '';

        foreach (Services::getFieldsForService($id) as $fieldName) {
            if (!isset($data[$fieldName])) {
                throw new \LogicException(sprintf('Cannot generate %s service hash without %s field', $id, $fieldName));
            }

            $content = $data[$fieldName];
            if($this->mbStrlen){
            	$hash .= str_pad (mb_strlen($content, $encoding), 3, "0", STR_PAD_LEFT) . $content;
            } else {
           		$hash .= str_pad (strlen($content), 3, "0", STR_PAD_LEFT) . $content;
            }
        }
        return $hash;
    }
}