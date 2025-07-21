<?php

use Joomla\Component\Jshopping\Site\Helper\Helper;
use Joomla\CMS\Factory;

/**
 * Duitku Helper Class
 * 
 * This class provides utility methods for Duitku payment integration including
 * customer detail building, country code resolution, and signature generation.
 * 
 * @package    Duitku Payment Plugin
 * @author     Duitku Payment Gateway
 * @version    1.0.0
 * @since      1.0.0
 */
class DuitkuHelper
{
    /**
     * Generate Duitku API headers with signature
     * 
     * @param string $merchantCode The merchant code from Duitku
     * @param string $apiKey The API key from Duitku
     * @return array Array of headers required for Duitku API calls
     * @throws Exception If merchant code or API key is empty
     */
    public static function generateHeaders($merchantCode, $apiKey)
    {
        if (empty($merchantCode) || empty($apiKey)) {
            throw new Exception('Merchant Code and API Key are required for header generation');
        }

        $timestamp = self::getJakartaTimestamp();
        $signature = self::generateSignature($merchantCode, $timestamp, $apiKey);

        $headers = [
            'x-duitku-signature' => $signature,
            'x-duitku-timestamp' => $timestamp,
            'x-duitku-merchantcode' => $merchantCode,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        Helper::saveToLog("duitku.log", "INFO: POP API Headers generated - " . print_r($headers, true));
        return $headers;
    }


    /**
     * Get current timestamp in Jakarta timezone (milliseconds)
     * 
     * @return int Timestamp in milliseconds
     */
    private static function getJakartaTimestamp()
    {
        $jakarta_tz = new DateTimeZone('Asia/Jakarta');
        $datetime = new DateTime('now', $jakarta_tz);
        return $datetime->getTimestamp() * 1000;
    }

    /**
     * Generate signature for Duitku API authentication
     * 
     * @param string $merchantCode The merchant code
     * @param int $timestamp The timestamp in milliseconds
     * @param string $apiKey The API key
     * @return string SHA256 hash of the signature string
     */
    private static function generateSignature($merchantCode, $timestamp, $apiKey)
    {
        $signature_string = $merchantCode . $timestamp . $apiKey;
        return hash('sha256', $signature_string);
    }

    /**
     * Validate required headers for Duitku API
     * 
     * @param array $headers The headers array to validate
     * @return bool True if all required headers are present
     */
    public static function validateHeaders($headers)
    {
        $required = ['x-duitku-signature', 'x-duitku-timestamp', 'x-duitku-merchantcode'];

        foreach ($required as $header) {
            if (empty($headers[$header])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Build customer detail array for Duitku API
     * 
     * @param object $order The order object containing customer information
     * @return array Customer detail array formatted for Duitku API
     */
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

    /**
     * Build item details array for Duitku API with precise calculations
     * 
     * @param object $order The order object containing item information
     * @return array Item details array formatted for Duitku API (prices in whole IDR)
     */
    public static function buildItemDetails($order)
    {
        $itemDetails = array();

        $db = Factory::getDbo();
        $query = "SELECT * FROM `#__jshopping_order_item` WHERE `order_id` = " . (int)$order->order_id;
        $db->setQuery($query);
        $orderItems = $db->loadObjectList();
        Helper::saveToLog("duitku.log", "INFO: Loaded " . count($orderItems) . " order items from database");

        if (!empty($orderItems)) {
            foreach ($orderItems as $item) {
                $itemPrice = (float)($item->product_item_price ?? 0);
                $itemQuantity = (int)($item->product_quantity ?? 1);

                $itemDetails[] = array(
                    'name' => $item->product_name ?? 'Product',
                    'quantity' => $itemQuantity,
                    'price' => (int)round($itemPrice) // IDR doesn't use cents, send as whole number
                );
            }
        }

        // Add shipping if present
        if (isset($order->order_shipping) && $order->order_shipping > 0) {
            $shippingAmount = (float)$order->order_shipping;

            $itemDetails[] = array(
                'name' => 'Shipping',
                'quantity' => 1,
                'price' => (int)round($shippingAmount) // IDR doesn't use cents
            );
        }

        // Calculate total from item details and adjust if necessary
        $calculatedTotal = 0;
        foreach ($itemDetails as $item) {
            $calculatedTotal += $item['price'] * $item['quantity'];
        }

        // Order total as whole number (IDR doesn't use cents)
        $orderTotalWhole = (int)round($order->order_total);

        // Check if there's a discrepancy and adjust
        if ($calculatedTotal !== $orderTotalWhole) {
            $difference = $orderTotalWhole - $calculatedTotal;
            Helper::saveToLog("duitku.log", "INFO: Adjusting item total by $difference IDR to match order total");

            // Add or subtract the difference as an adjustment item
            if ($difference != 0) {
                $itemDetails[] = array(
                    'name' => $difference > 0 ? 'Tax/Fee Adjustment' : 'Discount Adjustment',
                    'quantity' => 1,
                    'price' => $difference
                );
            }
        }

        // Final verification
        $finalTotal = 0;
        foreach ($itemDetails as $item) {
            $finalTotal += $item['price'] * $item['quantity'];
        }

        Helper::saveToLog("duitku.log", "INFO: Final item total: " . $finalTotal . " IDR (matches order total: " . $order->order_total . " IDR)");

        return $itemDetails;
    }

    /**
     * Get country code from country ID
     * 
     * @param int|string $countryId The country ID from JShopping
     * @return string The 2-letter country code or empty string if not found
     */
    public static function getCountryCode($countryId)
    {
        if (empty($countryId)) return '';

        try {
            $db = Factory::getDbo();
            $query = "SELECT `country_code_2` FROM `#__jshopping_countries` WHERE `country_id` = " . (int)$countryId;
            $db->setQuery($query);
            $result = $db->loadResult();
            return $result ?? '';
        } catch (Exception $e) {
            Helper::saveToLog("duitku.log", "WARNING: Error getting country code - " . $e->getMessage());
            return '';
        }
    }

    /**
     * Get merchant user info from order
     * 
     * @param object $order The order object
     * @return string Username or email as merchant user info
     */
    public static function getMerchantUserInfo($order)
    {
        $merchantUserInfo = $order->email;

        if (!empty($order->user_id)) {
            try {
                $db = Factory::getDbo();
                $query = "SELECT `username` FROM `#__users` WHERE `id` = " . (int)$order->user_id;
                $db->setQuery($query);
                $username = $db->loadResult();
                if ($username) {
                    $merchantUserInfo = $username;
                }
            } catch (Exception $e) {
                Helper::saveToLog("duitku.log", "WARNING: Error getting username, using email - " . $e->getMessage());
            }
        }

        return $merchantUserInfo;
    }

    /**
     * Get callback base URL for payment notifications
     * 
     * @param array $pmconfigs Payment method configuration
     * @return string Base URL for callbacks
     */
    public static function getCallbackBaseUrl($pmconfigs)
    {
        $uri = \Joomla\CMS\Uri\Uri::getInstance();
        $scheme = $uri->toString(['scheme']);
        $host = $uri->toString(['host', 'port']);
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

        if ($basePath === '/') $basePath = '';

        $callbackBaseUrl = $scheme . '://' . $host . $basePath;

        // Development URL
        $environment = isset($pmconfigs['environment']) ? $pmconfigs['environment'] : 'sandbox';
        if ($environment === 'sandbox' && !empty($pmconfigs['devUrl'])) {
            $callbackBaseUrl = rtrim($pmconfigs['devUrl'], '/');
        }

        Helper::saveToLog("duitku.log", "INFO: Callback Base URL: " . $callbackBaseUrl . " (Environment: " . $environment . ")");
        return $callbackBaseUrl;
    }
}
