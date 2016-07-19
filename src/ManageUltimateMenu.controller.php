<?php

/**
 * @package Ultimate Menu mod
 * @version 1.0
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

if (!defined('ELK'))
    die('No access...');

class Ultimate_Menu_Controller extends Action_Controller
{
    /**
     * Default action method, if a specific method wasn't
     * directly called. Simply forwards to menu.
     */
    public function action_index()
    {
        $this->action_menu();
    }

    /**
     * Action dispatcher for this class
     */
    public function action_menu()
    {
        global $context, $txt;

        // Actions baby
        require_once(SUBSDIR . '/Action.class.php');

        // But not much tonight
        $subActions = array(
            'manmenu' => array($this, 'action_ManageUltimateMenu', 'permission' => 'admin_forum'),
            'addbutton' => array($this, 'action_PrepareContext', 'permission' => 'admin_forum'),
            'savebutton' => array($this, 'action_SaveButton', 'permission' => 'admin_forum'),
        );

        // Your activity will end here if you don't have permission.
        $action = new Action();

        // db functions are here
        require_once(SUBSDIR . '/UltimateMenu.subs.php');

        loadTemplate('ManageUltimateMenu');

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

    public function action_ManageUltimateMenu()
    {
        global $context, $txt, $scripturl;

        // Get rid of all of em!
        if (!empty($_POST['removeAll'])) {
            checkSession();

            deleteall_um_pages();

            rebuild_um_menu();

            redirectexit('action=admin;area=umen');
        } // User pressed the 'remove selection button'.
        elseif (!empty($_POST['removeButtons']) && !empty($_POST['remove']) && is_array($_POST['remove'])) {
            checkSession();

            // Make sure every entry is a proper integer.
            foreach ($_POST['remove'] as $index => $page_id)
                $_POST['remove'][(int)$index] = (int)$page_id;

            // Delete the page(s)!
            delete_um_page($_POST['remove']);

            rebuild_um_menu();

            redirectexit('action=admin;area=umen');
        } // Changing the status?
        elseif (isset($_POST['save'])) {
            checkSession();

            foreach (total_getMenu() as $item) {
                $status = !empty($_POST['status'][$item['id_button']]) ? 'active' : 'inactive';
                if ($status != $item['status'])
                    update_um_page_status($item['id_button'], $status);
            }

            rebuild_um_menu();

            redirectexit('action=admin;area=umen');
        } // New item?
        elseif (isset($_POST['new']))
            redirectexit('action=admin;area=umen;sa=addbutton');

        loadLanguage('ManageBoards');

        // Our options for our list.
        $listOptions = array(
            'id' => 'menu_list',
            'items_per_page' => 20,
            'base_href' => $scripturl . '?action=admin;area=umen;sa=manmenu',
            'default_sort_col' => 'id_button',
            'default_sort_dir' => 'desc',
            'get_items' => array(
                'function' => array($this, 'list_getMenu'),
            ),
            'get_count' => array(
                'function' => array($this, 'list_getNumButtons'),
            ),
            'no_items_label' => $txt['um_menu_no_buttons'],
            'columns' => array(
                'id_button' => array(
                    'header' => array(
                        'value' => $txt['um_menu_button_id'],
                        'class' => 'centertext',
                    ),
                    'data' => array(
                        'db' => 'id_button',
                        'class' => 'centertext',
                    ),
                    'sort' => array(
                        'default' => 'men.id_button',
                        'reverse' => 'men.id_button DESC',
                    ),
                ),
                'name' => array(
                    'header' => array(
                        'value' => $txt['um_menu_button_name'],
                    ),
                    'data' => array(
                        'db_htmlsafe' => 'name',
                    ),
                    'sort' => array(
                        'default' => 'men.name',
                        'reverse' => 'men.name DESC',
                    ),
                ),
                'type' => array(
                    'header' => array(
                        'value' => $txt['um_menu_button_type'],
                    ),
                    'data' => array(
                        'function' => create_function('$rowData', '
                            global $txt;

                            return $txt[$rowData[\'type\'] . \'_link\'];
                        '),
                    ),
                    'sort' => array(
                        'default' => 'men.type',
                        'reverse' => 'men.type DESC',
                    ),
                ),
                'position' => array(
                    'header' => array(
                        'value' => $txt['um_menu_button_position'],
                    ),
                    'data' => array(
                        'function' => create_function('$rowData', '
                            global $txt;

                            // Dont show the stub name if we can find the parent name
                            $check = common_um_name($rowData[\'parent\']);
                            $name = !empty($check) ? $check : $rowData[\'parent\'];

                            return $txt[\'mboards_order_\' . $rowData[\'position\']] . \' \' . ucwords($name);
                        '),
                    ),
                    'sort' => array(
                        'default' => 'men.position',
                        'reverse' => 'men.position DESC',
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
                        'default' => 'men.link',
                        'reverse' => 'men.link DESC',
                    ),
                ),
                'status' => array(
                    'header' => array(
                        'value' => $txt['um_menu_button_active'],
                        'class' => 'centertext',
                    ),
                    'data' => array(
                        'function' => create_function('$rowData', '
                            global $txt;

                            $isChecked = $rowData[\'status\'] === \'inactive\' ? \'\' : \' checked="checked"\';
                            return sprintf(\'<span>%3$s</span>&nbsp;<input type="checkbox" name="status[%1$s]" id="status_%1$s" value="%1$s"%2$s />\', $rowData[\'id_button\'], $isChecked, $txt[$rowData[\'status\']], $rowData[\'status\']);
                        '),
                        'class' => 'centertext',
                    ),
                    'sort' => array(
                        'default' => 'men.status',
                        'reverse' => 'men.status DESC',
                    ),
                ),
                'actions' => array(
                    'header' => array(
                        'value' => $txt['um_menu_actions'],
                        'class' => 'centertext',
                    ),
                    'data' => array(
                        'sprintf' => array(
                            'format' => '<a href="' . $scripturl . '?action=admin;area=umen;sa=addbutton;edit;in=%1$d">' . $txt['modify'] . '</a>',
                            'params' => array(
                                'id_button' => false,
                            ),
                        ),
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
                            'format' => '<input type="checkbox" name="remove[]" value="%1$d" class="input_check" />',
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

    public function action_SaveButton()
    {
        global $context, $txt;

        if (isset($_REQUEST['submit'])) {
            $post_errors = array();
            $required_fields = array(
                'name',
                'link',
                'parent',
            );

            // Make sure we grab all of the content
            $menu_entry = array();
            $menu_entry['id'] = isset($_REQUEST['in']) ? (int)$_REQUEST['in'] : 0;
            $menu_entry['name'] = isset($_REQUEST['name']) ? $_REQUEST['name'] : '';
            $menu_entry['position'] = isset($_REQUEST['position']) ? $_REQUEST['position'] : 'before';
            $menu_entry['type'] = isset($_REQUEST['type']) ? $_REQUEST['type'] : 'forum';
            $menu_entry['link'] = isset($_REQUEST['link']) ? $_REQUEST['link'] : '';
            $menu_entry['permissions'] = isset($_REQUEST['permissions']) ? implode(',', array_intersect($_REQUEST['permissions'], array_keys(list_groups(-3, 1)))) : '1';
            $menu_entry['status'] = isset($_REQUEST['status']) ? $_REQUEST['status'] : 'active';
            $menu_entry['parent'] = isset($_REQUEST['parent']) ? $_REQUEST['parent'] : 'home';
            $menu_entry['target'] = isset($_REQUEST['target']) ? $_REQUEST['target'] : '_self';

            // These fields are required!
            foreach ($required_fields as $required_field) {
                if ($_POST[$required_field] == '')
                    $post_errors[$required_field] = 'um_menu_empty_' . $required_field;
            }

            // Stop making numeric names!
            if (is_numeric($menu_entry['name']))
                $post_errors['name'] = 'um_menu_numeric';

            // Let's make sure you're not trying to make a name that's already taken.
            $check = check_um_name($menu_entry['id'], $menu_entry['name']);
            if ($check > 0)
                $post_errors['name'] = 'um_menu_mysql';

            if (empty($post_errors)) {
                // I see you made it to the final stage, my young padawan.
                save_um_name(filter_var_array($menu_entry, FILTER_SANITIZE_FULL_SPECIAL_CHARS));

                // Built the new menu and stash it away in settings for quick access
                rebuild_um_menu();

                // Before we leave, we must clear the cache. See, ElkArte
                // caches its menu at level 2 or higher.
                clean_cache('menu_buttons');

                redirectexit('action=admin;area=umen');
            } else {
                $context['post_error'] = $post_errors;
                $context['error_title'] = empty($menu_entry['id']) ? 'um_menu_errors_create' : 'um_menu_errors_modify';
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

                $context['page_title'] = $txt['um_menu_edit_title'];
            }
        }
    }

    /**
     * Prepares theme context for the template.
     *
     * @since 1.1
     */
    public function action_PrepareContext()
    {
        global $context, $txt;

        if (isset($_GET['in'])) {
            $row = fetch_um_page($_GET['in']);

            $context['button_data'] = array(
                'id' => (int)$_GET['in'],
                'name' => $row['name'],
                'target' => $row['target'],
                'type' => $row['type'],
                'position' => $row['position'],
                'permissions' => list_groups($row['permissions'], 1),
                'link' => $row['link'],
                'status' => $row['status'],
                'parent' => $row['parent'],
                'edit' => true,
            );
        } else {
            $context['button_data'] = array(
                'name' => '',
                'link' => '',
                'target' => '_self',
                'type' => 'forum',
                'position' => 'before',
                'status' => '1',
                'permissions' => list_groups('-3', 1),
                'parent' => 'home',
                'id' => 0,
            );

            $context['page_title'] = $txt['um_menu_add_title'];
        }
    }

    public function list_getMenu($start, $items_per_page, $sort)
    {
        return list_um_getMenu($start, $items_per_page, $sort);
    }

    public function list_getNumButtons()
    {
        return list_um_getNumButtons();
    }
}
