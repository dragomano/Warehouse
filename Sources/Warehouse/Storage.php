<?php declare(strict_types=1);

/**
 * Storage.php
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

class Storage
{
	use Util;

	public function __toString(): string
	{
		return 'A warehouse is a place where people store their things. In boxes.';
	}

	/**
	 * @return mixed|void
	 */
	public function init()
	{
		isAllowedTo('warehouse_view');

		$subActions = [
			'box'  => [new Box, 'show'],
			'rack' => [new Rack, 'show'],
		];

		foreach ($subActions as $action => $callback) {
			if (isset($_REQUEST[$action]))
				return call_user_func($callback, (int) $_REQUEST[$action]);
		}

		$this->show();
	}

	public function show()
	{
		global $context, $scripturl, $txt;

		loadTemplate('Warehouse/Storage', ['admin', 'warehouse']);

		$context['page_title']    = WH_NAME;
		$context['canonical_url'] = WH_BASE_URL;

		$context['linktree'][] = [
			'name' => $context['page_title'],
		];

		$context['warehouse_recent_boxes'] = $this->getRecentBoxes();

		$context['warehouse_top_box'] = $this->getTopBox();

		$context['warehouse_boxes'] = $this->getBoxes();

		$context['warehouse_racks'] = (new Rack)->getAll();

		$context['wh_buttons'] = [
			[
				'href'        => WH_BASE_URL,
				'title'       => $txt['warehouse_buttons'][0],
				'is_selected' => empty($context['current_subaction']),
				'icon'        => '<i class="main_icons notify_button"></i>',
				'show'        => true
			],
			[
				'href'        => WH_BASE_URL . ';sa=user',
				'title'       => $txt['warehouse_buttons'][1],
				'is_selected' => $context['current_subaction'] === 'user',
				'icon'        => '<i class="main_icons packages"></i>',
				'show'        => allowedTo('warehouse_manage_boxes_own')
			],
			[
				'href'        => WH_BASE_URL . ';sa=test',
				'title'       => $txt['warehouse_buttons'][2],
				'is_selected' => $context['current_subaction'] === 'test',
				'icon'        => '<i class="main_icons split_button"></i>',
				'show'        => allowedTo('warehouse_manage_boxes_any')
			],
		];

		if (isset($_REQUEST['actions']) && $context['user']['is_admin']) {
			$this->updateRackOrder();

			$this->moveBox();
		}

		switch ($_REQUEST['sa'] ?? 'main') {
			case 'add_rack':
				(new Rack())->add();
				return;

			case 'user':
				(new UserArea())->show();
				return;

			case 'test':
				(new TestArea())->show();
				return;

			default:
				$context['wh_week_activity'] = $this->getWeekActivity();
		}

		addJavaScriptVar('whWorkUrl', WH_BASE_URL . ';actions', true);

		$context['sub_template'] = 'storage';
	}

	public function getRecentBoxes(): array
	{
		global $smcFunc, $txt;

		$request = $smcFunc['db_query']('', '
			SELECT wb.id, wb.owner_id, wb.title, wb.description, wb.created_at, wb.updated_at,
				a.id_attach, a.attachment_type, a.filename, a.file_hash, a.fileext, a.mime_type,
				COALESCE(mem.real_name, {string:guest}) AS poster_name
			FROM {db_prefix}warehouse_boxes AS wb
				LEFT JOIN {db_prefix}warehouse_things AS wt ON (wb.id = wt.box_id)
				LEFT JOIN {db_prefix}attachments AS a ON (wt.attach_id = a.id_attach AND a.id_thumb = 0)
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = wb.owner_id)
			WHERE wb.status = {int:status}
			ORDER BY wb.created_at DESC
			LIMIT {int:limit}',
			[
				'guest'  => $txt['guest_title'],
				'status' => 1,
				'limit'  => 10,
			]
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$items[$row['id']] = [
				'poster'      => $row['poster_name'],
				'url'         => WH_BASE_URL . ';box=' . $row['id'],
				'title'       => $row['title'],
				'description' => $this->shortText($row['description']),
				'created_at'  => timeformat($row['created_at']),
			];

		$smcFunc['db_free_result']($request);

		return $items;
	}

	public function getTopBox(): array
	{
		global $smcFunc, $txt;

		$request = $smcFunc['db_query']('', /** @lang text */ '
			SELECT wt.id, wt.attach_id, wt.box_id, a.filename, a.downloads,
				wb.title, wb.created_at, wb.updated_at,	COALESCE(mem.real_name, {string:guest}) AS poster_name
			FROM {db_prefix}warehouse_things AS wt
				INNER JOIN {db_prefix}attachments AS a ON (wt.attach_id = a.id_attach)
				INNER JOIN {db_prefix}warehouse_boxes AS wb ON (wt.box_id = wb.id)
				LEFT JOIN {db_prefix}members AS mem ON (wt.owner_id = mem.id_member)
			WHERE FROM_UNIXTIME(wt.requested_at) > NOW() - INTERVAL 7 DAY
				AND a.width = 0
				AND wb.status = 1
			ORDER BY downloads DESC
			LIMIT 1',
			[
				'guest'  => $txt['guest_title'],
				'status' => 1,
			]
		);

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$data = [
				'url'         => WH_BASE_URL . ';box=' . $row['box_id'],
				'title'       => $row['title'],
				'created_at'  => empty($row['created_at']) ? 0 : timeformat($row['created_at']),
				'updated_at'  => empty($row['updated_at']) ? 0 : timeformat($row['updated_at']),
				'poster_name' => $row['poster_name'],
				'downloads'   => $row['downloads'],
				'filename'    => $row['filename'],
			];
		}

		$smcFunc['db_free_result']($request);

		return $data ?? [];
	}

	public function getBoxes(): array
	{
		global $smcFunc, $modSettings;

		$request = $smcFunc['db_query']('', '
			SELECT t.id, t.rack_id, t.title
			FROM (
				SELECT wb.*,
					(SELECT COUNT(*)
						FROM {db_prefix}warehouse_boxes
						WHERE rack_id = wb.rack_id AND created_at >= wb.created_at) as i
				FROM {db_prefix}warehouse_boxes AS wb
			) t
			WHERE t.i <= {int:limit} AND t.status = {int:status}',
			[
				'limit'  => (int) ($modSettings['warehouse_boxes_per_rack'] ?? 6),
				'status' => 1,
			]
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$items[$row['id']] = [
				'rack_id' => (int) $row['rack_id'],
				'title'   => $row['title'],
				'url'     => WH_BASE_URL . ';box=' . $row['id'],
			];
		}

		$smcFunc['db_free_result']($request);

		return $items;
	}

	/**
	 * Получаем строку с количеством новых коробок за каждый день текущей недели
	 */
	private function getWeekActivity(): string
	{
		global $modSettings, $smcFunc, $txt;

		if (empty($modSettings['warehouse_enable_activity_chart']))
			return '';

		loadJavaScriptFile('https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js', ['external' => true]);

		$result = [];

		$cache_time = 3 * 24 * 60 * 60;
		if (($activity = cache_get_data('wh_week_activity', $cache_time)) === null) {
			$request = $smcFunc['db_query']('', '
				SELECT DATE(FROM_UNIXTIME(created_at)) AS created_at, COUNT(id) AS amount
				FROM {db_prefix}warehouse_boxes
				WHERE status = {int:status}
					AND (created_at BETWEEN {int:min_date} AND {int:max_date})
				GROUP BY DATE(FROM_UNIXTIME(created_at))',
				[
					'status'   => 1,
					'min_date' => strtotime('Mon this week'),
					'max_date' => strtotime('Mon next week')
				]
			);

			$items = [];
			while ($row = $smcFunc['db_fetch_assoc']($request))
				$items[$row['created_at']] = $row['amount'];

			$smcFunc['db_free_result']($request);

			if (empty($items))
				return '';

			$activity = [];
			foreach ($items as $date => $amount)
				$activity[$date] = [
					'name'  => $txt['days'][date('w', strtotime($date))],
					'count' => $amount
				];

			$result = [];
			foreach ($activity as $date => $value)
				$result[] = '{name: "' . $value['name'] . ', ' . date('j', strtotime($date)) . ' ' . $txt['months'][date('n', strtotime($date))] . '", value: ' . $value['count'] . '}';

			$activity = implode(',', $result);

			cache_put_data('wh_week_activity', $activity, $cache_time);
		}

		return count($result) > 1 ? $activity : '';
	}

	private function updateRackOrder(): void
	{
		global $smcFunc;

		$data = $this->getJSON();

		if (empty($data['new_order']))
			return;

		$conditions = '';
		foreach ($data['new_order'] as $rack_order => $id) {
			$conditions .= ' WHEN id = ' . $id . ' THEN ' . $rack_order;
		}

		if (empty($conditions))
			return;

		$smcFunc['db_query']('', /** @lang text */ '
			UPDATE {db_prefix}warehouse_racks
			SET rack_order = CASE ' . $conditions . ' ELSE rack_order END
			WHERE id IN ({array_int:racks})',
			[
				'racks' => $data['new_order'],
			]
		);
	}

	private function moveBox(): void
	{
		global $smcFunc;

		$data = $this->getJSON();

		if (empty($data['old_rack']) || empty($data['new_rack']) || empty($data['box']))
			return;

		$result = $smcFunc['db_query']('', '
			UPDATE {db_prefix}warehouse_boxes
			SET rack_id = {string:rack_id}
			WHERE id = {int:id}',
			[
				'rack_id' => $data['new_rack'],
				'id'      => $data['box'],
			]
		);

		if (empty($result))
			return;

		$this->decrement('racks', (int) $data['old_rack'], 'num_boxes');
		$this->increment('racks', (int) $data['new_rack'], 'num_boxes');
	}
}
