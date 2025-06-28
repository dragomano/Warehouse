<?php

if (file_exists(dirname(__FILE__) . '/SSI.php') && ! defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (! defined('SMF'))
	die('<b>Error:</b> Cannot install - please verify that you put this file in the same place as SMF\'s index.php and SSI.php files.');

if (version_compare(PHP_VERSION, '8.0', '<')) {
	die('This mod needs PHP 8.0 or greater. You will not be able to install/use this mod. Please, contact your host and ask for a php upgrade.');
}

if (SMF === 'SSI' && empty($user_info['is_admin']))
	die('Admin privileges required.');

$tables[] = array(
	'name' => 'warehouse_boxes',
	'columns' => array(
		array(
			'name' => 'id',
			'type' => 'int',
			'size' => 10,
			'unsigned' => true,
			'auto' => true
		),
		array(
			'name' => 'rack_id',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'owner_id',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'title',
			'type' => 'varchar',
			'size' => 255,
		),
		array(
			'name' => 'description',
			'type' => 'text',
			'null' => true,
		),
		array(
			'name' => 'num_views',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'num_things',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'created_at',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'updated_at',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'status',
			'type' => 'tinyint',
			'size' => 3,
			'default' => 1,
			'unsigned' => true,
		),
	),
	'indexes' => array(
		array(
			'type' => 'primary',
			'columns' => array('id')
		)
	)
);

$tables[] = array(
	'name' => 'warehouse_racks',
	'columns' => array(
		array(
			'name' => 'id',
			'type' => 'int',
			'size' => 10,
			'unsigned' => true,
			'auto' => true
		),
		array(
			'name' => 'title',
			'type' => 'varchar',
			'size' => 255,
		),
		array(
			'name' => 'description',
			'type' => 'text',
			'null' => true,
		),
		array(
			'name' => 'num_boxes',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'rack_order',
			'type' => 'smallint',
			'size' => 6,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'status',
			'type' => 'tinyint',
			'size' => 4,
			'default' => 1,
			'unsigned' => true,
		),
	),
	'indexes' => array(
		array(
			'type' => 'primary',
			'columns' => array('id')
		)
	)
);

$tables[] = array(
	'name' => 'warehouse_things',
	'columns' => array(
		array(
			'name' => 'id',
			'type' => 'int',
			'size' => 10,
			'unsigned' => true,
			'auto' => true
		),
		array(
			'name' => 'attach_id',
			'type' => 'int',
			'size' => 10,
			'unsigned' => true,
		),
		array(
			'name' => 'box_id',
			'type' => 'int',
			'size' => 10,
			'unsigned' => true,
		),
		array(
			'name' => 'owner_id',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'created_at',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
		array(
			'name' => 'requested_at',
			'type' => 'int',
			'size' => 10,
			'default' => 0,
			'unsigned' => true,
		),
	),
	'indexes' => array(
		array(
			'type' => 'primary',
			'columns' => array('id')
		)
	)
);

foreach ($tables as $table) {
	$smcFunc['db_create_table']('{db_prefix}' . $table['name'], $table['columns'], $table['indexes']);
}

if (SMF === 'SSI')
	echo 'Database changes are complete!';
