<?php

/**
 * @package Ultimate Menu mod
 * @version 1.1.2
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

/**
 * Loads the um menu into the site menu
 * Ensures um is the last menu hook to execute menu changes
 *
 * @param array $menu_buttons
 */
function um_load_menu(&$menu_buttons)
{
	global $user_info, $scripturl, $modSettings;

	// Make damn sure we ALWAYS load last. Priority: 100!
	$hooks = explode(',', $modSettings['integrate_menu_buttons']);
	$hook = end($hooks);
	if (strpos($hook, 'um_load_menu') === false)
	{
		remove_integration_function('integrate_menu_buttons', 'um_load_menu');
		add_integration_function('integrate_menu_buttons', 'um_load_menu');
	}

	// Load the um items for inclusion
	$db_buttons = @unserialize($modSettings['um_menu']);

	// No um items, easy
	if (empty($db_buttons))
	{
		return $menu_buttons;
	}

	// Insert the items in to the site menu as defined in the um ACP
	reset($db_buttons);
	foreach ($db_buttons as $row)
	{
		// UM menu button basics
		$temp_menu = array(
			'title' => $row['name'],
			'href' => ($row['type'] === 'forum' ? $scripturl . '?' : '') . $row['link'],
			'target' => $row['target'],
			'show' => (allowedTo('admin_forum') || count(array_intersect($user_info['groups'], explode(',', $row['permissions']))) >= 1) && $row['status'] === 'active',
		);

		// Loop on the menu, find the item that we are supposed to add the button after, before, child of
		foreach ($menu_buttons as $area => &$info)
		{
			if ($area == $row['parent'])
			{
				if ($row['position'] === 'before' || $row['position'] === 'after')
				{
					if (array_key_exists($row['parent'], $menu_buttons))
					{
						$menu_buttons = elk_array_insert($menu_buttons, $row['parent'], array($row['slug'] => $temp_menu), $row['position'], true, true);
						break;
					}
				}

				if ($row['position'] === 'child_of')
				{
					$info['sub_buttons'][$row['slug']] = $temp_menu;
					break;
				}
			}

			if (isset($info['sub_buttons'][$row['parent']]))
			{
				if ($row['position'] === 'before' || $row['position'] === 'after')
				{
					$info['sub_buttons'] = elk_array_insert($info['sub_buttons'], $row['parent'], array($row['slug'] => $temp_menu), $row['position'], true, true);
					break;
				}

				if ($row['position'] === 'child_of')
				{
					$info['sub_buttons'][$row['parent']]['sub_buttons'][$row['slug']] = $temp_menu;
					break;
				}
			}
		}
	}
}

/**
 * Gets all membergroups and filters them according to the parameters.
 *
 * @param string $checked comma-seperated list of all id_groups to be checked (have a mark in the checkbox). Default is an empty array.
 * @param string $disallowed comma-seperated list of all id_groups that are skipped. Default is an empty array.
 * @param bool $inherited whether to filter out the inherited groups. Default is false.
 * @param null $permission
 * @param null $board_id
 * @return array all the membergroups filtered according to the parameters; empty array if something went wrong.
 */
function list_groups($checked, $disallowed = '', $inherited = false, $permission = null, $board_id = null)
{
	global $modSettings, $txt;

	$db = database();

	// We'll need this for loading up the names of each group.
	if (!loadLanguage('ManageBoards'))
	{
		loadLanguage('ManageBoards');
	}

	$checked = explode(',', $checked);
	$disallowed = explode(',', $disallowed);

	// Are we also looking up permissions?
	if ($permission !== null)
	{
		require_once(SUBSDIR . '/Members.subs.php');
		$member_groups = groupsAllowedTo($permission, $board_id);
		$disallowed = array_diff(array_keys(list_groups(-3)), $member_groups['allowed']);
	}

	$groups = array();

	if (!in_array(-1, $disallowed))
	{
		// Guests
		$groups[-1] = array(
			'id' => -1,
			'name' => $txt['parent_guests_only'],
			'checked' => in_array(-1, $checked) || in_array(-3, $checked),
			'is_post_group' => false,
		);
	}

	if (!in_array(0, $disallowed))
	{
		// Regular Members
		$groups[0] = array(
			'id' => 0,
			'name' => $txt['parent_members_only'],
			'checked' => in_array(0, $checked) || in_array(-3, $checked),
			'is_post_group' => false,
		);
	}

	// Load membergroups.
	$request = $db->query('', '
        SELECT
            group_name, id_group, min_posts
        FROM {db_prefix}membergroups
        WHERE id_group > {int:is_zero}' . (!$inherited ? '
            AND id_parent = {int:not_inherited}' : '') . (!$inherited && empty($modSettings['permission_enable_postgroups']) ? '
            AND min_posts = {int:min_posts}' : ''),
		array(
			'is_zero' => 0,
			'not_inherited' => -2,
			'min_posts' => -1,
		)
	);
	while ($row = $db->fetch_assoc($request))
	{
		if (!in_array($row['id_group'], $disallowed))
		{
			$groups[(int) $row['id_group']] = array(
				'id' => $row['id_group'],
				'name' => trim($row['group_name']),
				'checked' => in_array($row['id_group'], $checked) || in_array(-3, $checked),
				'is_post_group' => $row['min_posts'] != -1,
			);
		}
	}
	$db->free_result($request);

	asort($groups);

	return $groups;
}

/**
 * Admin Hook, integrate_admin_areas, called from Menu.subs
 * Used to add/modify admin menu areas
 *
 * @param mixed $admin_areas
 */
function um_admin_areas(&$admin_areas)
{
	global $txt;

	loadLanguage('ManageUltimateMenu');

	$admin_areas['config']['areas']['umen'] = array(
		'label' => $txt['um_admin_menu'],
		'file' => 'ManageUltimateMenu.controller.php',
		'controller' => 'Ultimate_Menu_Controller',
		'permission' => array('admin_forum'),
		'function' => 'action_index',
		'icon' => 'umen.png',
		'subsections' => array(
			'manmenu' => array($txt['um_admin_manage_menu'], ''),
			'addbutton' => array($txt['um_admin_add_button'], ''),
		),
	);
}

/**
 * Loads all UM items from the db
 *
 * @return array
 */
function total_getMenu()
{
	$db = database();

	$request = $db->query('', '
        SELECT
            id_button, name, target, type, position, link, status, permissions, parent
        FROM {db_prefix}um_menu');
	$um_menu = array();
	while ($row = $db->fetch_assoc($request))
	{
		$um_menu[] = $row;
	}

	return $um_menu;
}

/**
 * Createlist call back, used to display um entries
 *
 * @param int $start
 * @param int $items_per_page
 * @param string $sort
 * @return array
 */
function list_um_getMenu($start, $items_per_page, $sort)
{
	$db = database();

	$request = $db->query('', '
        SELECT
            id_button, name, slug, target, type, position, link, status, permissions, parent
        FROM {db_prefix}um_menu AS men
        ORDER BY {raw:sort}
        LIMIT {int:offset}, {int:limit}',
		array(
			'sort' => $sort,
			'offset' => $start,
			'limit' => $items_per_page,
		)
	);
	$um_menu = array();
	while ($row = $db->fetch_assoc($request))
	{
		$um_menu[] = $row;
	}

	return $um_menu;
}

/**
 * Createlist callback to determine the number of um items
 *
 * @return type
 */
function list_um_getNumButtons()
{
	$db = database();

	$request = $db->query('', '
        SELECT 
            COUNT(*)
        FROM {db_prefix}um_menu');
	list ($numButtons) = $db->fetch_row($request);
	$db->free_result($request);

	return $numButtons;
}

/**
 * Sets the serialized array of um into settings
 * Called whenever the menu structure is updated in the ACP
 */
function rebuild_um_menu()
{
	$db = database();

	$request = $db->query('', '
        SELECT *
        FROM {db_prefix}um_menu');
	$db_buttons = array();
	while ($row = $db->fetch_assoc($request))
	{
		$db_buttons[$row['id_button']] = $row;
	}
	$db->free_result($request);

	updateSettings(
		array(
			'um_menu' => serialize($db_buttons),
		)
	);
}

/**
 * Removes menu item(s) from the um system
 *
 * @param int[] $ids
 */
function delete_um_page($ids)
{
	$db = database();

	// Delete the page!
	$db->query('', '
        DELETE FROM {db_prefix}um_menu
        WHERE id_button IN ({array_int:button_list})',
		array(
			'button_list' => $ids,
		)
	);
}

/**
 * Changes the status of an um item from active to inactive
 *
 * @param int $id
 * @param string $status
 */
function update_um_page_status($id, $status)
{
	$db = database();

	$db->query('', '
        UPDATE {db_prefix}um_menu
        SET status = {string:status}
        WHERE id_button = {int:item}',
		array(
			'status' => $status,
			'item' => $id,
		)
	);
}

/**
 * Checks if there is an existing um id with the same name before saving
 *
 * @param int $id
 * @param string $name
 * @return int
 */
function check_um_name($id, $name)
{
	$db = database();

	// Let's make sure you're not trying to make a name that's already taken.
	$request = $db->query('', '
        SELECT 
        	id_button
        FROM {db_prefix}um_menu
        WHERE name = {string:name}
            AND id_button != {int:id}',
		array(
			'name' => $name,
			'id' => $id,
		)
	);
	$check = $db->num_rows($request);
	$db->free_result($request);

	return $check;
}

/**
 * Save a new or update and existing um item
 *
 * @param array $menu_entry
 */
function save_um_name($menu_entry)
{
	$db = database();

	// I see you made it to the final stage, my young padawan.
	if (!empty($menu_entry['id']))
	{
		$db->query('', '
            UPDATE {db_prefix}um_menu
            SET name = {string:name}, type = {string:type}, target = {string:target}, position = {string:position}, link = {string:link},
                status = {string:status}, permissions = {string:permissions}, parent = {string:parent}
            WHERE id_button = {int:id}',
			array(
				'id' => $menu_entry['id'],
				'name' => $menu_entry['name'],
				'type' => $menu_entry['type'],
				'target' => $menu_entry['target'],
				'position' => $menu_entry['position'],
				'link' => $menu_entry['link'],
				'status' => $menu_entry['status'],
				'permissions' => $menu_entry['permissions'],
				'parent' => $menu_entry['parent'],
			)
		);
	}
	else
	{
		$db->insert('insert', '{db_prefix}um_menu',
			array(
				'slug' => 'string', 'name' => 'string', 'type' => 'string', 'target' => 'string', 'position' => 'string',
				'link' => 'string', 'status' => 'string', 'permissions' => 'string', 'parent' => 'string',
			),
			array(
				md5($menu_entry['name']) . '-' . time(), $menu_entry['name'], $menu_entry['type'], $menu_entry['target'], $menu_entry['position'],
				$menu_entry['link'], $menu_entry['status'], $menu_entry['permissions'], $menu_entry['parent'],
			),
			array('id_button')
		);
	}
}

/**
 * Fetch a specific um item
 *
 * @param int $id
 * @return array
 */
function fetch_um_page($id)
{
	$db = database();

	$request = $db->query('', '
        SELECT
            name, slug, target, type, position, link, status, permissions, parent
        FROM {db_prefix}um_menu
        WHERE id_button = {int:button}
        LIMIT 1',
		array(
			'button' => $id,
		)
	);
	$row = $db->fetch_assoc($request);
	$db->free_result($request);

	return $row;
}

/**
 * Removes all um items
 */
function deleteall_um_pages()
{
	$db = database();

	$db->query('truncate_table', '
        TRUNCATE {db_prefix}um_menu'
	);
}

/**
 * Fetches the common name of an item given the slug
 * Needed when we have nested um items
 *
 * @param string $slug
 * @return string
 */
function common_um_name($slug)
{
	$db = database();

	$request = $db->query('', '
        SELECT
            name
        FROM {db_prefix}um_menu
        WHERE slug = {string:slug}
        LIMIT 1',
		array(
			'slug' => $slug,
		)
	);
	list ($name) = $db->fetch_row($request);
	$db->free_result($request);

	return $name;
}
