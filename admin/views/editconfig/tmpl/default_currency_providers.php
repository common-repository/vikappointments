<?php
/** 
 * @package     VikAppointments
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$params = $this->params;

$vik = VAPApplication::getInstance();

/**
 * Trigger event to display custom HTML.
 * In case it is needed to include any additional fields,
 * it is possible to create a plugin and attach it to an event
 * called "onDisplayViewConfigCurrencyConversion". The event method
 * receives the view instance as argument.
 *
 * @since 1.7.6
 */
$forms = $this->onDisplayView('CurrencyConversion');

?>

<!-- INSTRUCTIONS -->

<div class="config-fieldset">

    <div class="config-fieldset-head">
        <h3><?php echo JText::translate('VAPMANAGECONFIGCRON8'); ?></h3>
    </div>

    <div class="config-fieldset-body">
        <?php echo $vik->alert(JText::translate('VAPCONFIGCURRCONVERSION_DESC'), 'info'); ?>
    </div>

</div>

<!-- ECB -->

<div class="config-fieldset">

    <div class="config-fieldset-head">
        <h3><?php echo JText::translate('VAPCURRENCYCONVPROVIDER_ECB'); ?></h3>
    </div>

    <div class="config-fieldset-body">
        
        <!-- ENABLED - Checkbox -->

        <?php
        $yes = $vik->initRadioElement('', '',  $params['currency_ecb_enabled']);
        $no  = $vik->initRadioElement('', '', !$params['currency_ecb_enabled']);

        echo $vik->openControl(JText::translate('VAPWIZARDENABLE'));
        ?>
            <div style="display: flex; justify-content: space-between;">
                <?php echo $vik->radioYesNo('currency_ecb_enabled', $yes, $no); ?>
                <a href="https://www.ecb.europa.eu/stats/eurofxref" target="_blank" class="btn">
                    <i class="fas fa-external-link-alt"></i>&nbsp;
                    <?php echo JText::translate('VAPVISITSITE'); ?>
                </a>
            </div>
        <?php echo $vik->closeControl(); ?>

        <!-- INFO - Alert -->

        <?php echo $vik->alert(JText::translate('VAPCURRENCYCONVPROVIDER_ECB_DESC'), 'info'); ?>

    </div>
    
</div>

<!-- CURRENCY API -->

<div class="config-fieldset">

    <div class="config-fieldset-head">
        <h3><?php echo JText::translate('VAPCURRENCYCONVPROVIDER_CURRENCYAPI'); ?></h3>
    </div>

    <div class="config-fieldset-body">
        
        <!-- ENABLED - Checkbox -->

        <?php
        $yes = $vik->initRadioElement('', '',  $params['currency_currencyapi_enabled'], 'currencyApiValueChanged(1)');
        $no  = $vik->initRadioElement('', '', !$params['currency_currencyapi_enabled'], 'currencyApiValueChanged(0)');

        echo $vik->openControl(JText::translate('VAPWIZARDENABLE'));
        ?>
            <div style="display: flex; justify-content: space-between;">
                <?php echo $vik->radioYesNo('currency_currencyapi_enabled', $yes, $no); ?>
                <a href="https://currencyapi.com" target="_blank" class="btn">
                    <i class="fas fa-external-link-alt"></i>&nbsp;
                    <?php echo JText::translate('VAPVISITSITE'); ?>
                </a>
            </div>
        <?php echo $vik->closeControl(); ?>

        <!-- API KEY - Text -->
        
        <?php echo $vik->openControl(JText::translate('VAPCURRENCYCONVPROVIDER_CURRENCYAPI_KEY'), 'currencyapi-setting', ['style' => $params['currency_currencyapi_enabled'] ? '' : 'display:none;']); ?>
            <div class="input-append">
                <input type="text" name="currency_currencyapi_key" value="<?php echo $this->escape($params['currency_currencyapi_key']); ?>" <?php echo (strlen($params['currency_currencyapi_key']) ? 'readonly' : ''); ?> />
            
                <?php
                if (strlen($params['currency_currencyapi_key']))
                {
                    ?>
                    <button type="button" class="btn" onClick="lockUnlockInput(this);">
                        <i class="fas fa-lock"></i>
                    </button>
                    <?php
                }
                ?>
            </div>
        <?php echo $vik->closeControl(); ?>

        <!-- CACHE LIFETIME - Number -->
        
        <?php
        $help = $vik->createPopover([
            'title'   => JText::translate('VAPCURRENCYCONVPROVIDER_CURRENCYAPI_CACHE_LIFETIME'),
            'content' => JText::translate('VAPCURRENCYCONVPROVIDER_CURRENCYAPI_CACHE_LIFETIME_DESC'),
        ]);

        echo $vik->openControl(JText::translate('VAPCURRENCYCONVPROVIDER_CURRENCYAPI_CACHE_LIFETIME') . $help, 'currencyapi-setting', ['style' => $params['currency_currencyapi_enabled'] ? '' : 'display:none;']); ?>
            <div class="input-append">
                <input type="number" name="currency_currencyapi_cache" value="<?php echo (int) $params['currency_currencyapi_cache']; ?>" min="1" step="1" />
                <span class="btn"><?php echo JText::translate('VAPSHORTCUTMINUTE'); ?></span>
            </div>
        <?php echo $vik->closeControl(); ?>

        <!-- INFO - Alert -->

        <?php echo $vik->alert(JText::translate('VAPCURRENCYCONVPROVIDER_CURRENCYAPI_DESC'), 'info'); ?>

    </div>
    
</div>

<!-- FLOAT RATES -->

<div class="config-fieldset">

    <div class="config-fieldset-head">
        <h3><?php echo JText::translate('VAPCURRENCYCONVPROVIDER_FLOATRATES'); ?></h3>
    </div>

    <div class="config-fieldset-body">
        
        <!-- ENABLED - Checkbox -->

        <?php
        $yes = $vik->initRadioElement('', '',  $params['currency_floatrates_enabled']);
        $no  = $vik->initRadioElement('', '', !$params['currency_floatrates_enabled']);

        echo $vik->openControl(JText::translate('VAPWIZARDENABLE'));
        ?>
            <div style="display: flex; justify-content: space-between;">
                <?php echo $vik->radioYesNo('currency_floatrates_enabled', $yes, $no); ?>
                <a href="https://www.floatrates.com" target="_blank" class="btn">
                    <i class="fas fa-external-link-alt"></i>&nbsp;
                    <?php echo JText::translate('VAPVISITSITE'); ?>
                </a>
            </div>
        <?php echo $vik->closeControl(); ?>

        <!-- INFO - Alert -->

        <?php echo $vik->alert(JText::translate('VAPCURRENCYCONVPROVIDER_FLOATRATES_DESC'), 'info'); ?>

    </div>
    
</div>

<!-- Define role to detect the supported hook -->
<!-- {"rule":"customizer","event":"onDisplayViewConfigCurrencyConversion","type":"fieldset"} -->

<?php
/**
 * Iterate remaining forms to be displayed as new fieldsets
 * within the Currency > Conversion tab.
 *
 * @since 1.7.6
 */
foreach ($forms as $formTitle => $formHtml)
{
    ?>
    <div class="config-fieldset">
        
        <div class="config-fieldset-head">
            <h3><?php echo JText::translate($formTitle); ?></h3>
        </div>

        <div class="config-fieldset-body">
            <?php echo $formHtml; ?>
        </div>
        
    </div>
    <?php
}
?>

<script>
    (function($) {
        'use strict';

        window.currencyApiValueChanged = (is) => {
            if (is) {
                $('.currencyapi-setting').show();
            } else {
                $('.currencyapi-setting').hide();
            }
        }
    })(jQuery);
</script>