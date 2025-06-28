<?php declare(strict_types=1);

/**
 * Util.php
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

trait Util
{
	private function shortText(string $text, int $limit = 200): string
	{
		return shorten_subject(parse_bbc($text), $limit);
	}

	private function getJSON(): array
	{
		$data = file_get_contents('php://input');

		return json_decode($data, true) ?? [];
	}

	private function isOwn(int $owner_id): bool
	{
		global $context;

		return ($owner_id === $context['user']['id'] && allowedTo('warehouse_manage_boxes_own')) || allowedTo('warehouse_manage_boxes_any');
	}

	private function increment(string $entity, int $item, string $column, int $amount = 1): void
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			UPDATE {db_prefix}warehouse_{raw:entity}
			SET {raw:column} = CASE WHEN CAST({raw:column} AS SIGNED) + {int:amount} < 0 THEN 0 ELSE CAST({raw:column} AS SIGNED) + {int:amount} END
			WHERE id = {int:item}',
			[
				'entity' => $entity,
				'column' => $column,
				'amount' => $amount,
				'item'   => $item,
			]
		);
	}

	private function decrement(string $entity, int $item, string $column, int $amount = 1): void
	{
		$this->increment($entity, $item, $column, -$amount);
	}

	private function prepareEditor(string $entity = 'box'): void
	{
		global $sourcedir, $context;

		require_once($sourcedir . '/Subs-Editor.php');

		$editorOptions = [
			'id'           => 'description',
			'value'        => $_REQUEST['description'] ?? $context['wh_' . $entity]['description'] ?? '',
			'width'        => '100%',
			'preview_type' => 2,
			'required'     => true,
		];

		create_control_richedit($editorOptions);

		$context['post_box_name'] = $editorOptions['id'];
	}

	private function logAction(string $action, array $item): void
	{
		global $modSettings, $scripturl, $user_info;

		if (empty($modSettings['warehouse_log_actions']))
			return;

		logAction($action, array_merge(['name' => "<a href=\"$scripturl?action=profile;u=\"{$user_info['id']}\">{$user_info['name']}</a>"], $item));
	}
}
