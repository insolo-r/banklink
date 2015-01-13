<?php

namespace Banklink;

class Estcard
{
    protected $requestUrl;
    
	protected $parameters = array(
		'lang' 			=> 'en', 		// Lang
		'action' 		=> 'gaf',
		'ver' 			=> '004', 		//cryptalgoritm version (lenght = 3)
		'id' 			=> false,
		'ecuno' 		=> false, 		//Order id (lenght = 20)
		'eamount' 		=> false, 		//Sum (lenght = 17)
		'cur' 			=> 'EUR', 		//Currency (lenght = 3)
		'datetime' 		=> '', 			//Account nr (lenght = 16)
		'mac' 			=> '', 			// Lang
		'charEncoding' 	=> 'UTF-8',
		'feedBackUrl' 	=> '', 			// Lang
		'delivery' 		=> 'S', 		// S â€“ Electronic delivery; T â€“ Physical delivery 
	);
	
	protected $responseFields = array();

    public function __construct($requestUrl = null, $returnUrl = null, $id = null)
    {
    	$this->requestUrl = $requestUrl;
    	$this->parameters['id'] = $id;
    	$this->parameters['feedBackUrl'] = sprintf("%-128s", "$returnUrl");
    }

    public function preparePaymentRequest($order_id, $sum, $message)
    {
    	$this->parameters['datetime'] 	= date("YmdHis");
    	$this->parameters['ecuno'] 		= sprintf("%012s", $order_id);
    	$this->parameters['eamount'] 	= sprintf("%012s", round($sum * 100));
    	
    	$sMacBase = $this->parameters['ver'] .
			    	sprintf("%-10s", $this->parameters['id']) .
			    	$this->parameters['ecuno'] .
			    	$this->parameters['eamount'] .
			    	$this->parameters['cur'] .
			    	$this->parameters['datetime'].
			    	$this->parameters['feedBackUrl'].
			    	$this->parameters['delivery'];
    	
    	$sSignature = sha1($sMacBase);
    	
    	if (!openssl_sign($sMacBase, $sSignature, openssl_get_privatekey(\Configuration::where('code', '=', 'estcard/privkey')->first()->value))) {
    		throw new PaymentException('Unable to generate signature');
    	}
    	$this->parameters['mac'] =bin2hex($sSignature);
    	
    	return $this;
    	
    }
    
    public function getRequestUrl()
    {
    	return $this->requestUrl;
    }
    
    public function buildRequestHtml()
    {   	
    	$output = '';
    	
    	foreach ($this->parameters as $key => $value) {
    		$output .= sprintf('<input id="%s" name="%s" value="%s" type="hidden" />', strtolower($key), $key, $value);
    	}
    	
    	return $output;
    }
    
    public function handleResponse()
    {
    	return $this;
    }

    public function isSuccesful()
    {
    	
    	foreach ((array)$_REQUEST as $ixField => $fieldValue) {
    		$this->responseFields[$ixField] = $fieldValue;
    	}
    	
    	$sSignatureBase = sprintf("%03s", $this->responseFields['ver']) .
    	sprintf("%-10s", $this->responseFields['id']) .
    	sprintf("%012s", $this->responseFields['ecuno']) .
    	sprintf("%06s", $this->responseFields['receipt_no']) .
    	sprintf("%012s", $this->responseFields['eamount']) .
    	sprintf("%3s", $this->responseFields['cur']) .
    	$this->responseFields['respcode'] .
    	$this->responseFields['datetime'] .
    	sprintf("%-40s", $this->responseFields['msgdata']) .
    	sprintf("%-40s", $this->responseFields['actiontext']);
    	
    	function hex2str($hex) {
    		$str = '';
    		for ($i = 0; $i < strlen($hex); $i += 2) {
    			$str .= chr(hexdec(substr($hex, $i, 2)));
    		}
    		return $str;
    	}
    	
    	$mac = hex2str($this->responseFields['mac']);
    	
    	$flKey = openssl_get_publickey(\Configuration::where('code', '=', 'estcard/pubkey')->first()->value);
    	
    	if (!openssl_verify($sSignatureBase, $mac, $flKey)) { // invalidSignature
			return false;
    	}
    	if ($this->responseFields['receipt_no'] == 000000) { # Payment was cancelled
			return false;
    	}

    	if ($this->responseFields['respcode'] == 000) { # Payment success
    		return true;
    	}
    }
    
    public function getOrderId()
    {
    	return sprintf("%012s", $this->responseFields['ecuno']);
    }
    	
    	
}