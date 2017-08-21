<?php

namespace Banklink;


/**
 * Banklink implementation for SEB bank using iPizza protocol for communication
 * For specs see http://seb.ee/en/business/collection-payments/collection-payments-web/bank-link-specification
 *
 * @author Roman Marintsenko <inoryy@gmail.com>
 * @since  11.01.2012
 */
class SEBLatvia extends Banklink
{
    protected $requestUrl = 'https://ibanka.seb.lv/ipc/epakindex.jsp';
    protected $testRequestUrl = 'https://ibanka.seb.lv/ipc/epakindex.jsp';


    /**
     * Force iPizza protocol
     *
     * @param \Banklink\Protocol\iPizza $protocol
     * @param boolean                   $testMode
     * @param string | null             $requestUrl
     */
    public function __construct($protocol, $testMode = false, $requestUrl = null)
    {
        parent::__construct($protocol, $testMode, $requestUrl);
    }

    /**
     * Force UTF-8 encoding
     *
     * @see Banklink::getAdditionalFields()
     *
     * @return array
     */
    protected function getAdditionalFields()
    {
        return array(
        );
    }
}
