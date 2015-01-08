<?php

namespace Banklink;

class Estcard extends Banklink
{
    protected $requestUrl = '';
    protected $testRequestUrl = '';

    public function __construct($protocol, $testMode = false, $requestUrl = null)
    {
    	
    }

    public function preparePaymentRequest($order_id, $sum, $message)
    {
    	
    }
    
    public function getRequestUrl()
    {
    	
    } 
    
    public function buildRequestHtml()
    {
    	
    }
}