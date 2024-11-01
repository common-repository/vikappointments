<?php
/**
 * @package     VikAppointments
 * @subpackage  mod_vikappointments_currencyconverter
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

$itemid = $params->get('itemid');

?>

<form action="<?php echo JRoute::rewrite('index.php?option=com_vikappointments&task=modules.changecurrency' . ($itemid ? '&Itemid=' . $itemid : '')); ?>" method="post">
    
    <select name="currency" id="vap-curr-conv-select<?php echo $module_id; ?>">
        <?php echo JHtml::fetch('select.options', $currencies, 'value', 'text', $userCurrency); ?>
    </select>
    
    <input type="hidden" name="return" value="<?php echo base64_encode(JUri::getInstance()); ?>" />
    
    <?php echo JHtml::fetch('form.token'); ?>

</form>

<script>
    (function($) {
        'use strict';

        $(function() {
            const dropdown = $('#vap-curr-conv-select<?php echo $module_id; ?>');

            dropdown.on('change', () => {
                dropdown.closest('form').submit();
            });

            dropdown.select2({
                allowClear: false,
                minimumResultsForSearch: -1,
                width: 'auto',
            });
        });
    })(jQuery);
</script>