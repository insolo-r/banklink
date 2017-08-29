<?php

namespace Banklink;

use Banklink\Protocol\CitadeleProtocol;

class Citadele extends Banklink
{
    protected $requestUrl = 'https://online.citadele.lv/amai/start.htm';
    protected $testRequestUrl = 'https://astra.citadele.lv/amai/start.htm';

    /**
     * Citadele constructor
     *
     * @param CitadeleProtocol   $protocol
     * @param boolean            $testMode
     * @param string|null        $requestUrl
     */
    public function __construct(CitadeleProtocol $protocol, $testMode = false, $requestUrl = null)
    {
        parent::__construct($protocol, $testMode, $requestUrl);
    }

    /**
     *
     * @see Banklink::getAdditionalFields()
     *
     * @return array
     */
    protected function getAdditionalFields()
    {
        return [];
    }
}