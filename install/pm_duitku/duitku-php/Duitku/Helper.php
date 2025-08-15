<?php

use Joomla\Component\Jshopping\Site\Helper\Helper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\CMS\Uri\Uri;


class DuitkuHelper
{
    public static function buildCustomerDetail($order)
    {
        $billingCountryCode = self::getCountryCode($order->country ?? '');
        $shippingCountryCode = self::getCountryCode($order->d_country ?? $order->country ?? '');

        return array(
            'firstName' => $order->f_name,
            'lastName' => $order->l_name,
            'email' => $order->email,
            'phoneNumber' => $order->phone,
            'billingAddress' => array(
                'firstName' => $order->f_name,
                'lastName' => $order->l_name,
                'address' => trim(($order->street) . ' ' . ($order->street_nr)),
                'city' => $order->city,
                'postalCode' => $order->zip,
                'phone' => $order->phone,
                'countryCode' => $billingCountryCode
            ),
            'shippingAddress' => array(
                'firstName' => $order->d_f_name ?? $order->f_name,
                'lastName' => $order->d_l_name ?? $order->l_name,
                'address' => trim(($order->d_street ?? $order->street) . ' ' . ($order->d_street_nr ?? $order->street_nr)),
                'city' => $order->d_city ?? $order->city,
                'postalCode' => $order->d_zip ?? $order->zip,
                'phone' => $order->phone,
                'countryCode' => $shippingCountryCode
            )
        );
    }

    // IGNORE: Fragile to Error (rounding-problem)
    public static function buildItemDetails($order)
    {
        $itemDetails = array();

        // Load order items using JoomShopping table method
        $orderTable = JSFactory::getTable('order');
        $orderTable->load($order->order_id);
        $orderItems = $orderTable->getAllItems(); // IGNORE (Red underline is linter error)
        Helper::saveToLog("duitku.log", "INFO: [buildItemDetails] Loaded " . count($orderItems) . " order items using JSFactory::getTable('order')");

        // Item details
        if (!empty($orderItems)) {
            foreach ($orderItems as $item) {
                $itemDetails[] = array(
                    'name' => $item->product_name ?? 'Product',
                    'quantity' => (int)($item->product_quantity),
                    'price' => (int)($item->product_item_price)
                );
            }
        }

        // Shipping
        if (isset($order->order_shipping) && $order->order_shipping > 0) {
            $itemDetails[] = array(
                'name' => 'Shipping',
                'quantity' => 1,
                'price' => (int)round($order->order_shipping)
            );
        }

        // Manual calculation for assurance
        $calculatedTotal = 0;
        $orderTotal = (int)round($order->order_total);
        foreach ($itemDetails as $item) {
            $calculatedTotal += $item['price'] * $item['quantity'];
        }
        if ($calculatedTotal !== $orderTotal) {
            $difference = $orderTotal - $calculatedTotal;
            Helper::saveToLog("duitku.log", "ERROR: [buildItemDetails] Calculated price is $calculatedTotal instead of $orderTotal (Differ $difference)");
        } else {
            Helper::saveToLog("duitku.log", "INFO: [buildItemDetails] Build item details success - $calculatedTotal");
        }

        return $itemDetails;
    }

    public static function getCountryCode($countryId)
    {
        try {
            $country = JSFactory::getTable('country');
            $country->load($countryId);
            return $country->country_code_2 ?? '';
        } catch (Exception $e) {
            Helper::saveToLog("duitku.log", "WARNING: Error getting country code - " . $e->getMessage());
            return '';
        }
    }


    public static function getBaseUrl($pmconfigs)
    {
        $uri = Uri::getInstance();
        $scheme = $uri->toString(['scheme']);
        $host = $uri->toString(['host', 'port']);
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        if ($basePath === '/') $basePath = '';
        $baseUrl = $scheme . $host . $basePath;

        $environment = isset($pmconfigs['environment']) ? $pmconfigs['environment'] : 'sandbox';
        Helper::saveToLog("duitku.log", "INFO: Base URL: " . $baseUrl . " ($environment)");
        return $baseUrl;
    }

    public static function getSEFLink($act, $baseUrl, $orderId, $paymentClass)
    {
        return $baseUrl . Helper::SEFLink("/index.php?option=com_jshopping&controller=checkout&task=step7&act=" . $act . "&js_paymentclass=" . $paymentClass . "&custom=" . $orderId);
    }
}
