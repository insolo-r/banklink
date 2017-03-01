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
    	$this->mb_sprintf("%-40s", $this->responseFields['msgdata']) .
    	$this->mb_sprintf("%-40s", $this->responseFields['actiontext']);

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

    private function mb_sprintf($format) {
    	$argv = func_get_args() ;
    	array_shift($argv) ;
    	return $this->mb_vsprintf($format, $argv) ;
    }

    private function mb_vsprintf($format, $argv, $encoding=null) {
	      if (is_null($encoding))
	          $encoding = mb_internal_encoding();

	      // Use UTF-8 in the format so we can use the u flag in preg_split
	      $format = mb_convert_encoding($format, 'UTF-8', $encoding);

	      $newformat = ""; // build a new format in UTF-8
	      $newargv = array(); // unhandled args in unchanged encoding

	      while ($format !== "") {

	        // Split the format in two parts: $pre and $post by the first %-directive
	        // We get also the matched groups
	        list ($pre, $sign, $filler, $align, $size, $precision, $type, $post) =
	            preg_split("!\%(\+?)('.|[0 ]|)(-?)([1-9][0-9]*|)(\.[1-9][0-9]*|)([%a-zA-Z])!u",
	                       $format, 2, PREG_SPLIT_DELIM_CAPTURE) ;

	        $newformat .= mb_convert_encoding($pre, $encoding, 'UTF-8');

	        if ($type == '') {
	          // didn't match. do nothing. this is the last iteration.
	        }
	        elseif ($type == '%') {
	          // an escaped %
	          $newformat .= '%%';
	        }
	        elseif ($type == 's') {
	          $arg = array_shift($argv);
	          $arg = mb_convert_encoding($arg, 'UTF-8', $encoding);
	          $padding_pre = '';
	          $padding_post = '';

	          // truncate $arg
	          if ($precision !== '') {
	            $precision = intval(substr($precision,1));
	            if ($precision > 0 && mb_strlen($arg,$encoding) > $precision)
	              $arg = mb_substr($precision,0,$precision,$encoding);
	          }

	          // define padding
	          if ($size > 0) {
	            $arglen = mb_strlen($arg, $encoding);
	            if ($arglen < $size) {
	              if($filler==='')
	                  $filler = ' ';
	              if ($align == '-')
	                  $padding_post = str_repeat($filler, $size - $arglen);
	              else
	                  $padding_pre = str_repeat($filler, $size - $arglen);
	            }
	          }

	          // escape % and pass it forward
	          $newformat .= $padding_pre . str_replace('%', '%%', $arg) . $padding_post;
	        }
	        else {
	          // another type, pass forward
	          $newformat .= "%$sign$filler$align$size$precision$type";
	          $newargv[] = array_shift($argv);
	        }
	        $format = strval($post);
	      }
	      // Convert new format back from UTF-8 to the original encoding
	      $newformat = mb_convert_encoding($newformat, $encoding, 'UTF-8');
	      return vsprintf($newformat, $newargv);
	  }

    public function getRequestData()
    {
        return $this->parameters;
    }


}