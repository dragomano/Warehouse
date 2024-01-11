<?php declare(strict_types=1);

/**
 * Storekeeper.php
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

class Storekeeper
{
	public function __toString(): string
	{
		return 'Hi, I am just a storekeeper! And you?';
	}

	public function getListOptions(): array
	{
		global $modSettings, $scripturl, $context, $txt, $smcFunc;

		return [
			'id' => 'file_list',
			'items_per_page' => $modSettings['defaultMaxListItems'],
			'base_href' => $scripturl . '?action=admin;area=manageattachments;sa=browse;' . $context['browse_type'],
			'default_sort_col' => 'name',
			'no_items_label' => $txt['attachment_manager_attachments_no_entries'],
			'get_items' => [
				'function' => [$this, 'getFiles'],
			],
			'get_count' => [
				'function' => [$this, 'getNumFiles'],
			],
			'columns' => [
				'name' => [
					'header' => [
						'value' => $txt['attachment_name'],
					],
					'data' => [
						'function' => function ($entry) use ($scripturl, $context, $smcFunc) {
							$link = '<a href="' . sprintf('%1$s?action=dlattach;box=%2$d;attach=%3$d', $scripturl, $entry['box'], $entry['id_attach']) . '"';

							if (! empty($entry['width']) && ! empty($entry['height']))
								$link .= sprintf(' onclick="return reqWin(this.href' . ($entry['attachment_type'] == 1 ? '' : ' + \';image\'') . ', %1$d, %2$d, true);"', $entry['width'] + 20, $entry['height'] + 20);

							$link .= sprintf('>%1$s</a>', preg_replace('~&amp;#(\\\\d{1,7}|x[0-9a-fA-F]{1,6});~', '&#\\\\1;', $smcFunc['htmlspecialchars']($entry['filename'])));

							if (! empty($entry['width']) && ! empty($entry['height']))
								$link .= ' <span class="smalltext">' . $entry['width'] . 'x' . $entry['height'] . '</span>';

							return $link;
						},
					],
					'sort' => [
						'default' => 'a.filename',
						'reverse' => 'a.filename DESC',
					],
				],
				'filesize' => [
					'header' => [
						'value' => $txt['attachment_file_size'],
					],
					'data' => [
						'function' => function ($entry) use ($txt) {
							return sprintf('%1$s%2$s', round($entry['size'] / 1024, 2), ' ' . $txt['kilobyte']);
						},
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'a.size',
						'reverse' => 'a.size DESC',
					],
				],
				'member' => [
					'header' => [
						'value' => $txt['posted_by'],
					],
					'data' => [
						'function' => function ($entry) use ($smcFunc, $scripturl) {
							if (empty($entry['id_member']))
								return $smcFunc['htmlspecialchars']($entry['poster_name']);
							else
								return '<a href="' . $scripturl . '?action=profile;u=' . $entry['id_member'] . '">' . $entry['poster_name'] . '</a>';
						},
					],
					'sort' => [
						'default' => 'mem.real_name',
						'reverse' => 'mem.real_name DESC',
					],
				],
				'date' => [
					'header' => [
						'value' => $txt['date'],
					],
					'data' => [
						'function' => function ($entry) use ($txt) {
							$date = empty($entry['created_at']) ? $txt['never'] : timeformat($entry['created_at']);

							$date .= '<br>' . $txt['in'] . ' <a href="' . WH_BASE_URL . ';box=' . $entry['box'] . '">' . $entry['title'] . '</a>';

							return $date;
						},
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'wt.created_at',
						'reverse' => 'wt.created_at DESC',
					],
				],
				'downloads' => [
					'header' => [
						'value' => $txt['downloads'],
					],
					'data' => [
						'db' => 'downloads',
						'comma_format' => true,
						'class' => 'centertext',
					],
					'sort' => [
						'default' => 'a.downloads',
						'reverse' => 'a.downloads DESC',
					],
				],
				'check' => [
					'header' => [
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);">',
						'class' => 'centercol',
					],
					'data' => [
						'sprintf' => [
							'format' => '<input type="checkbox" name="remove[%1$d]">',
							'params' => [
								'id_attach' => false,
							],
						],
						'class' => 'centercol',
					],
				],
			],
			'form' => [
				'href' => $scripturl . '?action=admin;area=manageattachments;sa=remove;' . $context['browse_type'],
				'include_sort' => true,
				'include_start' => true,
				'hidden_fields' => [
					'type' => $context['browse_type'],
				],
			],
			'additional_rows' => [
				[
					'position' => 'above_column_headers',
					'value' => '<input type="submit" name="remove_submit" class="button you_sure" value="' . $txt['quickmod_delete_selected'] . '" data-confirm="' . $txt['confirm_delete_attachments'] . '">',
				],
				[
					'position' => 'below_table_data',
					'value' => '<input type="submit" name="remove_submit" class="button you_sure" value="' . $txt['quickmod_delete_selected'] . '" data-confirm="' . $txt['confirm_delete_attachments'] . '">',
				],
			],
		];
	}

	public function getFiles(int $start, int $items_per_page, string $sort): array
	{
		global $smcFunc, $txt;

		$request = $smcFunc['db_query']('', '
			SELECT a.id_attach, a.filename, a.file_hash, a.attachment_type, a.size, a.width, a.height, a.downloads,
				wt.created_at, wb.id AS box, wb.title, COALESCE(mem.real_name, {string:guest}) AS poster_name, mem.id_member
			FROM {db_prefix}attachments AS a
				INNER JOIN {db_prefix}warehouse_things AS wt ON (a.id_attach = wt.attach_id)
				INNER JOIN {db_prefix}warehouse_boxes AS wb ON (wt.box_id = wb.id)
				LEFT JOIN {db_prefix}members AS mem ON (wb.owner_id = mem.id_member)
			WHERE a.attachment_type = {int:type}
			ORDER BY {raw:sort}
			LIMIT {int:start}, {int:per_page}',
			[
				'guest'    => $txt['guest_title'],
				'type'     => 0,
				'sort'     => $sort,
				'start'    => $start,
				'per_page' => $items_per_page,
			]
		);

		$files = [];
		while ($row = $smcFunc['db_fetch_assoc']($request))
			$files[] = $row;

		$smcFunc['db_free_result']($request);

		return $files;
	}

	public function getNumFiles(): int
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', '
			SELECT COUNT(*) AS num_attach
			FROM {db_prefix}attachments AS a
				INNER JOIN {db_prefix}warehouse_things AS wt ON (a.id_attach = wt.attach_id)
				INNER JOIN {db_prefix}warehouse_boxes AS wb ON (wt.box_id = wb.id)
			WHERE a.attachment_type = {int:type}',
			[
				'type' => 0,
			]
		);

		[$num_files] = $smcFunc['db_fetch_row']($request);

		$smcFunc['db_free_result']($request);

		return (int) $num_files;
	}
}
