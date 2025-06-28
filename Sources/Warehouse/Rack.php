<?php declare(strict_types=1);

/**
 * Rack.php
 *
 * @package Warehouse
 * @link https://github.com/dragomano/Warehouse
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2025 Bugo
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.3
 */

namespace Bugo\Warehouse;

use Exception;

if (! defined('SMF'))
	die('No direct access...');

class Rack
{
	use Util;

	public function __toString(): string
	{
		return 'Racks - convenient shelves for your boxes.';
	}

	public function show(int $rack): void
	{
		global $context, $txt, $sourcedir;

		loadCSSFile('https://cdn.jsdelivr.net/npm/pixabay-javascript-autocomplete@1/auto-complete.css', ['external' => true]);
		loadJavaScriptFile('https://cdn.jsdelivr.net/npm/pixabay-javascript-autocomplete@1/auto-complete.min.js', ['external' => true]);

		$context['wh_rack'] = $this->getItem($rack);

		$context['wh_rack']['description'] = parse_bbc($context['wh_rack']['description']);

		$context['canonical_url'] = WH_BASE_URL . ';rack=' . $rack;

		$context['page_title'] = WH_NAME . ' - ' . $context['wh_rack']['title'];

		$context['linktree'][] = [
			'name' => WH_NAME,
			'url'  => WH_BASE_URL,
		];

		$context['linktree'][] = [
			'name' => $context['wh_rack']['title'],
		];

		if ($context['current_subaction'] === 'edit') {
			$this->edit($rack);

			return;
		}

		if ($context['current_subaction'] === 'remove') {
			$this->remove($rack);

			redirectexit(WH_BASE_URL);
		}

		if ($context['current_subaction'] === 'add') {
			(new Box())->add($rack);

			return;
		}

		if ($context['current_subaction'] === 'search') {
			$this->search();

			return;
		}

		$context['wh_top_boxes'] = $this->getTopBoxes($rack, 3);

		if (isset($_REQUEST['delete_selected']) && ! empty($_REQUEST['boxes'])) {
			$this->removeBoxes($_REQUEST['boxes']);
		}

		$params = [' AND wb.rack_id = {int:rack}', ['rack' => $rack]];

		$listOptions = [
			'id' => 'wh_boxes',
			'items_per_page' => WH_ITEMS_PER_PAGE,
			'no_items_label' => $txt['warehouse_no_items'],
			'base_href' => $context['canonical_url'],
			'default_sort_col' => 'date',
			'get_items' => [
				'function' => [$this, 'getAllBoxes'],
				'params'   => $params,
			],
			'get_count' => [
				'function' => [$this, 'getNumAllBoxes'],
				'params'   => $params,
			],
			'columns' => [
				'id' => [
					'data' => [
						'db' => 'id',
					],
					'sort' => [
						'default' => 'wb.id DESC',
						'reverse' => 'wb.id',
					],
				],
				'date' => [
					'data' => [
						'db' => 'created_at',
					],
					'sort' => [
						'default' => 'wb.created_at DESC',
						'reverse' => 'wb.created_at',
					],
				],
				'title' => [
					'data' => [
						'function' => fn($entry) => '<a class="bbc_link" href="' . $entry['url'] . '" title="' . $this->shortText($entry['description']) . '">' . $entry['title'] . '</a>',
					],
					'sort' => [
						'default' => 'wb.title DESC',
						'reverse' => 'wb.title',
					],
				],
				'owner' => [
					'data' => [
						'function' => fn($entry) => $entry['poster']['avatar'],
					],
					'sort' => [
						'default' => 'poster_name DESC',
						'reverse' => 'poster_name',
					],
				],
				'num_views' => [
					'data' => [
						'db' => 'num_views',
					],
					'sort' => [
						'default' => 'wb.num_views DESC',
						'reverse' => 'wb.num_views',
					],
				],
				'num_things' => [
					'data' => [
						'db' => 'num_things',
					],
					'sort' => [
						'default' => 'wb.num_things DESC',
						'reverse' => 'wb.num_things',
					],
				],
			],
			'form' => [
				'name' => 'boxes',
				'href' => $context['canonical_url'],
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => [
					$context['session_var'] => $context['session_id'],
				],
			],
			'additional_rows' => [
				[
					'position' => 'below_table_data',
					'value' => allowedTo('warehouse_manage_boxes_own') || allowedTo('warehouse_manage_boxes_any') ? '
						<input type="hidden">
						<button class="button floatright" type="submit" name="delete_selected">
							<span class="main_icons delete"></span>	' . $txt['quickmod_delete_selected'] . '
						</button>' : ''
				]
			],
		];

		if (allowedTo('warehouse_manage_boxes_own') || allowedTo('warehouse_manage_boxes_any')) {
			$listOptions['columns']['actions'] = [
				'header' => [
					'value' => '<input type="checkbox" onclick="invertAll(this, this.form);">',
				],
				'data' => [
					'function' => fn($entry) => '<input type="checkbox" value="' . $entry['id'] . '" name="boxes[]"' . ($entry['is_own'] ? '' : ' disabled') . '>',
					'class' => 'centertext',
				],
			];
		}

		require_once $sourcedir . '/Subs-List.php';

		createList($listOptions);

		loadTemplate('Warehouse/Rack', ['admin', 'warehouse']);

		$context['sub_template'] = 'rack';
	}

	public function add(): void
	{
		global $context, $txt;

		isAllowedTo('warehouse_manage_boxes_any');

		$context['robot_no_index'] = true;

		$context['canonical_url'] = WH_BASE_URL . ';sa=add_rack';

		$context['post_url'] = $context['canonical_url'] . ';post';

		$context['page_title'] = WH_NAME . ' - ' . $txt['warehouse_add_rack'];

		$context['post_title'] = $txt['warehouse_add_rack'];

		$context['linktree'][1]['url'] = WH_BASE_URL;

		$context['linktree'][] = [
			'name' => $txt['warehouse_add_rack'],
		];

		$context['posting_fields']['subject'] = ['no'];

		$context['posting_fields']['title']['label']['text'] = $txt['warehouse_rack_title'];
		$context['posting_fields']['title']['input'] = [
			'type' => 'text',
			'attributes' => [
				'size'      => '100%',
				'maxlength' => 255,
				'required'  => true,
				'value'     => $_REQUEST['title'] ?? '',
			]
		];

		$this->prepareEditor('rack');

		loadTemplate('Post');

		loadTemplate('Warehouse/Post', ['admin', 'warehouse']);

		$context['sub_template'] = 'post_rack';

		if (isset($_REQUEST['post'])) {
			$this->post();
		}
	}

	public function edit(int $rack): void
	{
		global $context, $txt;

		isAllowedTo('warehouse_manage_boxes_any');

		$context['wh_rack'] = $this->getItem($rack);

		$context['robot_no_index'] = true;

		$context['canonical_url'] = WH_BASE_URL . ';rack=' . $rack . ';sa=edit';

		$context['post_url'] = $context['canonical_url'] . ';update';

		$context['page_title'] = WH_NAME . ' - ' . $txt['warehouse_edit_rack'];

		$context['post_title'] = $txt['warehouse_edit_rack'];

		$context['linktree'][] = [
			'name' => $context['wh_rack']['title'],
			'url'  => WH_BASE_URL . ';rack=' . $rack,
		];

		$context['linktree'][] = [
			'name' => $txt['warehouse_edit_rack'],
		];

		$context['posting_fields']['subject'] = ['no'];

		$context['posting_fields']['title']['label']['text'] = $txt['warehouse_rack_title'];
		$context['posting_fields']['title']['input'] = [
			'type' => 'text',
			'attributes' => [
				'size'      => '100%',
				'maxlength' => 255,
				'required'  => true,
				'value'     => $_REQUEST['title'] ?? $context['wh_rack']['title'] ?? '',
			]
		];

		$this->prepareEditor('rack');

		loadTemplate('Post');

		loadTemplate('Warehouse/Post', ['admin', 'warehouse']);

		$context['sub_template'] = 'post_rack';

		if (isset($_REQUEST['update'])) {
			$this->update();
		}
	}

	public function remove(int $rack): void
	{
		global $smcFunc;

		isAllowedTo('warehouse_manage_boxes_any');

		(new Cleaner())->removeRacks([$rack]);

		$request = $smcFunc['db_query']('', '
			SELECT id
			FROM {db_prefix}warehouse_boxes
			WHERE rack_id = {int:rack}',
			[
				'rack' => $rack,
			]
		);

		$boxes = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$boxes[] = (int) $row['id'];
		}

		$smcFunc['db_free_result']($request);

		$this->removeBoxes($boxes);

		$this->logAction('remove_rack', ['rack' => $rack]);
	}

	public function post(): void
	{
		global $txt, $smcFunc;

		$title       = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (empty($title)) {
			fatal_error($txt['warehouse_empty_data'], false);
		}

		$data = [
			'title'       => $title,
			'description' => $description ?? '',
			'status'      => (int) allowedTo('warehouse_manage_boxes_any'),
		];

		$id = $smcFunc['db_insert']('',
			'{db_prefix}warehouse_racks',
			[
				'title'       => 'string',
				'description' => 'string',
				'status'      => 'int',
			],
			$data,
			['id'],
			1
		);

		$link = sprintf(/** @lang text */ '<a href="%s;rack=%s">%s</a>', WH_BASE_URL, $id, $title);
		$this->logAction('add_rack', ['rack' => $link]);

		redirectexit('action=' . WH_ACTION . ';rack=' . $id);
	}

	public function update(): void
	{
		global $txt, $context, $smcFunc;

		$title       = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (empty($title)) {
			fatal_error($txt['warehouse_empty_data'], false);
		}

		$data = [
			'id'          => $context['wh_rack']['id'],
			'title'       => $title,
			'description' => $description,
			'status'      => (int) allowedTo('warehouse_manage_boxes_any'),
		];

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}warehouse_racks
			SET title = {string:title}, description = {string:description}, status = {int:status}
			WHERE id = {int:id}',
			$data,
		);

		$link = sprintf(/** @lang text */ '<a href="%s;rack=%s">%s</a>', WH_BASE_URL, $context['wh_rack']['id'], $context['wh_rack']['title']);
		$this->logAction('edit_rack', ['rack' => $link]);

		redirectexit('action=' . WH_ACTION . ';rack=' . $context['wh_rack']['id']);
	}

	public function search(): void
	{
		global $smcFunc;

		$data = $this->getJSON();

		if (empty($data['title']))
			return;

		$query_string = ' AND (INSTR(LOWER(wb.title), {string:search}) > 0)';
		$query_params = [
			'search' => $smcFunc['htmltrim']($smcFunc['htmlspecialchars']($data['title']))
		];

		$boxes = $this->getAllBoxes(0, 30, 'wb.title', $query_string, $query_params);

		exit(json_encode(array_values($boxes)));
	}

	public function getItem(int $rack): array
	{
		global $smcFunc, $context, $txt;

		$request = $smcFunc['db_query']('', '
			SELECT id, title, description, num_boxes
			FROM {db_prefix}warehouse_racks
			WHERE id = {int:rack}
			LIMIT 1',
			[
				'rack' => $rack,
			]
		);

		if (empty($smcFunc['db_num_rows']($request))) {
			$context['error_link'] = WH_BASE_URL;

			$txt['back'] = $txt['warehouse_see_other_racks'];

			fatal_lang_error('warehouse_rack_not_found', false, null, 404);
		}

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$data = [
				'id'          => (int) $row['id'],
				'title'       => $row['title'],
				'description' => $row['description'],
				'num_boxes'   => (int) $row['num_boxes'],
			];
		}

		$smcFunc['db_free_result']($request);

		return $data ?? [];
	}

	public function getAll(): array
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', /** @lang text */ '
			SELECT id, title, description, num_boxes
			FROM {db_prefix}warehouse_racks
			ORDER BY rack_order',
			[]
		);

		$racks = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$racks[(int) $row['id']] = [
				'url'         => WH_BASE_URL . ';rack=' . $row['id'],
				'title'       => $row['title'],
				'description' => parse_bbc($row['description']),
				'num_boxes'   => (int) $row['num_boxes'],
			];

		$smcFunc['db_free_result']($request);

		return $racks;
	}

	public function getTopBoxes(int $rack, int $limit = 6): array
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT wb.id, wb.title
			FROM {db_prefix}warehouse_boxes AS wb
				INNER JOIN {db_prefix}warehouse_things AS wt ON (wt.box_id = wb.id)
			WHERE wb.status = {int:status}
				AND wb.rack_id = {int:rack}
			ORDER BY wt.requested_at DESC
			LIMIT {int:limit}',
			[
				'status' => 1,
				'rack'   => $rack,
				'limit'  => $limit,
			]
		);

		$boxes = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$boxes[$row['id']] = [
				'title' => $row['title'],
				'url'   => WH_BASE_URL . ';box=' . $row['id'],
			];
		}

		$smcFunc['db_free_result']($request);

		return $boxes;
	}

	public function getAllBoxes(int $start, int $items_per_page, string $sort, string $query_string = '', array $query_params = []): array
	{
		global $smcFunc, $txt;

		$request = $smcFunc['db_query']('', '
			SELECT wb.id, wb.owner_id, wb.title, wb.description, wb.num_views, wb.num_things, wb.created_at, wb.updated_at,
				COALESCE(mem.real_name, {string:guest}) AS poster_name
			FROM {db_prefix}warehouse_boxes AS wb
				LEFT JOIN {db_prefix}members AS mem ON (wb.owner_id = mem.id_member)
			WHERE wb.status = {int:status}' . (empty($query_string) ? '' : '
				' . $query_string) . '
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:limit}',
			array_merge($query_params, [
				'guest'  => $txt['guest_title'],
				'status' => 1,
				'sort'   => $sort,
				'start'  => $start,
				'limit'  => $items_per_page,
			])
		);

		$items = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$items[$row['id']] = [
				'id'          => $row['id'],
				'url'         => WH_BASE_URL . ';box=' . $row['id'],
				'title'       => $row['title'],
				'description' => shorten_subject(strip_tags(parse_bbc($row['description'])), 200),
				'num_views'   => $row['num_views'],
				'num_things'  => $row['num_things'],
				'is_own'      => $this->isOwn((int) $row['owner_id']),
				'created_at'  => timeformat($row['created_at']),
				'updated_at'  => timeformat($row['updated_at']),
				'poster'      => [
					'id'   => $row['owner_id'],
					'name' => $row['poster_name'],
				],
			];

		$smcFunc['db_free_result']($request);

		$this->prepareAvatarsForItems($items);

		return $items;
	}

	public function getNumAllBoxes(string $query_string = '', array $query_params = []): int
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(wb.id)
			FROM {db_prefix}warehouse_boxes AS wb
			WHERE wb.status = {int:status}' . (empty($query_string) ? '' : '
				' . $query_string),
			array_merge($query_params, [
				'status' => 1,
			])
		);

		[$num_entries] = $smcFunc['db_fetch_row']($request);

		$smcFunc['db_free_result']($request);

		return (int) $num_entries;
	}

	public function removeBoxes(array $boxes = []): void
	{
		global $context, $smcFunc, $sourcedir;

		if (empty($boxes) || empty($context['user']['is_admin']))
			return;

		(new Cleaner())->removeBoxes($boxes);

		foreach ($boxes as $box) {
			$this->decrement('racks', $context['wh_rack']['id'], 'num_boxes');
		}

		$request = $smcFunc['db_query']('', '
			SELECT attach_id FROM {db_prefix}warehouse_things
			WHERE box_id IN ({array_int:boxes})',
			[
				'boxes' => $boxes,
			]
		);

		$attachments = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$attachments[] = $row['attach_id'];
		}

		$smcFunc['db_free_result']($request);

		if (empty($attachments))
			return;

		require_once $sourcedir . '/ManageAttachments.php';

		removeAttachments(['id_attach' => $attachments]);
	}

	private function prepareAvatarsForItems(array &$items): void
	{
		global $memberContext;

		if (empty($items))
			return;

		$userIds = loadMemberData(array_map(fn($item) => $item['poster']['id'], $items));

		foreach ($items as &$item) {
			$userId = $item['poster']['id'];

			if (!isset($memberContext[$userId]) && in_array($userId, $userIds)) {
				try {
					loadMemberContext($userId, true);
				} catch (Exception $e) {
					fatal_error(sprintf("%s: %s", WH_NAME, $e->getMessage()));
				}
			}

			$item['poster']['avatar'] = $memberContext[$userId]['avatar']['image'];
		}
	}
}
