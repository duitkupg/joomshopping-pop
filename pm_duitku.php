<?php

use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\CMS\Factory;
use Joomla\Component\Jshopping\Site\Helper\Helper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');

if (!class_exists('Duitku_Notification')) {
    require(dirname(__FILE__) . '/duitku-php/Duitku.php');
}

if (!class_exists('DuitkuConfig')) {
    require(dirname(__FILE__) . '/duitku-php/Duitku/Config.php');
}

if (!class_exists('DuitkuHelper')) {
    require(dirname(__FILE__) . '/duitku-php/Duitku/Helper.php');
}

/**
 * Duitku Payment Plugin for JShopping
 * 
 * This class handles the Duitku payment integration for JShopping e-commerce platform.
 * It provides methods for payment form display, transaction checking, and order processing.
 * 
 * @package    Duitku Payment Plugin
 * @author     Duitku Payment Gateway
 * @version    1.0.0
 * @since      1.0.0
 */
class pm_duitku extends PaymentRoot
{

    /**
     * Show payment form to the customer
     * 
     * @param array $params Payment parameters
     * @param array $pmconfigs Payment method configuration
     * @return void
     */
    function showPaymentForm($params, $pmconfigs)
    {
        include(dirname(__FILE__) . "/paymentform.php");
    }

    /**
     * Show admin form parameters for payment method configuration
     * 
     * @param array $params Current parameter values
     * @return void
     */
    function showAdminFormParams($params)
    {
        $array_params = array('merchantCode', 'apiKey', 'environment', 'paymentMethod', 'transaction_end_status', 'transaction_failed_status', 'devUrl');

        foreach ($array_params as $key) {
            if (!isset($params[$key])) $params[$key] = '';
        }

        if (!isset($params['environment']) || empty($params['environment'])) {
            $params['environment'] = 'sandbox';
        }
        if (!isset($params['address_override'])) $params['address_override'] = 0;

        $orders = JSFactory::getModel('orders'); // admin model
        include(dirname(__FILE__) . "/adminparamsform.php");
    }

    /**
     * Check transaction status from Duitku notification
     * 
     * @param array $pmconfigs Payment method configuration
     * @param object $order Order object
     * @param string $act Action type (notify, return, etc.)
     * @return array|bool Transaction status array [status, message, reference] or false on failure
     */
    function checkTransaction($pmconfigs, $order, $act)
    {
        Helper::saveToLog("duitku.log", "INFO: checkTransaction called - Act: $act, Order ID: " . ($order ? $order->order_id : 'null'));

        try {
            $notification = new Duitku_Notification();

            if (!$notification->validateSignature($pmconfigs['merchantCode'], $pmconfigs['apiKey'])) {
                Helper::saveToLog("duitku.log", "WARNING: Signature validation failed");
                return FALSE;
            }

            if (empty($notification->resultCode) || empty($notification->merchantOrderId)) {
                Helper::saveToLog("duitku.log", "WARNING: Missing required notification fields");
                return FALSE;
            }

            if ($order) {
                if ($notification->isSuccess()) {
                    Helper::saveToLog("duitku.log", "INFO: Payment SUCCESS for order " . $order->order_id);
                    return array(1, 'Payment Successful', $notification->reference);
                } elseif ($notification->isFailed()) {
                    Helper::saveToLog("duitku.log", "WARNING: Payment FAILED for order " . $order->order_id . " - Code: " . $notification->resultCode);
                    return array(0, 'Payment failed with code: ' . $notification->resultCode);
                } else {
                    Helper::saveToLog("duitku.log", "INFO: Payment PENDING for order " . $order->order_id . " - Code: " . $notification->resultCode);
                    return array(2, 'Payment pending with code: ' . $notification->resultCode);
                }
            } else {
                Helper::saveToLog("duitku.log", "WARNING: Order object is NULL");
                return FALSE;
            }
        } catch (Exception $e) {
            Helper::saveToLog("duitku.log", "ERROR: checkTransaction failed - " . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Process payment and redirect to Duitku payment page
     * 
     * @param array $pmconfigs Payment method configuration
     * @param object $order Order object
     * @return void
     */
    function showEndForm($pmconfigs, $order)
    {
        Helper::saveToLog("duitku.log", "INFO: showEndForm called - Order ID: " . $order->order_id . ", Order Number: " . $order->order_number);

        $pm_method = $this->getPmMethod();
        $amount = $this->fixOrderTotal($order);
        $orderId = $order->order_id;
        $item_name = sprintf(Text::_('JSHOP_PAYMENT_NUMBER'), $order->order_number);
        $callbackBaseUrl = DuitkuHelper::getCallbackBaseUrl($pmconfigs);
        $customerDetail = DuitkuHelper::buildCustomerDetail($order);
        $itemDetails = DuitkuHelper::buildItemDetails($order);
        $merchantUserInfo = DuitkuHelper::getMerchantUserInfo($order);

        $params = array(
            'paymentAmount' => (int)round($amount), // IDR doesn't use cents, send as whole number
            'merchantOrderId' => $order->order_number,
            'productDetails' => 'Order : ' . $order->order_number . ' - ' . $item_name,
            'merchantUserInfo' => $merchantUserInfo,
            'customerDetail' => $customerDetail,
            'itemDetails' => $itemDetails,
            'email' => $order->email,
            'phoneNumber' => $order->phone ?? '',
            'callbackUrl' => $callbackBaseUrl . "/components/com_jshopping/payments/pm_duitku/callback.php?js_paymentclass=" . $pm_method->payment_class . "&custom=" . $orderId,
            'returnUrl' => $callbackBaseUrl . Helper::SEFLink("/index.php?option=com_jshopping&controller=checkout&task=step7&act=return&custom=" . $orderId . "&js_paymentclass=" . $pm_method->payment_class)
        );

        try {
            $headers = DuitkuHelper::generateHeaders($pmconfigs['merchantCode'], $pmconfigs['apiKey']);
            $environment = isset($pmconfigs['environment']) ? DuitkuConfig::validateEnvironment($pmconfigs['environment']) : 'sandbox';
            $apiUrl = DuitkuConfig::getUrl($environment);
            Helper::saveToLog("duitku.log", "INFO: Using environment: " . $environment);

            $redirUrl = Duitku_POP::createInvoice($apiUrl, $params, $headers);
            Helper::saveToLog("duitku.log", "INFO: Redirect URL received, redirecting to payment page");

            header("Location: " . $redirUrl);
            exit();
        } catch (Exception $e) {
            Helper::saveToLog("duitku.log", "ERROR: Duitku POP API failed - " . $e->getMessage());
            echo "Payment processing error: " . $e->getMessage();
            return;
        }
    }

    /**
     * Get URL parameters from payment return/notification
     * 
     * @param array $pmconfigs Payment method configuration
     * @return array Array of URL parameters
     */
    function getUrlParams($pmconfigs)
    {
        $input = Factory::getApplication()->input;
        $params = array();
        $params['order_id'] = $input->getInt("custom");
        $params['hash'] = "";
        $params['checkHash'] = 0;
        $params['checkReturnParams'] = 0;

        Helper::saveToLog("duitku.log", "INFO: getUrlParams - Order ID: " . $params['order_id']);

        return $params;
    }

    /**
     * Fix order total formatting based on currency
     * 
     * @param object $order The order object
     * @return float|string Formatted order total
     */
    private function fixOrderTotal($order)
    {
        $total = $order->order_total;
        if ($order->currency_code_iso == 'HUF') {
            $total = round($total);
        } else {
            $total = number_format($total, 2, '.', '');
        }
        return $total;
    }
}
