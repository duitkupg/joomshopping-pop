<?php

/**
 * Duitku System Plugin for JShopping Installation
 * 
 * This is a minimal Joomla system plugin that enables SQL execution during
 * the installation process of the Duitku payment plugin. It allows the
 * install script to create payment methods in the JShopping database.
 * 
 * Without this plugin, Joomla's security restrictions would prevent SQL
 * execution during extension installation, which is required for automatic
 * payment method creation.
 * 
 * @package    Duitku Payment Plugin
 * @author     Duitku Payment Gateway
 * @version    1.0.0
 * @since      1.0.0
 */

defined('_JEXEC') or die;

/**
 * Duitku System Plugin Class
 * 
 * This class extends JPlugin to provide a system plugin that enables
 * SQL execution during the installation process.
 */
class PlgSystemDuitku extends JPlugin
{
    /**
     * This plugin just exists to enable SQL execution during installation.
     * No additional functionality is required as the plugin is only used
     * to bypass Joomla's security restrictions for database operations
     * during the installation of the Duitku payment method.
     */
}
