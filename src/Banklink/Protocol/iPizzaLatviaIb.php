<?php

namespace Banklink\Protocol;

use Banklink\Protocol\iPizzaLatvia\Fields,
    Banklink\Protocol\iPizzaLatvia\Services;

use Banklink\Protocol\iPizzaLatvia\FieldsIB;
use Banklink\Response\PaymentResponse;
use Banklink\Response\AuthResponse;

use Banklink\Protocol\Util\ProtocolUtils;


/**
 * This class implements iPizza protocol support
 *
 * @author Roman Marintsenko <inoryy@gmail.com>
 * @since  11.01.2012
 */
class iPizzaLatviaIb extends iPizzaLatvia
{
    /**
     * @var string FieldsIB
     */
    protected $fieldsClass = FieldsIB::class;

    public function prepareAuthRequestData()
    {
        $requestData = [
            $this->fieldsClass::SERVICE_ID => Services::IB_AUTHENTICATE_REQUEST,
            $this->fieldsClass::SELLER_ID => $this->sellerId,
            $this->fieldsClass::USER_LANG => 'LAT',
        ];

        $requestData = ProtocolUtils::convertValues($requestData, 'UTF-8', 'UTF-8');

        $requestData[Fields::SIGNATURE] = $this->getRequestSignature($requestData);

        return $requestData;

    }
    public function handleAuthResponse(array $responseData, $verificationSuccess)
    {
        // if response was verified, try to guess status by service id
        if ($verificationSuccess) {
            $status = $responseData[$this->fieldsClass::SERVICE_ID] == Services::IB_AUTHENTICATE_SUCCESS ? PaymentResponse::STATUS_SUCCESS : PaymentResponse::STATUS_CANCEL;
        } else {
            $status = PaymentResponse::STATUS_ERROR;
        }

        $response = new AuthResponse($status, $responseData);
        $response->setPersonalCode($responseData[$this->fieldsClass::VK_USER]);

        if (AuthResponse::STATUS_SUCCESS === $status) {
            $infoField = explode(';', $responseData[$this->fieldsClass::VK_INFO]);
            $infoFields = [];

            foreach($infoField as $field) {
                list($name, $value) = explode('=', $field);
                $infoFields[$name] = $value;
            }

            $fullname = isset($infoFields['USER']) ? $infoFields['USER'] : $infoFields['NAME'];

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
}
