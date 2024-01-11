<?php declare(strict_types=1);

/**
 * UserArea.php
 *
 * @package Warehouse
 * @link https://github.com/dragomano/Warehouse
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2024 Bugo
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.1
 */

namespace Bugo\Warehouse;

if (! defined('SMF'))
	die('No direct access...');

class UserArea
{
	public function show(): void
	{
		global $context, $txt, $modSettings, $sourcedir;

		isAllowedTo('warehouse_manage_boxes_own');

		loadLanguage('Admin');

		$context['canonical_url'] = WH_BASE_URL . ';sa=user';

		$context['page_title'] = WH_NAME . ' - ' . $txt['warehouse_buttons'][1];

		$context['linktree'][1]['url'] = WH_BASE_URL;

		$context['linktree'][] = [
			'name' => $txt['warehouse_buttons'][1],
		];

		$listOptions = [
			'id' => 'box_list',
			'items_per_page' => $modSettings['defaultMaxListItems'],
			'base_href' => $context['canonical_url'],
			'default_sort_col' => 'date',
			'no_items_label' => $txt['warehouse_no_items'],
			'get_items' => [
				'function' => [$this, 'getOwnBoxes'],
			],
			'get_count' => [
				'function' => [$this, 'getNumOwnBoxes'],
			],
			'columns' => [
				'date' => [
					'header' => [
						'value' => $txt['date'],
					],
					'data' => [
						'function' => fn($entry) => empty($entry['created_at']) ? $txt['never'] : timeformat($entry['created_at']),
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'wt.created_at DESC',
						'reverse' => 'wt.created_at',
					],
				],
				'title' => [
					'header' => [
						'value' => $txt['warehouse_box_title'],
					],
					'data' => [
						'function' => fn($entry) => '<a class="bbc_link" href="' . $entry['url'] . '">' . $entry['title'] . '</a>',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'wr.title',
						'reverse' => 'wr.title DESC',
					],
				],
				'rack' => [
					'header' => [
						'value' => $txt['warehouse_rack'],
					],
					'data' => [
						'function' => fn($entry) => '<a class="bbc_link" href="' . $entry['rack_url'] . '">' . $entry['rack_title'] . '</a>',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'rack_title',
						'reverse' => 'rack_title DESC',
					],
				],
				'views' => [
					'header' => [
						'value' => $txt['views'],
					],
					'data' => [
						'db' => 'num_views',
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'wb.num_views',
						'reverse' => 'wb.num_views DESC',
					],
				],
				'actions' => [
					'header' => [
						'value' => $txt['warehouse_actions'],
					],
					'data' => [
						'function' => function ($entry) use ($txt) {
							$buttons = [
								'<a class="button" href="' . $entry['url'] . ';sa=edit"><span class="main_icons modify_button"></span> ' . $txt['edit'] . '</a>',
								'<a class="button" href="' . $entry['url'] . ';sa=remove"><span class="main_icons remove_button"></span> ' . $txt['delete'] . '</a>',
							];

							return implode(' ', $buttons);
						},
						'class' => 'centertext',
					],
				],
			],
			'form' => [
				'href' => $context['canonical_url'] . ';actions',
				'include_sort' => true,
				'include_start' => true,
			],
		];

		require_once($sourcedir . '/Subs-List.php');

		createList($listOptions);

		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'box_list';

		loadTemplate('Warehouse/User');

		$context['template_layers'][] = 'user_area';
	}

	public function getOwnBoxes(int $start, int $items_per_page, string $sort): array
	{
		global $smcFunc, $user_info;

		$request = $smcFunc['db_query']('', '
			SELECT wb.id, wb.rack_id, wb.title, wb.description, wb.num_views, wb.num_things, wb.created_at, wb.updated_at, wr.title AS rack_title
			FROM {db_prefix}warehouse_boxes AS wb
				LEFT JOIN {db_prefix}warehouse_racks AS wr ON wr.id = wb.rack_id
				LEFT JOIN {db_prefix}warehouse_things AS wt ON wt.box_id = wb.id
			WHERE wb.owner_id = {int:user_id}
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:per_page}',
			[
				'user_id'  => $user_info['id'],
				'sort'     => $sort,
				'start'    => $start,
				'per_page' => $items_per_page,
			]
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$items[$row['id']] = [
				'created_at' => (int) $row['created_at'],
				'rack_id'    => (int) $row['rack_id'],
				'rack_title' => $row['rack_title'],
				'title'      => $row['title'],
				'num_views'  => (int) $row['num_views'],
				'num_things' => (int) $row['num_things'],
				'rack_url'   => WH_BASE_URL . ';rack=' . $row['rack_id'],
				'url'        => WH_BASE_URL . ';box=' . $row['id'],
			];
		}

		$smcFunc['db_free_result']($request);

		return $items;
	}

	public function getNumOwnBoxes(): int
	{
		global $smcFunc, $user_info;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(wb.id)
			FROM {db_prefix}warehouse_boxes AS wb
				LEFT JOIN {db_prefix}warehouse_racks AS wr ON wr.id = wb.rack_id
				LEFT JOIN {db_prefix}warehouse_things AS wt ON wt.box_id = wb.id
			WHERE wb.owner_id = {int:user_id}
			LIMIT 1',
			[
				'user_id' => $user_info['id'],
			]
		);

		[$num_items] = $smcFunc['db_fetch_row']($request);

		$smcFunc['db_free_result']($request);

		return (int) $num_items;
	}
}
