<?php

defined('_JEXEC') or die('Restricted access');

class DuitkuConfig
{
    public static function getUrl($environment = 'sandbox')
    {
        if ($environment == 'sandbox') {
            return 'https://api-'. $environment .'.duitku.com/api/merchant/createInvoice';
        }
        return 'https://api-prod.duitku.com/api/merchant/createInvoice';
    }
}
