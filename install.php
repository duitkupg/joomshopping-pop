<?php
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;

function my_copy_all($from, $to, $rewrite = true)
{
    if (is_dir($from)) {
        if (!file_exists($to)) {
            @mkdir($to, 0755, true);
        }
        $d = dir($from);
        while (false !== ($entry = $d->read())) {
            if ($entry == "." || $entry == "..") continue;
            my_copy_all("$from/$entry", "$to/$entry", $rewrite);
        }
        $d->close();
    } else {
        if (!file_exists($to) || $rewrite) {
            copy($from, $to);
        }
    }
}

$app = Factory::getApplication();
$currentDir = dirname(__FILE__);
$old_dir = $currentDir . '/install/pm_duitku/';
$new_dir = $_SERVER['DOCUMENT_ROOT'] . '/components/com_jshopping/payments/pm_duitku/';

if (is_dir($old_dir)) {
    my_copy_all($old_dir, $new_dir, false);
    if (is_dir($new_dir)) {
        try {
            $paymentTable = JSFactory::getTable('paymentMethod');
            $paymentTable->loadFromClass('pm_duitku'); // IGNORE (Red underline is linter error)
            if ($paymentTable->payment_id) {
                $app->enqueueMessage('Duitku payment method already exists!', 'warning');
            } else {
                $paymentTable->payment_code = 'duitku';
                $paymentTable->payment_class = 'pm_duitku';
                $paymentTable->scriptname = 'pm_duitku';
                $paymentTable->payment_publish = 0;
                $paymentTable->payment_ordering = 1;
                $paymentTable->payment_type = 2;
                $paymentTable->price = 0.00;
                $paymentTable->price_type = 1;
                $paymentTable->tax_id = 1;
                $paymentTable->show_descr_in_email = 0;
                $paymentTable->{'name_en-GB'} = 'Duitku';
                $paymentTable->{'name_de-DE'} = 'Duitku';

                if ($paymentTable->store()) {
                    $configs = [
                        'merchantCode' => '',
                        'apiKey' => '',
                        'environment' => 'sandbox',
                        'transaction_end_status' => '6',
                        'transaction_failed_status' => '1'
                    ];

                    $paymentTable->setConfigs($configs); // IGNORE (Red underline is linter error)
                    $paymentTable->store();
                    $app->enqueueMessage('Duitku auto-install success.', 'success');
                } else {
                    $app->enqueueMessage('Duitku auto-install error!', 'error');
                }
            }
        } catch (Exception $e) {
            $app->enqueueMessage('Database error: ' . $e->getMessage(), 'error');
        }
    } else {
        $app->enqueueMessage('Copy operation failed - target directory not created!', 'error');
    }
} else {
    $app->enqueueMessage('Source directory does not exist: ' . $old_dir, 'error');
}