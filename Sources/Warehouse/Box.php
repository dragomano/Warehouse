<?php declare(strict_types=1);

/**
 * Box.php
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

if (! defined('SMF'))
	die('No direct access...');

class Box
{
	use Util;

	public function __toString(): string
	{
		return 'There\'s something in it, or maybe not. Do you want to open it?';
	}

	public function show(int $box): void
	{
		global $context;

		$context['wh_box'] = $this->getContent($box);

		if ($context['current_subaction'] === 'edit') {
			$this->edit($box);

			return;
		}

		if ($context['current_subaction'] === 'remove') {
			$this->remove($box);

			redirectexit(WH_BASE_URL . ';rack=' . $context['wh_box']['rack']['id']);
		}

		$context['robot_no_index'] = empty($context['wh_box']['things']);

		$context['canonical_url'] = WH_BASE_URL . ';box=' . $box;

		$context['page_title'] = WH_NAME . ' - ' . $context['wh_box']['rack']['title'] . ' - ' . $context['wh_box']['title'];

		$context['linktree'][] = [
			'name' => WH_NAME,
			'url'  => WH_BASE_URL,
		];

		$context['linktree'][] = [
			'name' => $context['wh_box']['rack']['title'],
			'url'  => $context['wh_box']['rack']['url'],
		];

		$context['linktree'][] = [
			'name' => $context['wh_box']['title'],
		];

		$context['wh_box']['description'] = parse_bbc($context['wh_box']['description']);

		loadTemplate('Warehouse/Box', ['admin', 'warehouse']);

		$context['sub_template'] = 'box';

		$this->updateNumViews($box);
	}

	public function add(int $rack): void
	{
		global $context, $txt;

		isAllowedTo(['warehouse_manage_boxes_own', 'warehouse_manage_boxes_any']);

		$racks = (new Rack())->getAll();

		$context['robot_no_index'] = true;

		$context['canonical_url'] = WH_BASE_URL . ';rack=' . $rack . ';sa=add';

		$context['page_title'] = WH_NAME . ' - ' . $racks[$rack]['title'] . ' - ' . $txt['warehouse_add_box'];

		$context['linktree'][2]['url'] = WH_BASE_URL . ';rack=' . $rack;

		$context['linktree'][] = [
			'name' => $txt['warehouse_add_box'],
		];

		$context['posting_fields']['subject'] = ['no'];

		$context['posting_fields']['title']['label']['text'] = $txt['warehouse_box_title'];
		$context['posting_fields']['title']['input'] = [
			'type' => 'text',
			'attributes' => [
				'size'      => '100%',
				'maxlength' => 255,
				'required'  => true,
				'value'     => $_REQUEST['title'] ?? '',
			]
		];

		$context['posting_fields']['rack']['label']['text'] = $txt['warehouse_rack'];
		$context['posting_fields']['rack']['input'] = [
			'type' => 'select',
			'attributes' => [
				'id' => 'rack',
			],
			'options' => [],
		];

		foreach ($racks as $id => $rack) {
			$context['posting_fields']['rack']['input']['options'][$rack['title']] = [
				'value'    => $id,
				'selected' => $id == ($_REQUEST['rack'] ?? 1),
			];
		}

		$this->prepareEditor();

		loadTemplate('Post');

		loadTemplate('Warehouse/Post', ['admin', 'warehouse']);

		$context['sub_template'] = 'add';

		if (isset($_REQUEST['post'])) {
			$this->post();
		}
	}

	public function edit(int $box): void
	{
		global $context, $txt;

		if (! $this->isCanEdit())
			return;

		$racks = (new Rack())->getAll();

		$context['robot_no_index'] = true;

		$context['canonical_url'] = WH_BASE_URL . ';box=' . $box . ';sa=edit';

		$context['page_title'] = WH_NAME . ' - ' . $racks[$context['wh_box']['rack']['id']]['title'] . ' - ' . $txt['warehouse_edit_box'];

		$context['linktree'][] = [
			'url'  => WH_BASE_URL . ';rack=' . $context['wh_box']['rack']['id'],
			'name' => $context['wh_box']['rack']['title'],
		];

		$context['linktree'][] = [
			'url'  => WH_BASE_URL . ';box=' . $box,
			'name' => $context['wh_box']['title'],
		];

		$context['linktree'][] = [
			'name' => $txt['warehouse_edit_box'],
		];

		$context['posting_fields']['subject'] = ['no'];

		$context['posting_fields']['title']['label']['text'] = $txt['warehouse_box_title'];
		$context['posting_fields']['title']['input'] = [
			'type' => 'text',
			'attributes' => [
				'size'      => '100%',
				'maxlength' => 255,
				'required'  => true,
				'value'     => $_REQUEST['title'] ?? $context['wh_box']['title'] ?? '',
			]
		];

		$context['posting_fields']['rack']['label']['text'] = $txt['warehouse_rack'];
		$context['posting_fields']['rack']['input'] = [
			'type' => 'select',
			'attributes' => [
				'id' => 'rack',
			],
			'options' => [],
		];

		foreach ($racks as $id => $rack) {
			$context['posting_fields']['rack']['input']['options'][$rack['title']] = [
				'value'    => $id,
				'selected' => $id == ($_REQUEST['rack'] ?? $context['wh_box']['rack']['id'] ?? 1),
			];
		}

		$this->prepareEditor();

		loadTemplate('Post');

		loadTemplate('Warehouse/Post', ['admin', 'warehouse']);

		$context['sub_template'] = 'edit';

		if (isset($_REQUEST['update'])) {
			$this->update();
		}
	}

	public function remove(int $box): void
	{
		global $context, $sourcedir;

		if (! $this->isCanEdit())
			return;

		(new Cleaner())->removeBoxes([$box]);

		$this->decrement('racks', $context['wh_box']['rack']['id'], 'num_boxes');

		$this->logAction('remove_box', ['box' => $box]);

		if (empty($context['wh_box']['things']))
			return;

		require_once $sourcedir . '/ManageAttachments.php';

		removeAttachments(['id_attach' => array_keys($context['wh_box']['things'])]);
	}

	public function post(): void
	{
		global $txt, $user_info, $smcFunc;

		$rack        = filter_input(INPUT_POST, 'rack', FILTER_VALIDATE_INT);
		$title       = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (empty($rack) || empty($title) || empty($_FILES['things'])) {
			fatal_error($txt['warehouse_empty_data'], false);
		}

		$data = [
			'rack_id'     => $rack,
			'owner_id'    => $user_info['id'],
			'title'       => $title,
			'description' => $description,
			'created_at'  => time(),
			'status'      => (int) allowedTo('warehouse_manage_boxes_any'),
		];

		$id = $smcFunc['db_insert']('',
			'{db_prefix}warehouse_boxes',
			[
				'rack_id'     => 'int',
				'owner_id'    => 'int',
				'title'       => 'string',
				'description' => 'string',
				'created_at'  => 'int',
				'status'      => 'int',
			],
			$data,
			['id'],
			1
		);

		$link = sprintf(/** @lang text */ '<a href="%s;box=%s">%s</a>', WH_BASE_URL, $id, $title);
		$this->logAction('add_box', ['box' => $link]);

		$num_things = (new Thing())->add($id);

		if ($num_things) {
			$this->increment('racks', $rack, 'num_boxes');
			$this->increment('boxes', $id, 'num_things', $num_things);

			redirectexit('action=' . WH_ACTION . ';box=' . $id);
		}

		redirectexit('action=' . WH_ACTION . ';rack=' . $rack);
	}

	public function update(): void
	{
		global $txt, $context, $smcFunc, $sourcedir;

		$rack        = filter_input(INPUT_POST, 'rack', FILTER_VALIDATE_INT);
		$title       = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
		$description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

		if (empty($rack) || empty($title) || empty($_FILES['things'])) {
			fatal_error($txt['warehouse_empty_data'], false);
		}

		$data = [
			'id'          => $context['wh_box']['id'],
			'rack_id'     => $rack,
			'title'       => $title,
			'description' => $description,
			'updated_at'  => time(),
			'status'      => (int) allowedTo('warehouse_manage_boxes_any'),
		];

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}warehouse_boxes
			SET rack_id = {int:rack_id}, title = {string:title}, description = {string:description}, updated_at = {int:updated_at}, status = {int:status}
			WHERE id = {int:id}',
			$data,
		);

		$link = sprintf(/** @lang text */ '<a href="%s;box=%s">%s</a>', WH_BASE_URL, $context['wh_box']['id'], $context['wh_box']['title']);
		$this->logAction('edit_box', ['box' => $link]);

		// Update things
		$itemsToDelete = array_diff(array_column($context['wh_box']['things'], 'id'), $_REQUEST['things'] ?? []);

		if (! empty($itemsToDelete)) {
			require_once $sourcedir . '/ManageAttachments.php';

			removeAttachments(['id_attach' => $itemsToDelete]);

			$this->decrement('boxes', $context['wh_box']['id'], 'num_things', count($itemsToDelete));
		}

		// Add new things
		$num_things = (new Thing())->add($context['wh_box']['id']);

		if ($num_things) {
			$this->increment('racks', $rack, 'num_boxes');
			$this->increment('boxes', $context['wh_box']['id'], 'num_things', $num_things);
		}

		redirectexit('action=' . WH_ACTION . ';box=' . $context['wh_box']['id']);
	}

	public function getContent(int $box): array
	{
		global $smcFunc, $txt, $context, $scripturl;

		$request = $smcFunc['db_query']('', '
			SELECT wb.id, wb.rack_id, wb.owner_id, wb.title, wb.description, wb.num_views, wb.created_at, wb.updated_at, wb.status,
				wr.title AS rack_title, COALESCE(mem.real_name, {string:guest}) AS poster_name
			FROM {db_prefix}warehouse_boxes AS wb
				LEFT JOIN {db_prefix}warehouse_racks AS wr ON (wb.rack_id = wr.id)
				LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = wb.owner_id)
			WHERE wb.id = {int:box}
			LIMIT 1',
			[
				'guest' => $txt['guest_title'],
				'box'   => $box,
			]
		);

		if (empty($smcFunc['db_num_rows']($request))) {
			$context['error_link'] = WH_BASE_URL;

			$txt['back'] = $txt['warehouse_see_other_boxes'];

			fatal_lang_error('warehouse_box_not_found', false, null, 404);
		}

		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			if (empty($row['status']) && empty(allowedTo('warehouse_manage_boxes_any'))) {
				$context['error_link'] = WH_BASE_URL;

				$txt['back'] = $txt['warehouse_see_other_boxes'];

				fatal_lang_error('warehouse_box_not_yet_approved', false, null, 404);
			}

			$data = [
				'id'          => (int) $row['id'],
				'title'       => $row['title'],
				'description' => $row['description'],
				'num_views'   => (int) $row['num_views'],
				'created_at'  => timeformat($row['created_at']),
				'updated_at'  => empty($row['updated_at']) ? $txt['never'] : timeformat($row['updated_at']),
				'things'      => $this->getThings($box),
				'is_own'      => $this->isOwn((int) $row['owner_id']),
				'status'      => (int) $row['status'],
				'rack'        => [
					'id'    => (int) $row['rack_id'],
					'title' => $row['rack_title'],
					'url'   => WH_BASE_URL . ';rack=' . $row['rack_id'],
				],
				'poster'      => [
					'id'   => (int) $row['owner_id'],
					'name' => $row['poster_name'],
					'url'  => $scripturl . '?action=profile;u=' . $row['owner_id'],
				],
			];
		}

		$smcFunc['db_free_result']($request);

		if (
			isset($data['status']) && $data['status'] === 2
			&& (! $data['is_own'] && ! allowedTo('warehouse_manage_boxes_any'))
		) {
			$data = [];
		}

		return $data ?? [];
	}

	public function getThings(int $box): array
	{
		global $smcFunc, $txt, $scripturl;

		$request = $smcFunc['db_query']('', '
			SELECT a.id_attach, a.id_thumb, a.attachment_type, a.filename, a.file_hash, a.fileext, a.size, a.width, a.downloads, a.mime_type,
				wt.id, wt.created_at, wt.requested_at
			FROM {db_prefix}attachments AS a
				INNER JOIN {db_prefix}warehouse_things AS wt ON (a.id_attach = wt.attach_id)
			WHERE wt.box_id = {int:box}',
			[
				'box' => $box,
			]
		);

		$things = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			if (empty($row['id_thumb']) && $row['width'])
				continue;

			$things[$row['id_attach']] = [
				'id'           => (int) $row['id_attach'],
				'id_thumb'     => (int) $row['id_thumb'],
				'url'          => $scripturl . '?action=dlattach;box=' . $box . ';attach=' . $row['id_attach'],
				'width'        => $row['width'],
				'name'         => $row['filename'],
				'hash'         => $row['file_hash'],
				'ext'          => $row['fileext'],
				'size'         => $this->getSize((int) $row['size']),
				'downloads'    => (int) $row['downloads'],
				'mime'         => $row['mime_type'],
				'created_at'   => timeformat($row['created_at']),
				'requested_at' => empty($row['requested_at']) ? $txt['never'] : timeformat($row['requested_at']),
			];

			if ($row['width']) {
				$things[$row['id_attach']]['thumb_url'] = $scripturl . '?action=dlattach;box=' . $box . ';attach=' . ($row['id_attach'] + 1);
			}
		}

		$smcFunc['db_free_result']($request);

		return $things;
	}

	private function getSize(int $size): string
	{
		global $txt;

		return $size < 1024000
			? round($size / 1024, 2) . ' ' . $txt['kilobyte']
			: round($size / 1024 / 1024, 2) . ' ' . $txt['megabyte'];
	}

	private function updateNumViews(int $box): void
	{
		global $user_info;

		if (empty($user_info['possibly_robot']) && (empty($_SESSION['last_read_wh_box']) || $_SESSION['last_read_wh_box'] != $box)) {
			$this->increment('boxes', $box, 'num_views');

			$_SESSION['last_read_wh_box'] = $box;
		}
	}

	private function isCanEdit(): bool
	{
		global $context;

		return (
			allowedTo('warehouse_manage_boxes_own') && $context['wh_box']['is_own']
		) || allowedTo('warehouse_manage_boxes_any');
	}
}
