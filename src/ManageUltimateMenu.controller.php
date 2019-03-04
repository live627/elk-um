<?php

/**
 * @package   Ultimate Menu mod
 * @version   1.1.1
 * @author    John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license   http://opensource.org/licenses/MIT MIT
 */

if (!defined('ELK'))
	die('No access...');

class UltimateMenuController extends Action_Controller
{
	private $um;

	public function action_index()
	{
		global $context, $txt;

		$subActions = array(
			'manmenu' => array($this, 'action_manmenu', 'permission' => 'admin_forum'),
			'addbutton' => array($this, 'action_addbutton', 'permission' => 'admin_forum'),
			'savebutton' => array($this, 'action_savebutton', 'permission' => 'admin_forum'),
		);

		// Your activity will end here if you don't have permission.
		$action = new Action();

		// db functions are here
		require_once(SUBSDIR . '/UltimateMenu.subs.php');

		loadTemplate('ManageUltimateMenu');
		$um = new UltimateMenu;

		// Set the page title
		$context['page_title'] = $txt['admin_menu_title'];

		// Load up all the tabs...
		$context[$context['admin_menu_name']]['tab_data'] = array(
			'title' => &$txt['admin_menu'],
			'description' => $txt['admin_menu_desc'],
			'tabs' => array(
				'manmenu' => array(
					'description' => $txt['admin_manage_menu_desc'],
				),
				'addbutton' => array(
					'description' => $txt['admin_menu_add_button_desc'],
				),
			),
		);

		// Default to sub-action 'manmenu' if they have asked for something crazy like
		$subAction = $action->initialize($subActions, 'manmenu');
		$context['sub_action'] = $subAction;

		// Call the right function
		$action->dispatch($subAction);
	}

	public function action_manmenu()
	{
		global $context, $txt, $scripturl;

		// Get rid of all of em!
		if (!empty($_POST['removeAll']))
		{
			checkSession();
			$um->deleteallButtons();
			$um->rebuildMenu();
			redirectexit('action=admin;area=umen');
		}
		// User pressed the 'remove selection button'.
		elseif (!empty($_POST['removeButtons']) && !empty($_POST['remove']) && is_array($_POST['remove']))
		{
			checkSession();

			// Make sure every entry is a proper integer.
			foreach ($_POST['remove'] as $index => $page_id)
				$_POST['remove'][(int) $index] = (int) $page_id;

			// Delete the page(s)!
			$um->deleteButton($_POST['remove']);
			$um->rebuildMenu();
			redirectexit('action=admin;area=umen');
		}
		// Changing the status?
		elseif (isset($_POST['save']))
		{
			checkSession();

			foreach ($um->total_getMenu() as $item)
			{
				$status = !empty($_POST['status'][$item['id_button']]) ? 'active' : 'inactive';
				if ($status != $item['status'])
					$um->updateButton_status($item['id_button'], $status);
			}

			$um->rebuildMenu();

			redirectexit('action=admin;area=umen');
		}
		// New item?
		elseif (isset($_POST['new']))
			redirectexit('action=admin;area=umen;sa=addbutton');

		$button_names = $um->getButtonNames();
		$listOptions = array(
			'id' => 'menu_list',
			'items_per_page' => 20,
			'base_href' => $scripturl . '?action=admin;area=umen;sa=manmenu',
			'default_sort_col' => 'name',
			'default_sort_dir' => 'desc',
			'get_items' => array(
				'function' => array($um, 'list_getMenu'),
			),
			'get_count' => array(
				'function' => array($um, 'list_getNumButtons'),
			),
			'no_items_label' => $txt['um_menu_no_buttons'],
			'columns' => array(
				'name' => array(
					'header' => array(
						'value' => $txt['um_menu_button_name'],
					),
					'data' => array(
						'db' => 'name',
					),
					'sort' => array(
						'default' => 'name',
						'reverse' => 'name DESC',
					),
				),
				'type' => array(
					'header' => array(
						'value' => $txt['um_menu_button_type'],
					),
					'data' => array(
						'function' => function ($rowData) use ($txt)
						{
							return $txt['um_menu_' . $rowData['type'] . '_link'];
						},
					),
					'sort' => array(
						'default' => 'type',
						'reverse' => 'type DESC',
					),
				),
				'position' => array(
					'header' => array(
						'value' => $txt['um_menu_button_position'],
					),
					'data' => array(
						'function' => function ($rowData) use ($txt, $button_names)
						{
							return sprintf(
								'%s %s',
								$txt['um_menu_' . $rowData['position']],
								isset($button_names[$rowData['parent']])
									? $button_names[$rowData['parent']]
									: ucwords($rowData['parent'])
							);
						},
					),
					'sort' => array(
						'default' => 'position',
						'reverse' => 'position DESC',
					),
				),
				'link' => array(
					'header' => array(
						'value' => $txt['um_menu_button_link'],
					),
					'data' => array(
						'db_htmlsafe' => 'link',
					),
					'sort' => array(
						'default' => 'link',
						'reverse' => 'link DESC',
					),
				),
				'status' => array(
					'header' => array(
						'value' => $txt['um_menu_button_active'],
						'class' => 'centertext',
					),
					'data' => array(
						'function' => function ($rowData) use ($txt)
						{
							return sprintf(
								'<input type="checkbox" name="status[%1$s]" id="status_%1$s" value="%1$s"%2$s />',
								$rowData['id_button'],
								$rowData['status'] == 'inactive' ? '' : ' checked="checked"'
							);
						},
						'class' => 'centertext',
					),
					'sort' => array(
						'default' => 'status',
						'reverse' => '  status DESC',
					),
				),
				'actions' => array(
					'header' => array(
						'value' => $txt['um_menu_actions'],
						'class' => 'centertext',
					),
					'data' => array(
						'function' => function ($rowData) use ($txt)
						{
							return sprintf(
								'<a href="%s?action=admin;area=umen;sa=addbutton;edit;in=%d">%s</a>',
								$scripturl,
								$rowData['id_button'],
								$txt['modify']
							);
						},
						'class' => 'centertext',
					),
				),
				'check' => array(
					'header' => array(
						'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						'class' => 'centertext',
					),
					'data' => array(
						'sprintf' => array(
							'format' => '<input type="checkbox" name="remove[]" value="%d" class="input_check" />',
							'params' => array(
								'id_button' => false,
							),
						),
						'class' => 'centertext',
					),
				),
			),
			'form' => array(
				'href' => $scripturl . '?action=admin;area=umen;sa=manmenu',
			),
			'additional_rows' => array(
				array(
					'position' => 'below_table_data',
					'value' => '
						<input type="submit" name="removeButtons" value="' . $txt['um_menu_remove_selected'] . '" onclick="return confirm(\'' . $txt['um_menu_remove_confirm'] . '\');" class="button_submit" />
						<input type="submit" name="removeAll" value="' . $txt['um_menu_remove_all'] . '" onclick="return confirm(\'' . $txt['um_menu_remove_all_confirm'] . '\');" class="button_submit" />
						<input type="submit" name="new" value="' . $txt['um_admin_add_button'] . '" class="button_submit" />
						<input type="submit" name="save" value="' . $txt['save'] . '" class="button_submit" />',
					'class' => 'righttext',
				),
			),
		);

		require_once(SUBSDIR . '/GenericList.class.php');
		createList($listOptions);

		$context['sub_template'] = 'show_list';
		$context['default_list'] = 'menu_list';
	}

	public function action_savebutton()
	{
		global $context, $txt;

		if (isset($_POST['submit']))
		{
			$post_errors = array();
			$required_fields = array(
				'name',
				'link',
				'parent',
			);

			// Make sure we grab all of the content
			$menu_entry = array(
				'id' => filter_input(INPUT_GET, 'in', FILTER_SANITIZE_NUMBER_INT),
				'name' => filter_input(INPUT_POST, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
				'position' => isset($_POST['position']) ? $_POST['position'] : 'before',
				'type' => isset($_POST['type']) ? $_POST['type'] : 'forum',
				'link' => isset($_POST['link']) ? $_POST['link'] : '',
				'permissions' => isset($_POST['permissions'])
					? implode(',', array_intersect($_POST['permissions'], array_keys(list_groups(-3, 1))))
					: '1',
				'status' => isset($_POST['status']) ? $_POST['status'] : 'active',
				'parent' => isset($_POST['parent']) ? $_POST['parent'] : 'home',
				'target' => isset($_POST['target']) ? $_POST['target'] : '_self',
			);

			// These fields are required!
			foreach ($required_fields as $required_field)
				if (empty($menu_entry[$required_field]))
					$post_errors[$required_field] = 'um_menu_empty_' . $required_field;

			// Stop making numeric names!
			if (is_numeric($menu_entry['name']))
				$post_errors['name'] = 'um_menu_numeric';

			// Let's make sure you're not trying to make a name that's already taken.
			$check = $um->checkButton($menu_entry['id'], $menu_entry['name']);
			if ($check > 0)
				$post_errors['name'] = 'um_menu_mysql';

			// I see you made it to the final stage, my young padawan.
			if (empty($post_errors))
			{
				$um->saveButton($menu_entry);
				$um->rebuildMenu();

				// Before we leave, we must clear the cache. See, ElkArte
				// caches its menu at level 2 or higher.
				clean_cache('menu_buttons');

				redirectexit('action=admin;area=umen');
			}
			else
			{
				$context['page_title'] = $txt['um_menu_edit_title'];
				$context['post_error'] = $post_errors;
				$context['error_title'] = empty($menu_entry['id'])
					? 'um_menu_errors_create'
					: 'um_menu_errors_modify';
				$context['button_data'] = array(
					'name' => $menu_entry['name'],
					'type' => $menu_entry['type'],
					'target' => $menu_entry['target'],
					'position' => $menu_entry['position'],
					'link' => $menu_entry['link'],
					'parent' => $menu_entry['parent'],
					'permissions' => list_groups($menu_entry['permissions'], 1),
					'status' => $menu_entry['status'],
					'id' => $menu_entry['id'],
				);
			}
		}
	}

	/**
	 * Prepares theme context for the template.
	 */
	public function action_addbutton()
	{
		global $context, $txt;

		if (isset($_GET['in']))
		{
			$row = $um->fetchButton($_GET['in']);

			$context['button_data'] = array(
				'id' => (int) $_GET['in'],
				'name' => $row['name'],
				'target' => $row['target'],
				'type' => $row['type'],
				'position' => $row['position'],
				'permissions' => $um->list_groups($row['permissions'], 1),
				'link' => $row['link'],
				'status' => $row['status'],
				'parent' => $row['parent'],
				'edit' => true,
			);
		}
		else
		{
			$context['button_data'] = array(
				'name' => '',
				'link' => '',
				'target' => '_self',
				'type' => 'forum',
				'position' => 'before',
				'status' => '1',
				'permissions' => $um->list_groups('-3', 1),
				'parent' => 'home',
				'id' => 0,
			);

			$context['page_title'] = $txt['um_menu_add_title'];
		}
	}
}
