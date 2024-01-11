<?php

function template_box()
{
	global $context, $txt, $settings;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span>', $txt['warehouse_box'], sprintf($txt['warehouse_postfix'], $context['wh_box']['title']), '</span>';

	if ($context['wh_box']['is_own']) {
		echo '
			<a class="button floatright you_sure" data-confirm="', $txt['warehouse_are_you_sure'], '" href="', $context['canonical_url'], ';sa=remove" style="color: crimson; margin-left: 10px">', $txt['remove'], '</a>
			<a class="button floatright" href="', $context['canonical_url'], ';sa=edit" style="color: initial">', $txt['edit'], '</a>';
	}

	echo '
		</h3>
	</div>
	<div class="information">
		<div class="wh_box_info wh_box_sizing">
			<div class="wh_sub">
				<table class="table_grid">
					<tbody>
						<tr class="windowbg">
							<td><strong>', $txt['warehouse_box_owner'], '</strong></td>
							<td><a href="', $context['wh_box']['poster']['url'], '">', $context['wh_box']['poster']['name'], '</a></td>
							<td><strong>', $txt['warehouse_created_at'], '</strong></td>
							<td>', $context['wh_box']['created_at'], '</td>
						</tr>
						<tr class="windowbg">
							<td><strong>', $txt['warehouse_updated_at'], '</strong></td>
							<td>', $context['wh_box']['updated_at'], '</td>
							<td><strong>', $txt['warehouse_num_views'], '</strong></td>
							<td>', $context['wh_box']['num_views'], '</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>';

	if (! empty($context['wh_box']['description']))
		echo '
	<div class="wh_box_description">
		<fieldset class="windowbg admin_group wh_box_sizing">
			<legend>', $txt['warehouse_box_description'], '</legend>
			<div>', $context['wh_box']['description'], '</div>
		</fieldset>
	</div>';

	if (empty($context['wh_box']['things']))
		return;

	echo '
	<div class="wh_box_description">
		<fieldset class="windowbg admin_group wh_box_info wh_box_sizing">
			<legend>', $txt['warehouse_box_content'], '</legend>';

	$class = count($context['wh_box']['things']) > 1 ? 'wh_thing ' : '';

	foreach ($context['wh_box']['things'] as $thing) {
		echo '
			<div class="', $class, 'wh_sub">
				<table class="table_grid">
					<thead>
						<tr class="title_bar">
							<th colspan="3">
								<a href="', $thing['url'], '" style="display: inline; float: none">
									<img class="centericon" alt="', $thing['name'], '" src="', $settings['default_images_url'], '/icons/clip.png"> ', $thing['name'], '
								</a>
							</th>
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
		</fieldset>
	</div>';
}
