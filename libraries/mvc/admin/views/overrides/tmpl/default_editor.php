<?php
/** 
 * @package     VikAppointments
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$vik = VAPApplication::getInstance();

?>

<style>
	.vap-overrides-manager .overrides-body .override-file {
		display: flex;
		align-items: center;
		padding: 10px;
		border-bottom: 1px solid #ccc;
	}
	.vap-overrides-manager .overrides-body .override-file #override-options {
		font-size: 16px;
		margin-right: 10px;
	}
	.vap-overrides-manager .overrides-body .override-file input {
		flex: 1;
		margin-right: 4px;
	}

	.vap-overrides-manager .overrides-body .overrides-differences {
		border-bottom: 1px solid #ccc;
	}
	.vap-overrides-manager .overrides-body .overrides-differences .notice {
		margin: 10px;
	}

	.vap-overrides-manager .overrides-body .overrides-editor[data-layout="columns"] {
		display: flex;
		flex-wrap: wrap;
	}
	.vap-overrides-manager .overrides-body .overrides-editor[data-layout="columns"] > * {
		width: 50%;
		box-sizing: border-box;
	}
	.vap-overrides-manager .overrides-body .overrides-editor[data-layout="columns"] > *:only-of-type {
		width: 100%;
	}

	.vap-overrides-manager .overrides-body .overrides-editor .overrides-editor-default {
		visibility: hidden;
		position: fixed;
		transform: translateX(200%);
	}
	.vap-overrides-manager .overrides-body .overrides-editor[data-layout="columns"] .overrides-editor-default,
	.vap-overrides-manager .overrides-body .overrides-editor[data-layout="rows"] .overrides-editor-default {
		visibility: visible;
		position: initial;
		transform: none;
	}
	.vap-overrides-manager .overrides-body .overrides-editor[data-layout="columns"] .overrides-editor-default {
		border-left: 1px solid #ccc;
	}
	.vap-overrides-manager .overrides-body .overrides-editor[data-layout="rows"] .overrides-editor-default {
		border-top: 1px solid #ccc;
	}

	.vap-overrides-manager .overrides-body .CodeMirror {
		height: 100%;
	}
</style>

<!-- Toolbar -->

<div class="override-file">
	<i class="fas fa-cog" id="override-options"></i>

	<input type="text" readonly value="<?php echo $this->escape($this->filters['override']); ?>" />
	
	<button type="button" class="button" id="override-copy"><?php echo __('Copy'); ?></button>
</div>

<!-- Differences -->

<div class="overrides-differences" style="display: none;">
	<?php
	if (empty($this->filters['differences']))
	{
		echo $vik->alert(__('The are no differences among the override and the original file.', 'vikappointments'));
	}
	else
	{
		echo $this->loadTemplate('diff');
	}
	?>
</div>

<!-- Editor box -->

<div class="overrides-editor">

	<div class="overrides-editor-code">
		<?php
		// Display code mirror editor.
		// Take the editor outside the form in order to
		// prevent XSS blocks handled by the browser.
		echo $vik->getCodeMirror('__code', $this->filters['code']);
		?>
	</div>
	
	<div class="overrides-editor-default">
		<?php
		// Display code mirror editor.
		// Take the editor outside the form in order to
		// prevent XSS blocks handled by the browser.
		echo $vik->getCodeMirror('__default', $this->filters['defaultcode']);
		?>
	</div>

</div>

<?php
JText::script('VAPCONNECTIONLOSTERROR');
JText::script('JLIB_APPLICATION_SAVE_SUCCESS');
JText::script('VAPCONFDIALOGMSG');
?>

<script>
	(function($) {
		'use strict';

		$(function() {
			// mark default editor as readonly
			Joomla.editors.instances.__default.element.codemirror.setOption('readOnly', true);

			let hasOverride = <?php echo $this->filters['hasoverride'] ? 'true' : 'false'; ?>;
			let isPublished = <?php echo $this->filters['published'] ? 'true' : 'false'; ?>;

			// implement copy callback
			$('#override-copy').on('click', function() {
				// copy path within the clipboard
				copyToClipboard($(this).prev('input')).then(() => {
					// display successful message on copy
					VAPToast.dispatch(new VAPToastMessageOS({
						body: '<?php echo addslashes(__('Copied!')); ?>',
						status: VAPToast.SUCCESS_STATUS,
						delay: 'auto',
					}));
				}).catch(() => {
					// cannot copy
					VAPToast.dispatch(new VAPToastMessageOS({
						body: '<?php echo addslashes(__('Cannot copy the files! Please proceed manually.')); ?>',
						status: VAPToast.ERROR_STATUS,
						delay: 'auto',
					}));
				});
			});

			let isSaving = false;

			// override click of toolbar button
			Joomla.submitbutton = (task) => {
				if (task == 'override.save') {
					if (isSaving) {
						// already saving, do not go ahead
						return false;
					}

					isSaving = true;

					// extract code from editor
					let code = Joomla.editors.instances.__code.getValue();

					// build post data object
					const postData = {
						code: code,
						client: '<?php echo addslashes($this->filters['client']); ?>',
						selectedfile: '<?php echo addslashes($this->filters['file']); ?>',
						overridefile: '<?php echo base64_encode($this->filters['override']); ?>',
						status: isPublished ? 1 : 0,
					};

					// save code
					UIAjax.do(
						'admin-ajax.php?action=vikappointments&task=override.save',
						postData,
						(resp) => {
							// display successful message
							VAPToast.dispatch(new VAPToastMessageOS({
								body: Joomla.JText._('JLIB_APPLICATION_SAVE_SUCCESS'),
								status: VAPToast.SUCCESS_STATUS,
								delay: 'auto',
							}));

							if (!hasOverride) {
								// add override style to file link
								$('a.file[data-path="' + postData.selectedfile + '"]')
									.find('i').addClass('has-override');

								// we now have an active override
								hasOverride = isPublished = true;
							}

							// no more pending changes after save
							SOMETHING_HAS_CHANGED = false;

							isSaving = false;
						},
						(error) => {
							// prompt error message
							VAPToast.dispatch(new VAPToastMessageOS({
								body: error.responseText || Joomla.JText._('VAPCONNECTIONLOSTERROR'),
								status: VAPToast.ERROR_STATUS,
							}));

							isSaving = false;
						}
					);
				} else {
					// submit form
					Joomla.submitform(task, document.adminForm);
				}
			}

			let layoutMode;

			// retrieve current layout mode from local storage, if suppoered
			if (typeof Storage !== 'undefined') {
				layoutMode = localStorage.getItem('overrideLayoutMode');

				// init layout data
				$('.overrides-editor').attr('data-layout', layoutMode);
			}

			// layout mode button click implementor
			const saveOverrideLayoutMode = function(root, event) {
				// register layout mode
				layoutMode = this.layoutMode;

				// register layout within the local storage, if supported
				if (typeof Storage !== 'undefined') {
					localStorage.setItem('overrideLayoutMode', layoutMode);
				}

				// apply layout data
				$('.overrides-editor').attr('data-layout', layoutMode);
			};

			// check whether the current button is selected
			const isLayoutModeSelected = function(root, event) {
				if (this.layoutMode == layoutMode || (!this.layoutMode && !layoutMode)) {
					return 'fas fa-check';
				}

				return '';
			};

			let showDifferences = 0

			if (typeof Storage !== 'undefined') {
				showDifferences = parseInt(localStorage.getItem('overrideShowDifferences')) || 0;
			}

			if (showDifferences) {
				$('.overrides-differences').show();
			} else {
				$('.overrides-differences').hide();
			}

			// set up context menu for layout handling
			$('#override-options').vikContextMenu({
				clickable: true,
				buttons: [
					// HIDE NAVIGATOR
					{
						state: 1,
						text: '<?php echo addslashes(__('Show files tree', 'vikappointments')); ?>',
						action: function(root, event) {
							// get opposite state
							this.state ^= 1;

							if (this.state) {
								$('.overrides-navigator').show();
							} else {
								$('.overrides-navigator').hide();
							}
						},
						icon: function(root, event) {
							if (this.state) {
								return 'fas fa-check';
							}

							return '';
						},
					},
					// HIDE NAVIGATOR
					{
						state: showDifferences,
						text: '<?php echo addslashes(__('Show differences', 'vikappointments')); ?>',
						separator: true,
						action: function(root, event) {
							// get opposite state
							this.state ^= 1;

							if (this.state) {
								$('.overrides-differences').show();
							} else {
								$('.overrides-differences').hide();
							}

							if (typeof Storage !== 'undefined') {
								localStorage.setItem('overrideShowDifferences', this.state);
							}
						},
						icon: function(root, event) {
							if (this.state) {
								return 'fas fa-check';
							}

							return '';
						},
					},
					// HIDE ORIGINAL CODE
					{
						text: '<?php echo addslashes(__('Hide original file', 'vikappointments')); ?>',
						action: saveOverrideLayoutMode,
						icon: isLayoutModeSelected,
						layoutMode: '',
					},
					// SHOW IN COLUMNS
					{
						text: '<?php echo addslashes(__('Show both in columns', 'vikappointments')); ?>',
						action: saveOverrideLayoutMode,
						icon: isLayoutModeSelected,
						layoutMode: 'columns',
					},
					// SHOW IN ROWS
					{
						text: '<?php echo addslashes(__('Show one over the other', 'vikappointments')); ?>',
						action: saveOverrideLayoutMode,
						icon: isLayoutModeSelected,
						layoutMode: 'rows',
						separator: true,
					},
					// ACTIVATE
					{
						text: '<?php echo addslashes(__('Activate')); ?>',
						class: 'success',
						separator: true,
						action: (root, event) => {
							// submit form to publish override
							Joomla.submitbutton('override.publish');
						},
						visible: (root, event) => {
							// display button only in case of deactivated override
							return hasOverride === true && isPublished === false;
						},
					},
					// DEACTIVATE
					{
						text: '<?php echo addslashes(__('Deactivate')); ?>',
						class: 'warning',
						separator: true,
						action: (root, event) => {
							// submit form to publish override
							Joomla.submitbutton('override.unpublish');
						},
						disabled: (root, event) => {
							// disable button in case of missing override
							return hasOverride === false;
						},
						visible: (root, event) => {
							// display button only in case of activated override
							return hasOverride === true && isPublished === true;
						},
					},
					// SAVE
					{
						text: '<?php echo addslashes(__('Save')); ?>',
						action: (root, event) => {
							Joomla.submitbutton('override.save');
						},
						// Always keep button hidden. Declare it just
						// to implement the CMD+S shortcut for saving.
						visible: false,
						shortcut: (() => {
							 if (window.navigator.platform.match(/^MAC/i)) {
						        // Mac OS
						        return ['meta', 's'];
						    }
						    // Windows
						    return ['ctrl', 's'];

						})(),
					},
					// DELETE
					{
						icon: 'fas fa-times',
						text: '<?php echo addslashes(__('Delete')); ?>',
						class: 'danger',
						action: (root, event) => {
							// dispatch confirmation and delete in a thread so that
							// we can complete the closure of the context menu
							setTimeout(() => {
								// ask for a confirmation
								const r = confirm(Joomla.JText._('VAPCONFDIALOGMSG'));

								// unset flag because we don't care of any pending
								// changes in case we are going to delete the file
								SOMETHING_HAS_CHANGED = false;

								if (r) {
									// delete override
									Joomla.submitbutton('override.delete');
								}
							}, 32);
						},
						disabled: (root, event) => {
							// disable button in case of missing override
							return hasOverride === false;
						},
					},
				],
			});

			// flag used to check whether the code has changed
			let SOMETHING_HAS_CHANGED = false;

			Joomla.editors.instances.__code.element.codemirror.on('change', () => {
				// the user change the content of the editor, register flag
				SOMETHING_HAS_CHANGED = true;
			});

			// register event to block the user while attempting
			// to leave the page with pending changes
			$(window).on('beforeunload', (event) => {
				// check whether something has changed
				if (SOMETHING_HAS_CHANGED) {
					// The translated message is meant to work only
					// for internal purposes as almost all the browsers 
					// uses their own localised strings.
					const dialogText = 'Do you want to leave the page? Your changes will be lost if you don\'t save them.';

					event.returnValue = dialogText;

					// return the message to trigger the browser prompt
					return dialogText;
				}
			});
		});
	})(jQuery);
</script>