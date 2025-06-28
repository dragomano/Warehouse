<?php declare(strict_types=1);

/**
 * Cleaner.php
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

class Cleaner
{
	public function __toString(): string
	{
		return 'Please, vacate the premises, I need to clean!';
	}

	/**
	 * @hook integrate_weekly_maintenance
	 */
	public function cleanStorage(): void
	{
		$this->removeEmptyBoxes();

		$this->updateNumBoxes();

		$this->updateNumThings();
	}

	public function removeThings(array $items): void
	{
		global $smcFunc;

		if (empty($items))
			return;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}warehouse_things
			WHERE attach_id IN ({array_int:items})',
			[
				'items' => $items,
			]
		);
	}

	public function removeBoxes(array $boxes): void
	{
		global $smcFunc;

		if (empty($boxes))
			return;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}warehouse_boxes
			WHERE id IN ({array_int:boxes})',
			[
				'boxes' => $boxes,
			]
		);
	}

	public function removeRacks(array $racks): void
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}warehouse_racks
			WHERE id IN ({array_int:racks})',
			[
				'racks' => $racks,
			]
		);
	}

	protected function removeEmptyBoxes(): void
	{
		global $smcFunc;

		$smcFunc['db_query']('', '
			DELETE FROM {db_prefix}warehouse_boxes
			WHERE num_things = {int:num_things}
				AND status = {int:status}',
			[
				'num_things' => 0,
				'status'     => 1,
			]
		);
	}

	protected function updateNumBoxes(): void
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', /** @lang text */ '
			SELECT wr.id, COUNT(wb.id) AS num_boxes
			FROM {db_prefix}warehouse_racks wr
				LEFT JOIN {db_prefix}warehouse_boxes wb ON (wb.rack_id = wr.id)
			GROUP BY wr.id
			ORDER BY wr.id',
			[]
		);

		$racks = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$racks[$row['id']] = $row['num_boxes'];
		}

		$smcFunc['db_free_result']($request);

		if (empty($racks))
			return;

		$line = '';
		foreach ($racks as $rack_id => $num_boxes) {
			$line .= ' WHEN id = ' . $rack_id . ' THEN ' . $num_boxes;
		}

		$smcFunc['db_query']('', /** @lang text */ '
			UPDATE {db_prefix}warehouse_racks
			SET num_boxes = CASE ' . $line . ' ELSE num_boxes END
			WHERE id IN ({array_int:racks})',
			[
				'racks' => array_keys($racks)
			]
		);
	}

	protected function updateNumThings(): void
	{
		global $smcFunc;

		$request = $smcFunc['db_query']('', /** @lang text */ '
			SELECT wb.id, COUNT(wt.id) AS num_things
			FROM {db_prefix}warehouse_boxes wb
				LEFT JOIN {db_prefix}warehouse_things wt ON (wt.box_id = wb.id)
			GROUP BY wb.id
			ORDER BY wb.id',
			[]
		);

		$boxes = [];
		while ($row = $smcFunc['db_fetch_assoc']($request)) {
			$boxes[$row['id']] = $row['num_things'];
		}

		$smcFunc['db_free_result']($request);

		if (empty($boxes))
			return;

		$line = '';
		foreach ($boxes as $box_id => $num_things) {
			$line .= ' WHEN id = ' . $box_id . ' THEN ' . $num_things;
		}

		$smcFunc['db_query']('', /** @lang text */ '
			UPDATE {db_prefix}warehouse_boxes
			SET num_things = CASE ' . $line . ' ELSE num_things END
			WHERE id IN ({array_int:boxes})',
			[
				'boxes' => array_keys($boxes),
			]
		);
	}
}
