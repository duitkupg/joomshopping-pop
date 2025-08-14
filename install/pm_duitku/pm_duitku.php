<?php

use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\CMS\Factory;
use Joomla\Component\Jshopping\Site\Helper\Helper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');
require_once(dirname(__FILE__) . "/duitku-php/Duitku.php");

class pm_duitku extends PaymentRoot
{

    function showPaymentForm($params, $pmconfigs)
    {
        include(dirname(__FILE__) . "/paymentform.php");
    }

    function showAdminFormParams($params)
    {
        $array_params = array('merchantCode', 'apiKey', 'environment', 'paymentMethod', 'transaction_end_status', 'transaction_failed_status');

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

    function checkTransaction($pmconfigs, $order, $act)
    {
        Helper::saveToLog("duitku.log", "INFO: Checking transaction for Order ID: " . $order->order_id);

        try {
            $notification = new DuitkuNotification();
            $merchantCode = $pmconfigs['merchantCode'];
            $apiKey = $pmconfigs['apiKey'];

            if (!$notification->validateSignature($merchantCode, $apiKey)) {
                Helper::saveToLog("duitku.log", "ERROR: Signature validation failed for Order ID: " . $order->order_id);
                return FALSE;
            }

            if ($order) {
                if ($notification->isSuccess()) {
                    Helper::saveToLog("duitku.log", "INFO: Payment SUCCESS for order ID: " . $order->order_id);
                    Helper::saveToLog("duitku_callback.log", "INFO: Callback Body: " . print_r($notification->toArray(), true));
                    return array(1, 'Payment Successful', $notification->reference);
                } elseif ($notification->isFailed()) {
                    Helper::saveToLog("duitku.log", "WARNING: Payment FAILED for order ID: " . $order->order_id . " - Code: " . $notification->resultCode);
                    return array(0, 'Payment failed with code: ' . $notification->resultCode);
                } else {
                    Helper::saveToLog("duitku.log", "INFO: Payment PENDING for order ID: " . $order->order_id . " - Code: " . $notification->resultCode);
                    return array(2, 'Payment pending with code: ' . $notification->resultCode);
                }
            } else {
                Helper::saveToLog("duitku.log", "WARNING: Order object is NULL");
                return FALSE;
            }
        } catch (Exception $e) {
            Helper::saveToLog("duitku.log", "ERROR: Check Transaction for order ID " . $order->order_id . " failed - " . $e->getMessage());
            return FALSE;
        }
    }

    function showEndForm($pmconfigs, $order)
    {
        Helper::saveToLog("duitku.log", "INFO: Processing transaction with order number " . $order->order_number) . "...";

        $orderId = $order->order_id;
        $baseUrl = DuitkuHelper::getBaseUrl($pmconfigs);
        $jsPaymentClass = $this->getPmMethod()->payment_class;

        $params = array(
            'paymentAmount' => (int)round($this->fixOrderTotal($order)),
            'merchantOrderId' => $order->order_number,
            'productDetails' => sprintf(Text::_('JSHOP_PAYMENT_NUMBER'), $order->order_number),
            'merchantUserInfo' => $order->email,
            'customerDetail' => DuitkuHelper::buildCustomerDetail($order),
            'email' => $order->email,
            'phoneNumber' => $order->phone ?? '',
            'callbackUrl' => DuitkuHelper::getSEFLink('notify', $baseUrl, $orderId, $jsPaymentClass),
            'returnUrl' => DuitkuHelper::getSEFLink('return', $baseUrl, $orderId, $jsPaymentClass)
        );

        try {
            $headers = DuitkuHeader::generate($pmconfigs['merchantCode'], $pmconfigs['apiKey']);
            $apiUrl = DuitkuConfig::getUrl($pmconfigs['environment']);
            
            Helper::saveToLog("duitku.log", "INFO: REQUEST PARAMETERS");
            Helper::saveToLog("duitku.log", "INFO: Request Headers: " . print_r($headers, true));
            Helper::saveToLog("duitku.log", "INFO: Request Body: " . print_r($params, true));
            Helper::saveToLog("duitku.log", "INFO: Sending request to Duitku POP API...");
            
            $result = DuitkuPop::createInvoice($apiUrl, $params, $headers);
            Helper::saveToLog("duitku.log", "INFO: Response Body: " . print_r($result, true));
            header("Location: " . $result->paymentUrl);
            exit();
        } catch (Exception $e) {
            Helper::saveToLog("duitku.log", "ERROR: Request failed - " . $e->getMessage());
            echo "Payment processing error: " . $e->getMessage();
            return;
        }
    }

    function getUrlParams($pmconfigs)
    {
        $input = Factory::getApplication()->input;
        $params = array();
        $params['order_id'] = $input->getInt("custom");
        $params['merchantOrderId'] = $input->getString("merchantOrderId");
        $params['reference'] = $input->getString("reference");
        $params['resultCode'] = $input->getInt("resultCode");

        if ($input->getString("act") === "return") {
            Helper::saveToLog("duitku.log", "INFO: Return URL parameter (from param query): " . print_r($params, true));
        } elseif ($input->getString("act") === "notify") {
            $params['signature'] = $input->getString("signature");
            Helper::saveToLog("duitku.log", "INFO: Callback URL parameter: " . print_r($params, true));
        }


        return $params;
    }

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
