<?php

namespace Banklink;

use Banklink\Protocol\iPizzaLatvia;

/**
 * Banklink implementation for Swedbank Latvia using iPizza protocol for communication
 * For specs see https://www.swedbank.lv/static/pdf/business/cash/BankLink_Tehn_apraksts_ar_autorizaciju_en.pdf
 *
 * @author Roman Marintsenko <inoryy@gmail.com>
 * @since  11.01.2012
 */
class SwedbankLatvia extends Banklink
{
    protected $requestUrl = 'https://ib.swedbank.lv/banklink';
    protected $testRequestUrl = 'https://pangalink.net/banklink/swedbank';

    /**
     * Force iPizza protocol
     *
     * @param iPizzaLatvia      $protocol
     * @param boolean           $testMode
     * @param string | null     $requestUrl
     */
    public function __construct(iPizzaLatvia $protocol, $testMode = false, $requestUrl = null)
    {
        parent::__construct($protocol, $testMode, $requestUrl);
    }

    /**
     * @inheritDoc
     */
    protected function getEncodingField()
    {
        return 'VK_ENCODING';
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
            'VK_ENCODING' => $this->requestEncoding
        );
    }
}