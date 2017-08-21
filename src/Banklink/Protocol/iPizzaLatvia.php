<?php

namespace Banklink\Protocol;

use Banklink\Protocol\iPizzaLatvia\Fields,
    Banklink\Protocol\iPizzaLatvia\Services;

use Banklink\Response\PaymentResponse;
use Banklink\Response\AuthResponse;

use Banklink\Protocol\Util\ProtocolUtils;


/**
 * This class implements iPizza protocol support
 *
 * @author Roman Marintsenko <inoryy@gmail.com>
 * @since  11.01.2012
 */
class iPizzaLatvia implements ProtocolInterface
{
    protected $publicKey;
    protected $privateKey;

    protected $sellerId;
    protected $sellerName;
    protected $sellerAccountNumber;

    protected $endpointUrl;

    protected $protocolVersion;

    protected $mbStrlen;
    protected $fieldsClass = Fields::class;

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
            $this->fieldsClass::SERVICE_ID       => Services::PAYMENT_REQUEST,
            $this->fieldsClass::PROTOCOL_VERSION => $this->protocolVersion,
            $this->fieldsClass::SELLER_ID        => $this->sellerId,
            $this->fieldsClass::ORDER_ID         => $orderId,
            $this->fieldsClass::SUM              => $sum,
            $this->fieldsClass::CURRENCY         => $currency,
//             $this->fieldsClass::SELLER_BANK_ACC  => $this->sellerAccountNumber,
//             $this->fieldsClass::SELLER_NAME      => $this->sellerName,
            $this->fieldsClass::ORDER_REFERENCE  => ProtocolUtils::generateOrderReference($orderId),
            $this->fieldsClass::DESCRIPTION      => $message,
            $this->fieldsClass::SUCCESS_URL      => $this->endpointUrl,
            $this->fieldsClass::CANCEL_URL       => $this->endpointUrl,
            $this->fieldsClass::USER_LANG        => $language,
        	$this->fieldsClass::VK_DATETIME		 => $datetime->format(DATE_ISO8601) //date(DATE_ISO8601, time()),
        );

        $requestData = ProtocolUtils::convertValues($requestData, 'UTF-8', $outputEncoding);

        $requestData[$this->fieldsClass::SIGNATURE] = $this->getRequestSignature($requestData);

        return $requestData;
    }


    public function prepareAuthRequestData()
    {
    	$datetime = new \DateTime('now', new \DateTimeZone('Europe/Tallinn'));

    	$requestData = array(
            $this->fieldsClass::SERVICE_ID      => Services::AUTHENTICATE_REQUEST,
            $this->fieldsClass::PROTOCOL_VERSION=> $this->protocolVersion,
            $this->fieldsClass::SELLER_ID       => $this->sellerId,
    		$this->fieldsClass::VK_REPLY		=> 3002,
    		$this->fieldsClass::SUCCESS_URL		=> $this->endpointUrl,
            $this->fieldsClass::VK_DATE		    => $datetime->format('Y-m-d'),
            $this->fieldsClass::VK_TIME			=> $datetime->format('H:i:s')
    	);

    	$requestData = ProtocolUtils::convertValues($requestData, 'UTF-8', 'UTF-8');

    	$requestData[$this->fieldsClass::SIGNATURE] = $this->getRequestSignature($requestData);

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

        $service = $responseData[$this->fieldsClass::SERVICE_ID];
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
            $status = $responseData[$this->fieldsClass::SERVICE_ID] == Services::PAYMENT_SUCCESS ? PaymentResponse::STATUS_SUCCESS : PaymentResponse::STATUS_CANCEL;
        } else {
            $status = PaymentResponse::STATUS_ERROR;
        }

        $response = new PaymentResponse($status, $responseData);
        $response->setOrderId($responseData[$this->fieldsClass::ORDER_ID]);

        if (PaymentResponse::STATUS_SUCCESS === $status) {
            $response->setSum($responseData[$this->fieldsClass::SUM]);
            $response->setCurrency($responseData[$this->fieldsClass::CURRENCY]);
            $response->setSenderName($responseData[$this->fieldsClass::SENDER_NAME]);
            $response->setSenderBankAccount($responseData[$this->fieldsClass::SENDER_BANK_ACC]);
            $response->setTransactionId($responseData[$this->fieldsClass::TRANSACTION_ID]);
            $response->setTransactionDate(new \DateTime($responseData[$this->fieldsClass::TRANSACTION_DATE]));
        }

        return $response;
    }


    public function handleAuthResponse(array $responseData, $verificationSuccess)
    {
    	// if response was verified, try to guess status by service id
    	if ($verificationSuccess) {
    		$status = $responseData[$this->fieldsClass::SERVICE_ID] == Services::AUTHENTICATE_SUCCESS ? PaymentResponse::STATUS_SUCCESS : PaymentResponse::STATUS_CANCEL;
    	} else {
    		$status = PaymentResponse::STATUS_ERROR;
    	}

    	$response = new AuthResponse($status, $responseData);
    	$response->setPersonalCode($responseData[$this->fieldsClass::VK_USER]);

    	if (AuthResponse::STATUS_SUCCESS === $status) {
            $infoField = explode(';', $responseData[$this->fieldsClass::VK_INFO]);
            $infoFields = [];

            foreach($infoField as $field) {
                list($name, $value) = explode(':', $field);
                $infoFields[$name] = $value;
            }

            // $idCode = $infoFields['ISIK'];
            $fullname = $infoFields['NIMI'];

    		if(strpos($fullname, ',') !== false){
    			$response->setLastname(substr($fullname, 0, strpos($fullname, ',')));
    			$response->setFirstname(substr($fullname, strpos($fullname, ',')+1));
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

        $result = openssl_verify($hash, base64_decode($responseData[$this->fieldsClass::SIGNATURE]), $keyId);

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
        $id = $data[$this->fieldsClass::SERVICE_ID];

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
