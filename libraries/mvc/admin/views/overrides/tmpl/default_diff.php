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

$diff = $this->filters['differences'];

$codes = $lines = [];

foreach ($diff as $index => $line)
{
	if ($index > 1 && !isset($diff[$index - 1]))
	{
		$codes[] = '';
		$codes[] = '...';
		$codes[] = '';

		$lines[] = '';
		$lines[] = '';
		$lines[] = '';
	}

	if ($line[1] === -1)
	{
		$code = '<span class="overrides-diff-line line-delete">';
	}
	else if ($line[1] === 1)
	{
		$code = '<span class="overrides-diff-line line-insert">';
	}
	else
	{
		$code = '<span class="overrides-diff-line line-keep">';
	}

	$codes[] = $code . htmlentities($line[0]) . "</span>";

	// register real line number
	$lines[] = $line[2];
}
?>

<style>
	.overrides-diff-wrapper {
		display: flex;
	}

	.overrides-diff-wrapper pre {
		margin: 0;
	}
	.overrides-diff-wrapper pre code {
		display: block;
	}
	.overrides-diff-wrapper pre code:last-of-type {
		padding-bottom: 20px;
	}

	.overrides-diff-numbers {
		border-right: 1px solid #ccc;
	}
	.overrides-diff-numbers code {
		background: rgba(0, 0, 0, 0.04);
	}

	.overrides-diff-code-scrollable {
		flex: 1;
		overflow-x: scroll;
		width: 0;
	}
	pre.overrides-diff-code {
		word-break: keep-all !important;
		word-wrap: normal !important;
		white-space: pre !important;
	}
	.overrides-diff-code code {
		background: #fff;
	}

	.overrides-diff-line.line-delete {
		background: #9005;
	}
	.overrides-diff-line.line-insert {
		background: #0905;
	}
</style>

<div class="overrides-diff-wrapper">

	<pre class="overrides-diff-numbers"><code><?php echo implode("\n", $lines); ?></code></pre>

	<div class="overrides-diff-code-scrollable">
		<pre class="overrides-diff-code"><code><?php echo implode("\n", $codes); ?></code></pre>
	</div>

</div>