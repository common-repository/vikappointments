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

JHtml::fetch('formbehavior.chosen');
JHtml::fetch('vaphtml.assets.contextmenu');
JHtml::fetch('vaphtml.assets.toast', 'bottom-right');

?>

<style>
	.vap-overrides-manager {
		display: flex;
		background: #fff;
		border: 1px solid #ccc;
		min-height: 500px;
	}
	.vap-overrides-manager .overrides-navigator {
		width: 250px;
		max-width: 250px;
		overflow-x: scroll;
		border-right: 1px solid #ccc;
		padding: 10px;
	}
	.vap-overrides-manager .overrides-body {
		flex: 1;
	}

	.vap-overrides-manager .overrides-navigator ul {
		padding: 0 0 0 15px;
		margin: 0 0 0 5px;
		border-left: 1px solid rgba(0,0,0,.2);
	}
	.vap-overrides-manager .overrides-navigator ul li {
		margin: 4px 0 0 0;
		position: relative;
	}
	.vap-overrides-manager .overrides-navigator ul li:before {
		position: absolute;
		top: 9px;
		left: -15px;
		width: 10px;
		height: 1px;
		margin: auto;
		content: "";
		background-color: rgba(0,0,0,.2);
	}
	.vap-overrides-manager .overrides-navigator li ul {
		margin-left: 5px;
	}
	.vap-overrides-manager .overrides-navigator > a:not(:first-of-type) {
		margin-top: 4px;
	}
	.vap-overrides-manager .overrides-navigator a {
		display: flex;
		align-items: center;
	}
	.vap-overrides-manager .overrides-navigator a.folder i {
		width: 18px;
	}
	.vap-overrides-manager .overrides-navigator a.file i {
		margin-right: 4px;
	}
	.vap-overrides-manager .overrides-navigator a i.has-override {
		color: #060;
	}
	.vap-overrides-manager .overrides-navigator a i.has-override.unpublished {
		color: #900;
	}

	.vap-overrides-manager .overrides-body .overrides-guide {
		padding: 0 10px;
	}
</style>

<form action="admin.php?page=vikappointments&view=overrides" method="post" name="adminForm" id="adminForm">

	<!-- filters -->

	<div class="btn-toolbar hidden-phone" id="vap-search-tools" style="height: 32px;">

		<div class="btn-group pull-left">
			<select name="client" id="vap-client-sel" class="active" onchange="document.adminForm.submit();">
				<?php
				$options = [
					JHtml::fetch('select.option',          'site', __('Site Pages', 'vikappointments')),
					JHtml::fetch('select.option', 'administrator', __('Admin Pages', 'vikappointments')),
					JHtml::fetch('select.option',       'widgets', __('Widgets', 'vikappointments')),
					JHtml::fetch('select.option',       'layouts', __('Layouts', 'vikappointments')),
				];

				echo JHtml::fetch('select.options', $options, 'value', 'text', $this->filters['client']);
				?>
			</select>
		</div>

		<div class="btn-group pull-left">
			<select name="status" id="vap-status-sel" class="<?php echo $this->filters['status'] !== '' ? 'active' : ''; ?>" onchange="document.adminForm.submit();">
				<?php
				$options = [
					JHtml::fetch('select.option', '', __('- Select Status -', 'vikappointments')),
					JHtml::fetch('select.option',  1, __('Active', 'vikappointments')),
					JHtml::fetch('select.option',  0, __('Inactive', 'vikappointments')),
				];

				echo JHtml::fetch('select.options', $options, 'value', 'text', $this->filters['status'], true);
				?>
			</select>
		</div>

	</div>

	<?php echo JHtml::fetch('form.token'); ?>

	<input type="hidden" name="selectedfile" value="<?php echo $this->escape($this->filters['file']); ?>" />
	<input type="hidden" name="overridefile" value="<?php echo $this->escape(base64_encode($this->filters['override'])); ?>" />

	<input type="hidden" name="option" value="com_vikappointments" />
	<input type="hidden" name="view" value="overrides" />
	<input type="hidden" name="task" value="" />

</form>

<!-- body -->

<div class="vap-overrides-manager">

	<!-- navigator -->

	<div class="overrides-navigator">
		<?php
		if ($this->tree)
		{
			foreach ($this->tree as $node)
			{
				echo $this->buildNode($node);
			}
		}
		else
		{
			?><div style="text-align: center; margin-top: 6px;"><?php echo JText::translate('JGLOBAL_NO_MATCHING_RESULTS'); ?></div><?php
		}
		?>
	</div>

	<!-- editor -->

	<div class="overrides-body">
		<?php
		if ($this->filters['file'])
		{
			// display editor
			echo $this->loadTemplate('editor');
		}
		else
		{
			// display a short guide
			?>
			<div class="overrides-guide">
				<?php echo $this->loadTemplate('guide'); ?>
			</div>
			<?php
		}
		?>
	</div>

</div>

<script>
	(function($) {
		'use strict';

		VikRenderer.chosen('.btn-toolbar');

		// handle folders click
		$('.overrides-navigator a.folder').on('click', function() {
			// get UL next to button
			const ul = $(this).next('ul');

			if (ul.is(':visible')) {
				// hide list
				ul.hide();
				// back to the closed folder icon
				$(this).find('i').attr('class', 'fas fa-folder');
			} else {
				// show list
				ul.show();
				// set open folder icon
				$(this).find('i').attr('class', 'fas fa-folder-open');
			}
		});

		// handle files click
		$('.overrides-navigator a.file').on('click', function() {
			// register the paths of the selected file within the form
			document.adminForm.selectedfile.value = $(this).data('path');
			document.adminForm.overridefile.value = $(this).data('override');
			// submit the form
			document.adminForm.submit();
		});
	})(jQuery);
</script>