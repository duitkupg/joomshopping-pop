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

    public static function isProduction($environment)
    {
        return $environment === 'production';
    }

    public static function getEnvironments()
    {
        return [
            'sandbox' => 'Sandbox',
            'production' => 'Production'
        ];
    }

    public static function validateEnvironment($environment)
    {
        return in_array($environment, ['sandbox', 'production']) ? $environment : 'sandbox';
    }
}
