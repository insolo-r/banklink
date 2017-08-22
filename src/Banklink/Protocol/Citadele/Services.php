<?php

namespace Banklink\Protocol\Citadele;

final class Services
{
    // Requests
    const PAYMENT_REQUEST      = 'PMTREQ';
    const AUTHENTICATE_REQUEST = 'AUTHREQ';

    // Responses
    const PAYMENT_SUCCESS      = 100;
    const PAYMENT_CANCEL       = 200;
    const PAYMENT_ERROR        = 200;
    const AUTHENTICATE_SUCCESS = '';

    /**
     * Can't instantiate this class
     */
    private function __construct() {}
}