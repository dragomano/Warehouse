<?php

function template_user_area_above()
{
	global $txt;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span>', $txt['warehouse_buttons'][1], '</span>
			<a class="button floatright" href="', WH_BASE_URL, '" style="color: initial">', $txt['warehouse_back_to_storage'], '</a>
		</h3>
	</div>';
}

function template_user_area_below() {}
