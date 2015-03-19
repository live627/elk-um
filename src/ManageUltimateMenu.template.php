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

function template_main()
{
	global $context, $scripturl, $txt;

	echo '
		<form action="', $scripturl, '?action=admin;area=umen;sa=savebutton" method="post" accept-charset="UTF-8" name="postmodify" id="postmodify" class="flow_hidden">
			<div class="category_header">
				<h3>
					', $context['page_title'], '
				</h3>
			</div>
				<div class="roundframe">';

	// If an error occurred, explain what happened.
	if (!empty($context['post_error']))
	{
		echo '
					<div class="errorbox" id="errors">
						<strong>', $txt[$context['error_title']], '</strong>
						<ul>';

		foreach ($context['post_error'] as $error)
			echo '
							<li>', $txt[$error], '</li>';

		echo '
						</ul>
					</div>';
	}

	echo '
					<dl class="settings">
						<dt>
							<strong>', $txt['um_menu_button_name'], ':</strong>
						</dt>
						<dd>
							<input type="text" name="name" id="bnbox" value="', $context['button_data']['name'], '" tabindex="1" class="input_text" style="width: 100%;" />
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_position'], ':</strong>
						</dt>
						<dd>
							<select name="position" size="10" style="width:22%;height:auto;max-height:none;" onchange="this.form.position.disabled = this.options[this.selectedIndex].value == \'\';">
								<option value="after"', $context['button_data']['position'] === 'after' ? ' selected="selected"' : '', '>' . $txt['mboards_order_after'] . '...</option>
								<option value="child_of"', $context['button_data']['position'] === 'child_of' ? ' selected="selected"' : '', '>' . $txt['mboards_order_child_of'] . '...</option>
								<option value="before"', $context['button_data']['position'] === 'before' ? ' selected="selected"' : '', '>' . $txt['mboards_order_before'] . '...</option>
							</select>
							<select name="parent" size="10" style="width:75%;;height:auto;max-height:none;">';

	foreach ($context['menu_buttons'] as $buttonIndex => $buttonData)
	{
		echo '
								<option value="', $buttonIndex, '"', $context['button_data']['parent'] == $buttonIndex ? ' selected="selected"' : '', '>', $buttonData['title'], '</option>';

		if (!empty($buttonData['sub_buttons']))
		{
			foreach ($buttonData['sub_buttons'] as $childButton => $childButtonData)
			{
				echo '
								<option value="', $childButton, '"', $context['button_data']['parent'] == $childButton ? ' selected="selected"' : '', '>&#8195;', (isBrowser('ie8') ? '&#187; ' : '&#10148; '), $childButtonData['title'], '</option>';

				if (!empty($childButtonData['sub_buttons']))
				{
					foreach ($childButtonData['sub_buttons'] as $grandChildButton => $grandChildButtonData)
						echo '
								<option value="', $grandChildButton, '"', $context['button_data']['parent'] == $grandChildButton ? ' selected="selected"' : '', '>&#8195;&#8195;', (isBrowser('ie8') ? '&#187; ' : '&#10148; '), $grandChildButtonData['title'], '</option>';
				}
			}
		}
	}

	echo '
							</select>
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_type'], ':</strong>
						</dt>
						<dd>
							<input type="radio" class="input_check" name="type" value="forum"', $context['button_data']['type'] == 'forum' ? ' checked="checked"' : '', '/>', $txt['um_menu_forum'], '<br />
							<input type="radio" class="input_check" name="type" value="external"', $context['button_data']['type'] == 'external' ? ' checked="checked"' : '', '/>', $txt['um_menu_external'], '
						</dd>
						<dt>
							<strong>', $txt['um_menu_link_type'], ':</strong>
						</dt>
						<dd>
							<input type="radio" class="input_check" name="target" value="_self"', $context['button_data']['target'] == '_self' ? ' checked="checked"' : '', '/>', $txt['um_menu_same_window'], '<br />
							<input type="radio" class="input_check" name="target" value="_blank"', $context['button_data']['target'] == '_blank' ? ' checked="checked"' : '', '/>', $txt['um_menu_new_tab'], '
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_link'], ':</strong><br />
						</dt>
						<dd>
							<input type="text" name="link" value="', $context['button_data']['link'], '" tabindex="1" class="input_text" style="width: 100%;" />
							<span class="smalltext">', $txt['um_menu_button_link_desc'], '</span>
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_perms'], ':</strong>
						</dt>
						<dd>
							<fieldset id="group_perms">
								<legend>', $txt['avatar_select_permission'], '</legend>';

	$all_checked = true;

	// List all the groups to configure permissions for.
	foreach ($context['button_data']['permissions'] as $permission)
	{
		echo '
								<div id="permissions_', $permission['id'], '">
									<label for="check_group', $permission['id'], '">
										<input type="checkbox" class="input_check" name="permissions[]" value="', $permission['id'], '" id="check_group', $permission['id'], '"', $permission['checked'] ? ' checked="checked"' : '', ' />
										<span', ($permission['is_post_group'] ? ' title="' . $txt['mboards_groups_post_group'] . '"' : ''), '>', $permission['name'], '</span>
									</label>
								</div>';

		if (!$permission['checked'])
			$all_checked = false;
	}

	echo '
								<input type="checkbox" class="input_check" onclick="invertAll(this, this.form, \'permissions[]\');" id="check_group_all"', $all_checked ? ' checked="checked"' : '', ' />
								<label for="check_group_all"><em>', $txt['check_all'], '</em></label>
							</fieldset>
						</dd>
						<dt>
							<strong>', $txt['um_menu_button_status'], ':</strong>
						</dt>
						<dd>
							<input type="radio" class="input_check" name="status" value="active"', $context['button_data']['status'] == 'active' ? ' checked="checked"' : '', ' />', $txt['um_menu_button_active'], ' <br />
							<input type="radio" class="input_check" name="status" value="inactive"', $context['button_data']['status'] == 'inactive' ? ' checked="checked"' : '', ' />', $txt['um_menu_button_inactive'], '
						</dd>
					</dl>
					<input name="in" value="', $context['button_data']['id'], '" type="hidden" />
					<div class="submitbutton">
						<input name="submit" value="', $txt['admin_manage_menu_submit'], '" class="button_submit" type="submit" />
					</div>
				</div>
			</form>';
}