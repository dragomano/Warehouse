<?php

declare(strict_types = 1);

/**
 * app.php
 *
 * @package Warehouse
 * @link https://github.com/dragomano/Warehouse
 * @author Bugo <bugo@dragomano.ru>
 * @copyright 2023-2025 Bugo
 * @license https://opensource.org/licenses/MIT The MIT License
 *
 * @version 0.3
 */

if (! defined('SMF'))
	die('No direct access...');

require_once __DIR__ . '/Manager.php';

$manager = new Bugo\Warehouse\Manager();
$manager->tasks();
