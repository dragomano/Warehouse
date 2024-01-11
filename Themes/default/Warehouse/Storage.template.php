<?php

function template_storage()
{
	global $txt, $context, $scripturl;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span>', $txt['warehouse_title'], '</span>';

	if ($context['user']['is_admin']) {
		echo '
			<a class="button floatright" href="', $scripturl, '?action=admin;area=modsettings;sa=warehouse" style="color: crimson">', $txt['settings'], '</a>';
	}

	echo '
		</h3>
	</div>
	<p class="information">', $txt['warehouse_example_description'], '</p>';

	if (empty($context['warehouse_boxes'])) {
		if (empty($context['warehouse_racks'])) {
			echo '
	<div class="centertext">
		<div class="errorbox">', $txt['warehouse_required_racks'], '</div>
	</div>';

			show_wh_buttons();

			return;
		} else {
			echo '
	<div class="centertext">
		<div class="noticebox">', $txt['warehouse_is_empty'], '</div>
	</div>';
		}
	}

	show_wh_buttons();

	show_week_activity();

	show_staff_list();

	show_recent_and_top_boxes();

	show_racks();
}

function show_wh_buttons()
{
	global $context, $txt;

	echo '
	<div class="wh_buttons">';

	foreach ($context['wh_buttons'] as $button) {
		if ($button['show'] === false)
			continue;

		echo '
		<a class="button', $button['is_selected'] ? ' active' : '', '" href="', $button['href'], '">', $button['icon'], ' ', $button['title'], '</a>';
	}

	if ($context['user']['is_admin']) {
		echo '
		<a class="button" href="', WH_BASE_URL, ';sa=add_rack"><span class="main_icons plus"> ', $txt['warehouse_add_rack_button'], '</a>';
	}

	echo '
	</div>';
}

function show_week_activity()
{
	global $context, $txt;

	if (empty($context['wh_week_activity']))
		return;

	echo /** @lang text */ '
	<canvas id="wh_activity" style="height: 250px"></canvas>
	<script>
		new Chart("wh_activity", {
			type: "line",
			data: {
				labels: ["', $txt['warehouse_boxes'], '"],
				datasets: [{
					label: "', $txt['warehouse_activity_chart'], '",
					data: [', $context['wh_week_activity'], '],
					borderWidth: 1
				}]
			},
			options: {
				parsing: {
					xAxisKey: "name",
					yAxisKey: "value"
				}
			}
		})
	</script>';
}

function show_staff_list()
{
	global $context, $txt;

	if (empty($context['wh_today_staff']))
		return;

	echo '
		<table class="table_grid centertext">
			<thead>
				<tr class="title_bar">
					<th colspan="2">', $txt['who_member'], '</th>
					<th>', $txt['warehouse_role'], '</th>
				</tr>
			</thead>
			<tbody>';

	foreach ($context['wh_today_staff'] as $member) {
		echo '
			<tr class="windowbg">
				<td>', $member['avatar'], '</td>
				<td>', $member['real_name'], '</td>
				<td>', $member['position'], '</td>
			</tr>';
	}

	echo '
			</tbody>
		</table>
		<br class="clear">';
}

function show_recent_and_top_boxes()
{
	echo '
	<div id="admin_main_section">';

	show_recent_boxes();

	show_top_box();

	echo '
	</div>';
}

function show_recent_boxes()
{
	global $context, $txt;

	if (empty($context['warehouse_recent_boxes']))
		return;

	echo '
		<div class="wh_recent_boxes">
			<div class="cat_bar">
				<h3 class="catbg">', $txt['warehouse_recent_boxes'], '</h3>
			</div>
			<div class="windowbg nopadding">
				<div id="smfAnnouncements">
					<dl>';

	foreach ($context['warehouse_recent_boxes'] as $box) {
		echo '
						<dt><a href="', $box['url'], '">', $box['title'], '</a> ', $box['created_at'], ', ', $txt['by'], ' <em>', $box['poster'], '</em></dt>
						<dd></dd>';
	}

	echo '
					</dl>
				</div>
			</div>
		</div>';
}

function show_top_box()
{
	global $context, $txt;

	if (empty($context['warehouse_top_box']))
		return;

	echo '
		<div class="wh_top_box">
			<div class="cat_bar">
				<h3 class="catbg">', $txt['warehouse_week_top'], '</h3>
			</div>
			<div class="windowbg">
				<table class="table_grid">
					<tbody>
						<tr class="generic_list_wrapper bg odd">
							<td>', $txt['warehouse_box_title'], '</td>
							<td><a href="', $context['warehouse_top_box']['url'], '">', $context['warehouse_top_box']['title'], '</a></td>
						</tr>
						<tr class="generic_list_wrapper bg even">
							<td>', $txt['warehouse_box_owner'], '</td>
							<td>', $context['warehouse_top_box']['poster_name'], '</td>
						</tr>
						<tr class="generic_list_wrapper bg odd">
							<td>', empty($context['warehouse_top_box']['updated_at']) ? $txt['warehouse_created_at'] : $txt['warehouse_updated_at'], '</td>
							<td>', empty($context['warehouse_top_box']['updated_at']) ? $context['warehouse_top_box']['created_at'] : $context['warehouse_top_box']['updated_at'], '</td>
						</tr>
						<tr class="generic_list_wrapper bg even">
							<td>', $txt['file'], '</td>
							<td>', $context['warehouse_top_box']['filename'], '</td>
						</tr>
						<tr class="generic_list_wrapper bg odd">
							<td>', $txt['warehouse_downloads'], '</td>
							<td>', $context['warehouse_top_box']['downloads'], '</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>';
}

function show_racks()
{
	global $context, $txt, $modSettings, $settings;

	if (empty($context['warehouse_racks']))
		return;

	echo '
	<div class="wh_racks">
		<div class="title_bar noup">
			<h3 class="titlebg">', sprintf($txt['warehouse_recently_opened_boxes'], (int) $modSettings['warehouse_boxes_per_rack']), '</h3>
		</div>';

	foreach ($context['warehouse_racks'] as $rack_id => $rack) {
		if (empty($rack['num_boxes']) && empty($context['user']['is_admin']))
			continue;

		echo '
		<fieldset class="windowbg admin_group" data-id="', $rack_id, '">
			<legend>
				<a class="subbg" href="', $rack['url'], '">', $rack['title'], ' (', $rack['num_boxes'], ')</a>
			</legend>
			<div class="wh_box_list">';

		foreach ($context['warehouse_boxes'] as $box_id => $box) {
			if ($box['rack_id'] !== $rack_id)
				continue;

			echo '
				<div class="drag_box" data-id="', $box_id, '">
					<a href="', $box['url'], '"><span class="large_admin_menu_icon packages handle"></span> ', $box['title'], '</a>
				</div>';
		}

		echo '
			</div>';

		if ($context['allow_warehouse_manage_boxes_own'] || $context['allow_warehouse_manage_boxes_any']) {
			echo '
			<div class="add_box">
				<a href="', WH_BASE_URL, ';rack=', $rack_id, ';sa=add"><span class="main_icons plus"></span><br>', $txt['warehouse_add_box_button'], '</a>
			</div>';
		}

		echo '
		</fieldset>';
	}

	echo '
	</div>';

	if ($context['user']['is_admin'])
		echo '
	<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
	<script src="', $settings['default_theme_url'], '/scripts/warehouse.js"></script>';
}
