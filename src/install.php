<?php

/**
 * @package Ultimate Menu mod
 * @version 1.0
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/ISC ISC
 */

// If we have found SSI.php and we are outside of ElkArte, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('ELK')) // If we are outside ElkArte and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as ElkArte\'s SSI.php.');

$db = database();
$dbtbl = db_table();

$tables = array(
	array(
		'name' => 'um_menu',
		'columns' => array(
			array(
				'name' => 'id_button',
				'type' => 'smallint',
				'size' => 5,
				'unsigned' => true,
				'auto' => true,
			),
			array(
				'name' => 'name',
				'type' => 'varchar',
				'size' => 65,
			),
			array(
				'name' => 'slug',
				'type' => 'varchar',
				'size' => 80,
			),
			array(
				'name' => 'type',
				'type' => 'enum(\'forum\',\'external\')',
				'default' => 'forum',
			),
			array(
				'name' => 'target',
				'type' => 'enum(\'_self\',\'_blank\')',
				'default' => '_self',
			),
			array(
				'name' => 'position',
				'type' => 'varchar',
				'size' => 65,
			),
			array(
				'name' => 'link',
				'type' => 'varchar',
				'size' => 255,
			),
			array(
				'name' => 'status',
				'type' => 'enum(\'active\',\'inactive\')',
				'default' => 'active',
			),
			array(
				'name' => 'permissions',
				'type' => 'varchar',
				'size' => 255,
			),
			array(
				'name' => 'parent',
				'type' => 'varchar',
				'size' => 65,
			),
		),
		'indexes' => array(
			array(
				'type' => 'primary',
				'columns' => array('id_button')
			),
		)
	)
);

foreach ($tables as $table)
	$dbtbl->db_create_table('{db_prefix}' . $table['name'], $table['columns'], $table['indexes'], array(), 'update');