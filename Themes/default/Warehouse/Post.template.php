<?php

function template_add()
{
	global $txt, $context;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span>', $txt['warehouse_new_box'], '</span>
			<a class="button floatright" href="', WH_BASE_URL, '" style="color: initial">', $txt['warehouse_back_to_storage'], '</a>
		</h3>
	</div>
	<div class="roundframe noup">
		<form action="', $context['canonical_url'], ';post" method="post" enctype="multipart/form-data">';

	template_post_header();

	echo '
			<div class="preview"></div>
			<div>';

	template_control_richedit($context['post_box_name'], 'smileyBox_message', 'bbcBox_message');

	echo '
			</div>
			<div class="title_bar">
				<h4 class="titlebg">', $txt['warehouse_box_content'], '</h4>
			</div>
			<br class="clear">
			<div class="centertext">
				<input type="hidden" name="MAX_FILE_SIZE" value="', WH_SIZE_LIMIT, '">
				<input type="file" name="things[]" accept="', WH_ACCEPTED_FILE_TYPES, '" multiple required>
				<hr>
				<button type="submit" class="button">', $txt['post'], '</button>
			</div>
		</form>
	</div>';
}

function template_edit()
{
	global $txt, $context, $settings;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">', $txt['warehouse_edit_box'], '</h3>
	</div>
	<div class="roundframe noup">
		<form action="', $context['canonical_url'], ';update" method="post" enctype="multipart/form-data">';

	template_post_header();

	echo '
			<div class="preview"></div>
			<div>';

	template_control_richedit($context['post_box_name'], 'smileyBox_message', 'bbcBox_message');

	echo '
			</div>
			<div class="title_bar">
				<h4 class="titlebg">', $txt['warehouse_box_content'], '</h4>
			</div>
			<div class="generic_list_wrapper wh_box_info">';

	$class = count($context['wh_box']['things']) > 1 ? 'wh_thing ' : '';

	foreach ($context['wh_box']['things'] as $thing) {
		echo '
				<div class="', $class, 'wh_sub">
					<table class="table_grid">
						<thead>
							<tr class="title_bar">
								<th colspan="2">
									<a href="', $thing['url'], '" style="display: inline; float: none">
										<img class="centericon" alt="', $thing['name'], '" src="', $settings['default_images_url'], '/icons/clip.png"> ', $thing['name'], '
									</a>
								</th>
								<th class="righttext"><input type="checkbox" name="things[]" value="' . $thing['id'] . '" checked></th>
							</tr>
						</thead>
						<tbody>
							<tr class="windowbg">
								<td><strong>', $txt['warehouse_filesize'], '</strong></td>
								<td>', $thing['size'], '</td>';

		if (isset($thing['thumb_url'])) {
			echo /** @lang text */ '
								<td class="centertext" rowspan="4"><img alt="', $thing['name'], '" src="', $thing['thumb_url'], '"></td>';
		}

		echo '
							</tr>
							<tr class="windowbg">
								<td><strong>', $txt['warehouse_downloads'], '</strong></td>
								<td>', $thing['downloads'], '</td>
							</tr>
							<tr class="windowbg">
								<td><strong>', $txt['warehouse_uploaded_at'], '</strong></td>
								<td>', $thing['created_at'], '</td>
							</tr>
							<tr class="windowbg">
								<td><strong>', $txt['warehouse_requested_at'], '</strong></td>
								<td>', $thing['requested_at'], '</td>
							</tr>
						</tbody>
					</table>
				</div>';
	}

	echo '
			</div>
			<br class="clear">
			<div class="centertext">
				<input type="hidden" name="MAX_FILE_SIZE" value="', WH_SIZE_LIMIT, '">
				<input type="file" name="things[]" accept="', WH_ACCEPTED_FILE_TYPES, '" multiple', empty($context['wh_box']['things']) ? ' required' : '', '>
				<hr>
				<button type="submit" class="button">', $txt['save'], '</button>
			</div>
		</form>
	</div>';
}

function template_post_rack()
{
	global $txt, $context;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span>', $context['post_title'], '</span>
			<a class="button floatright" href="', WH_BASE_URL, '" style="color: initial">', $txt['warehouse_back_to_storage'], '</a>
		</h3>
	</div>
	<div class="roundframe noup">
		<form action="', $context['post_url'], '" method="post">';

	template_post_header();

	echo '
			<div class="preview"></div>
			<div>';

	template_control_richedit($context['post_box_name'], 'smileyBox_message', 'bbcBox_message');

	echo '
			</div>
			<br class="clear">
			<div class="centertext">
				<button type="submit" class="button">', $txt['save'], '</button>
			</div>
		</form>
	</div>';
}
