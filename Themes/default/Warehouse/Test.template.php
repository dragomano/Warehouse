<?php

function template_test_area_above()
{
	global $txt, $context;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span>', $txt['warehouse_buttons'][2], '</span>
			<a class="button floatright" href="', WH_BASE_URL, '" style="color: initial">', $txt['warehouse_back_to_storage'], '</a>';

	if (!empty($context['box_list']['rows']))
		echo '
			<a class="button floatright you_sure" data-confirm="', $txt['warehouse_are_you_sure'], '" href="', $context['canonical_url'], ';run=approve_all" style="color: green; margin-right: 10px">', $txt['approve_all'], '</a>';

	echo '
		</h3>
	</div>';
}

function template_test_area_below() {}
