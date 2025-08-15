<?php

use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die('Restricted access');
?>
<div class="col100">
  <fieldset class="adminform">
    <table class="admintable" width="100%">
      <tr>
        <td style="width:250px;" class="key">
          <?php echo 'Merchant Code'; ?>
        </td>
        <td>
          <input type="text" class="inputbox form-control" name="pm_params[merchantCode]" size="45" value="<?php echo htmlspecialchars($params['merchantCode']); ?>" />
        </td>
      </tr>
      <tr>
        <td class="key">
          <?php echo 'API Key'; ?>
        </td>
        <td>
          <input type="password" class="inputbox form-control" name="pm_params[apiKey]" size="45" value="<?php echo htmlspecialchars($params['apiKey']); ?>" />
        </td>
      </tr>
      <tr>
        <td class="key">
          <?php echo 'Environment'; ?>
        </td>
        <td>
          <select name="pm_params[environment]" class="inputbox custom-select">
            <option value="sandbox" <?php if (isset($params['environment']) && $params['environment'] == 'sandbox') echo "selected";
                                    elseif (!isset($params['environment'])) echo "selected"; ?>>Sandbox</option>
            <option value="production" <?php if (isset($params['environment']) && $params['environment'] == 'production') echo "selected"; ?>>Production</option>
          </select>
        </td>
      </tr>
      <tr>
        <td class="key">
          <?php echo Text::_('JSHOP_TRANSACTION_END'); ?>
        </td>
        <td>
          <?php
          echo HTMLHelper::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_end_status]', 'class="inputbox custom-select" size="1"', 'status_id', 'name', $params['transaction_end_status']);
          ?>
        </td>
      </tr>
      <tr>
        <td class="key">
          <?php echo Text::_('JSHOP_TRANSACTION_FAILED'); ?>
        </td>
        <td>
          <?php
          echo HTMLHelper::_('select.genericlist', $orders->getAllOrderStatus(), 'pm_params[transaction_failed_status]', 'class="inputbox custom-select" size="1"', 'status_id', 'name', $params['transaction_failed_status']);
          ?>
        </td>
      </tr>
    </table>
  </fieldset>
</div>
<div class="clr"></div>