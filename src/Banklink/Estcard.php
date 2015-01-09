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
		'delivery' 		=> 'S', 		// S – Electronic delivery; T – Physical delivery 
	);

    public function __construct($requestUrl = null, $returnUrl = null, $id = null)
    {
    	$this->requestUrl = $requestUrl;
    	$this->parameters['id'] = $id;
    	$this->parameters['feedBackUrl'] = sprintf("%-128s", "$returnUrl");
    }

    public function preparePaymentRequest($order_id, $sum, $message)
    {
    	$this->parameters['datetime'] 	= date("YmdHis");
    	$this->parameters['ecuno'] 		= sprintf("%012s", time() . rand(10, 99));
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
    	
    }
}