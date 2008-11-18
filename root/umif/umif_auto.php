<?php
/**
 * @author Nathan Guse (EXreaction) http://lithiumstudios.org
 * @author David Lewis (Highway of Life) highwayoflife@gmail.com
 * @package phpBB3 UMIF - Unified MOD Install File
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 */

/* Parameters which should be setup before calling this file:
* @param string $file_name The path to this file (the file which contains the instructions)
* @param string $mod_name The name of the mod to be displayed during installation.
* @param string $language_file The language file which will be included when installing (should contain the $mod_name)
* @param string $current_version The current version that would be installed/updated to.
* @param string $version_config_name The name of the config variable which will hold the currently installed version
* @param array $versions The array of versions and actions within each.
*/

/* Language entries that should exist in the $language_file that will be included:
* $mod_name
* 'INSTALL_' . $mod_name
* 'INSTALL_' . $mod_name . '_CONFIRM'
* 'UPDATE_' . $mod_name
* 'UPDATE_' . $mod_name . '_CONFIRM'
* 'UNINSTALL_' . $mod_name
* 'UNINSTALL_' . $mod_name . '_CONFIRM'
*/

// You must run define('UMIF_AUTO', true) before calling this file.
if (!defined('UMIF_AUTO') || !defined('PHPBB_ROOT_PATH') || !defined('PHP_EXT'))
{
	exit;
}

// Compatibility with 3.0.X
$phpbb_root_path = PHPBB_ROOT_PATH;
$phpEx = PHP_EXT;

define('IN_PHPBB', true);
include(PHPBB_ROOT_PATH . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup($language_file);

if (!class_exists('umif_frontend'))
{
	include(PHPBB_ROOT_PATH . 'umif/umif_frontend.' . PHP_EXT);
}

$umif = new umif_frontend($mod_name, true);

if ($version_config_name && isset($config[$version_config_name]))
{
	$submit = (isset($_POST['submit'])) ? true : false;
	$action = request_var('action', '');
	$stages = array(
		'CONFIGURE'	=> array('url' => append_sid($file_name)),
		'CONFIRM',
		'ACTION',
	);

	if (!$submit && !$umif->confirm_box(true))
	{
		$umif->display_stages($stages);

		$default = ($config[$version_config_name]) ? (($current_version == $config[$version_config_name]) ? 3 : 2) : 1;
		$options = array(
			'legend1'		=> $mod_name,
			'action'		=> array('lang' => 'ACTION', 'type' => 'custom', 'function' => 'umif_install_update_uninstall_select', 'explain' => false, 'default' => $default),
		);

		$umif->display_options($options);
		$umif->done();
	}
	else if (!$umif->confirm_box(true))
	{
		$umif->display_stages($stages, 2);

		$hidden = array('action' => $action);
		switch ($action)
		{
			case 'install' :
				$umif->confirm_box(false, 'INSTALL_' . $mod_name, $hidden);
			break;

			case 'update' :
				$umif->confirm_box(false, 'UPDATE_' . $mod_name, $hidden);
			break;

			case 'uninstall' :
				$umif->confirm_box(false, 'UNINSTALL_' . $mod_name, $hidden);
			break;
		}
	}
	else if ($umif->confirm_box(true))
	{
		$umif->display_stages($stages, 3);

		umif_run_actions($action, $versions, $current_version, $version_config_name);
		$umif->done();
	}
}
else
{
	$stages = array(
		'CONFIRM'	=> array('url' => append_sid($file_name)),
		'INSTALL',
	);

	if (!$umif->confirm_box(true))
	{
		$umif->display_stages($stages);
		$umif->confirm_box(false, 'INSTALL_' . $mod_name);
	}
	else if ($umif->confirm_box(true))
	{
		$umif->display_stages($stages, 2);
		umif_run_actions('install', $versions, $current_version, $version_config_name);
		$umif->done();
	}
}

// Shouldn't get here.
redirect($file_name);

function umif_install_update_uninstall_select($value, $key)
{
	global $user;

	return '<input id="' . $key . '" class="radio" type="radio" name="' . $key . '" value="install"' . (($value == 1) ? ' checked="checked"' : '') . ' /> ' . $user->lang['INSTALL'] . '&nbsp;&nbsp;
	<input id="' . $key . '" class="radio" type="radio" name="' . $key . '" value="update"' . (($value == 2) ? ' checked="checked"' : '') . ' /> ' . $user->lang['UPDATE'] . '&nbsp;&nbsp;
	<input id="' . $key . '" class="radio" type="radio" name="' . $key . '" value="uninstall"' . (($value == 3) ? ' checked="checked"' : '') . ' /> ' . $user->lang['UNINSTALL'];
}

/**
* Run Actions
*
* Do-It-All function that can do everything required for installing/updating/uninstalling a mod based on an array of actions and the versions.
*
* @param string $action The action. install|update|uninstall
* @param array $versions The array of versions and the actions for each
* @param string $current_version The current version to install/update to
* @param string|bool $db_version The current version installed to update to/remove from
*/
function umif_run_actions($action, $versions, $current_version, $version_config_name)
{
	global $config, $umif;

	$db_version = (isset($config[$version_config_name])) ? $config[$version_config_name] : '';

	// sort the actions by the version.
	if ($action == 'install' || $action == 'update')
	{
		ksort($versions);
	}
	else if ($action == 'uninstall')
	{
		krsort($versions);
	}
	else
	{
		return false;
	}

	foreach ($versions as $version => $version_actions)
	{
		if ($action == 'install' || ($action == 'update' && version_compare($version, $db_version, '>')))
		{
			foreach ($version_actions as $method => $params)
			{
				if (method_exists($umif, $method))
				{
					call_user_func(array($umif, $method), $params);
				}
			}
		}
		else if ($action == 'uninstall' && version_compare($db_version, $version, '>'))
		{
			$version_actions = array_reverse($version_actions);

			foreach ($version_actions as $method => $params)
			{
				// update mode (reversing an action) isn't possible for uninstallations
				if (strpos($method, 'update'))
				{
					continue;
				}

				// reverse function call
				$method = str_replace(array('add', 'remove', 'temp'), array('temp', 'add', 'remove'), $method);

				if (method_exists($umif, $method))
				{
					call_user_func(array($umif, $method), array_reverse($params));
				}
			}
		}
	}

	if ($action == 'uninstall')
	{
		$umif->config_remove($version_config_name);
	}
	else
	{
		if ($umif->config_exists($version_config_name))
		{
			$umif->config_update($version_config_name, $current_version);
		}
		else
		{
			$umif->config_add($version_config_name, $current_version);
		}
	}
}
?>