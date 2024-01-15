<?php

function template_rack()
{
	global $txt, $context;

	echo '
	<div class="cat_bar">
		<h3 class="catbg">
			<span>', $txt['warehouse_rack_content'], sprintf($txt['warehouse_postfix'], $context['wh_rack']['title']), '</span>';

	if ($context['allow_warehouse_manage_boxes_own'] || $context['allow_warehouse_manage_boxes_any']) {
		echo '
			<a class="button floatright" href="', $context['canonical_url'], ';sa=add" style="color: blue; margin-left: 10px">', $txt['warehouse_add_box_button'], '</a>';
	}

	if ($context['user']['is_admin']) {
		echo '
			<a class="button floatright you_sure" data-confirm="', $txt['warehouse_are_you_sure'], '" href="', $context['canonical_url'], ';sa=remove" style="color: crimson; margin-left: 10px">', $txt['remove'], '</a>
			<a class="button floatright" href="', $context['canonical_url'], ';sa=edit" style="color: initial">', $txt['edit'], '</a>';
	}

	echo '
		</h3>
	</div>
	<div class="information flow_hidden">
		<div class="wh_search">
			<div class="wh_search_input_area">
				<input type="search" name="search" placeholder="', $txt['warehouse_search_whole_storage'], '">
			</div>
			<div class="wh_sort_select_area">
				<!-- @TODO -->
			</div>
		</div>
	</div>';

	if (! empty($context['wh_rack']['description']))
		echo '
	<div class="roundframe">', $context['wh_rack']['description'], '</div>';

	if (! empty($context['wh_top_boxes'])) {
		echo '
		<div class="sub_bar">
			<h3 class="subbg">', $txt['warehouse_top_boxes'], '</h3>
		</div>
		<table class="table_grid">
			<tbody>';

		$i = 0;
		foreach ($context['wh_top_boxes'] as $box) {
			echo '
				<tr class="generic_list_wrapper bg ', $i++ % 2 ? 'odd' : 'even', '">
					<td><a class="bbc_link" href="', $box['url'], '">', $box['title'], '</a></td>
				</tr>';
		}

		echo '
			</tbody>
		</table>';
	}

	show_pagination();

	echo '
	<div class="wh_boxes">';

	if (empty($context['wh_boxes']['total_num_items'])) {
		echo '
		<div class="noticebox">', $context['wh_boxes']['no_items_label'], '</div>';
	}

	foreach ($context['wh_boxes']['rows'] as $row) {
		echo '
		<div class="wh_box windowbg">
			<div><span class="large_admin_menu_icon packages"></span></div>
			<div class="word_break">', $row['data']['title']['value'], '<div class="smalltext">', $row['data']['date']['value'], '</div></div>
			<div class="stats">
				<div class="roundframe">', $row['data']['owner']['value'], '</div>
				<div class="roundframe"><div class="smalltext">', $txt['views'], '</div><strong>', $row['data']['num_views']['value'], '</strong></div>
				<div class="roundframe"><div class="smalltext">', $txt['warehouse_box_things'], '</div><strong>', $row['data']['num_things']['value'], '</strong></div>
			</div>
		</div>';
	}

	echo /** @lang text */ '
	</div>
	<script defer>
		new autoComplete({
			selector: ".wh_search_input_area input",
			minChars: 3,
			source: async function(term, response) {
				const results = await fetch("', $context['canonical_url'], ';sa=search", {
					method: "POST",
					headers: {
						"Content-Type": "application/json; charset=utf-8"
					},
					body: JSON.stringify({
						title: term
					})
				});

				if (results.ok) {
					const data = await results.json();
					response(data);
				}
			},
			renderItem: function (item, search) {
				search = search.replace(/[-\/\\^$*+?.()|[\]{}]/g, "\\$&");
				let re = new RegExp("(" + search.split(" ").join("|") + ")", "gi");

				return `<div class="autocomplete-suggestion" data-val="` + item.title + `" data-url="` + item.url + `" style="cursor: pointer">` + item.title.replace(re, "<b>$1</b>") + `</div>`;
			},
			onSelect: function(e, term, item) {
				window.location = item.dataset.url;
			}
		});
	</script>';

	show_pagination();
}

function show_pagination()
{
	global $context;

	if (empty($context['wh_boxes']['total_num_items']) || $context['wh_boxes']['items_per_page'] >= $context['wh_boxes']['total_num_items'])
		return;

	if (! empty($context['wh_boxes']['items_per_page']) && ! empty($context['wh_boxes']['page_index']))
		echo '
	<div class="pagesection">
		<div class="centertext">', $context['wh_boxes']['page_index'], '</div>
	</div>';
}
