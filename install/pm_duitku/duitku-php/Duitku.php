<?php

/**
 * Duitku Payment Gateway PHP Library
 * 
 * This is the main entry point for the Duitku PHP SDK. It includes all
 * necessary components for integrating with the Duitku payment gateway.
 * 
 * @package    Duitku PHP SDK
 * @author     Duitku Payment Gateway
 * @version    1.0.0
 * @since      1.0.0
 */

// Check PHP version requirement
if (version_compare(PHP_VERSION, '5.2.1', '<')) {
  throw new Exception('PHP version >= 5.2.1 required');
}

// Check required PHP extensions
if (!function_exists('curl_init')) {
  throw new Exception('Duitku needs the CURL PHP extension.');
}
if (!function_exists('json_decode')) {
  throw new Exception('Duitku needs the JSON PHP extension.');
}

// Load required Duitku classes
require_once('Duitku/ApiRequestor.php');
require_once('Duitku/Config.php');
require_once('Duitku/DuitkuPop.php');
require_once('Duitku/Header.php');
require_once('Duitku/Helper.php');
require_once('Duitku/Notification.php');