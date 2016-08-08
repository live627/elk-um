<?php

/**
 * @package Ultimate Menu mod
 * @version 1.1.1
 * @author John Rayes <live627@gmail.com>
 * @copyright Copyright (c) 2014, John Rayes
 * @license http://opensource.org/licenses/MIT MIT
 */

// If SSI.php is in the same place as this file, and ElkArte isn't defined...
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('ELK'))
    require_once(dirname(__FILE__) . '/SSI.php');

// Hmm... no SSI.php and no ElkArte?
elseif (!defined('ELK'))
    die('<b>Error:</b> Cannot uninstall - please verify you put this in the same place as ElkArte\'s SSI.php.');

if (isset($modSettings['um_menu']))
    unset($modSettings['um_menu']);

$db = database();
$db->query('', '
    DELETE FROM {db_prefix}settings
    WHERE variable = {string:setting}',
    array(
        'setting' => 'um_menu',
    )
);
