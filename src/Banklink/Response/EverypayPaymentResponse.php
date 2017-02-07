<?php
namespace Banklink\Response;

class EverypayPaymentResponse
{
    protected $responseFields = [];
    protected $apiSecret;
    protected $accountId;
    protected $errorMessages;

    function __construct(array $responseData, $apiSecret, $accountId)
    {
        $this->responseFields = $responseData;
        $this->apiSecret = $apiSecret;
        $this->accountId = $accountId;
    }

    public function isSuccessful()
    {
        $fields = explode(',', $this->responseFields['hmac_fields']);
        sort($fields);

        $data = '';
        foreach ($fields as $field)
            $data .= $field . '=' . $this->responseFields[$field] . '&';

        $hmac = hash_hmac('sha1', substr($data, 0, -1), $this->apiSecret);
        if ($this->responseFields['hmac'] != $hmac) {
            $this->errorMessage = '(Everypay) Invalid HMAC';
            return false;
        }

        $now = time();
        if (($this->responseFields['timestamp'] > $now) || ($this->responseFields['timestamp'] < ($now - 300))) {
            $this->errorMessage = '(Everypay) Response outdated';
            return false;
        }

        if (isset($this->responseFields['account_id']) && ($this->responseFields['account_id'] !== $this->accountId)) {
            $this->errorMessage = '(Everypay) Invalid account ID';
            return false;
        }

        return $this->responseFields['transaction_result'] === 'completed' ? true : false;
    }

    public function getRawResponseData()
    {
        return $this->responseFields;
    }

    public function getSum()
    {
        return $this->responseFields['amount'];
    }

    public function getOrderId()
    {
        return $this->responseFields['order_reference'];
    }

    public function getErrorMessage()
    {
        return $this->errorMessages;
    }
}