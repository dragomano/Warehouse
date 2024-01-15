<?php declare(strict_types=1);

/**
 * TestArea.php
 *
 * @package Warehouse
 * @link https://github.com/dragomano/Warehouse
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2024 Bugo
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.2
 */

namespace Bugo\Warehouse;

if (! defined('SMF'))
	die('No direct access...');

class TestArea
{
	public function show(): void
	{
		global $context, $txt, $modSettings, $sourcedir;

		isAllowedTo('warehouse_manage_boxes_any');

		loadLanguage('Admin+ManageMembers');

		$context['canonical_url'] = WH_BASE_URL . ';sa=test';

		$context['page_title'] = WH_NAME . ' - ' . $txt['warehouse_buttons'][2];

		$context['linktree'][1]['url'] = WH_BASE_URL;

		$context['linktree'][] = [
			'name' => $txt['warehouse_buttons'][1],
		];

		if (isset($_REQUEST['run'])) {
			$action = $_REQUEST['run'];
			$box = (int) ($_REQUEST['id'] ?? 0);

			switch ($action) {
				case 'approve':
					$this->toggleStatus($box, 1);
					break;

				case 'reject':
					$this->toggleStatus($box, 2);
					break;

				case 'approve_all':
					$this->approveAll();
					break;
			}

			redirectexit($context['canonical_url']);
		}

		$listOptions = [
			'id' => 'box_list',
			'items_per_page' => $modSettings['defaultMaxListItems'],
			'base_href' => $context['canonical_url'],
			'default_sort_col' => 'date',
			'no_items_label' => $txt['warehouse_no_items'],
			'get_items' => [
				'function' => [$this, 'getTestBoxes'],
			],
			'get_count' => [
				'function' => [$this, 'getNumTestBoxes'],
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
						'default' => 'wb.created_at DESC',
						'reverse' => 'wb.created_at',
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
				'actions' => [
					'header' => [
						'value' => $txt['warehouse_actions'],
					],
					'data' => [
						'function' => function ($entry) use ($context, $txt) {
							$buttons = [
								'<a class="button" href="' . $context['canonical_url'] . ';run=approve;id=' . $entry['id'] . '"><span class="main_icons like"></span> ' . $txt['approve'] . '</a>',
								'<a class="button" href="' . $context['canonical_url'] . ';run=reject;id=' . $entry['id'] . '"><span class="main_icons ignore"></span> ' . $txt['admin_browse_w_reject'] . '</a>',
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

		loadTemplate('Warehouse/Test');

		$context['template_layers'][] = 'test_area';
	}

	public function getTestBoxes(int $start, int $items_per_page, string $sort): array
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT wb.id, wb.rack_id, wb.title, wb.num_things, wb.created_at, wr.title AS rack_title
			FROM {db_prefix}warehouse_boxes AS wb
				LEFT JOIN {db_prefix}warehouse_racks AS wr ON wr.id = wb.rack_id
				LEFT JOIN {db_prefix}warehouse_things AS wt ON wt.box_id = wb.id
			WHERE wb.status = {int:status}
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:per_page}',
			[
				'status'   => 0,
				'sort'     => $sort,
				'start'    => $start,
				'per_page' => $items_per_page,
			]
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$items[$row['id']] = [
				'id'         => (int) $row['id'],
				'created_at' => (int) $row['created_at'],
				'rack_id'    => (int) $row['rack_id'],
				'rack_title' => $row['rack_title'],
				'title'      => $row['title'],
				'num_things' => $row['num_things'],
				'rack_url'   => WH_BASE_URL . ';rack=' . $row['rack_id'],
				'url'        => WH_BASE_URL . ';box=' . $row['id'],
			];
		}

		$smcFunc['db_free_result']($request);

		return $items;
	}

	public function getNumTestBoxes(): int
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(id)
			FROM {db_prefix}warehouse_boxes
			WHERE status = {int:status}
			LIMIT 1',
			[
				'status' => 0,
			]
		);

		[$num_items] = $smcFunc['db_fetch_row']($request);

		$smcFunc['db_free_result']($request);

		return (int) $num_items;
	}

	private function toggleStatus(int $id, int $status = 0): void
	{
		global $smcFunc;

		if (empty($id))
			return;

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}warehouse_boxes
			SET status = {int:status}
			WHERE id = {int:id}',
			[
				'status' => $status,
				'id'     => $id,
			],
		);
	}

	private function approveAll(): void
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}warehouse_boxes
			SET status = {int:status}
			WHERE status = 0',
			[
				'status' => 1,
			],
		);
	}
}
