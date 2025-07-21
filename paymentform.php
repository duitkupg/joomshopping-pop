<?php

/**
 * Duitku Payment Form
 * 
 * This file provides the client-side JavaScript function for the Duitku payment form.
 * Since Duitku is a redirect payment gateway, this form is minimal and just triggers
 * the payment submission which will redirect the user to Duitku's payment page.
 * 
 * @package    Duitku Payment Plugin
 * @author     Duitku Payment Gateway
 * @version    1.0.0
 * @since      1.0.0
 */
defined('_JEXEC') or die('Restricted access');
?>
<script type="text/javascript">
    /**
     * Submit the Duitku payment form
     * 
     * This function is called when the user clicks the payment button.
     * It submits the payment form which triggers the redirect to Duitku's payment page.
     */
    function check_pm_duitku() {
        jQuery('#payment_form').submit();
    }
</script>