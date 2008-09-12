<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

function asacp_display_table_head($row)
{
	$output = '<tr>';
	foreach ($row as $name => $data)
	{
		$output .= '<th>' . $name . '</th>';
	}
	$output .= '</tr>';

	return $output;
}

function asacp_display_table_row($row, $cnt)
{
	$output = '<tr class="row' . ($cnt % 2 + 1) . '">';
	foreach ($row as $name => $data)
	{
		$output .= '<td>' . $data . '</td>';
	}
	$output .= '</tr>';

	return $output;
}

function asacp_display_ip_search($type, $ip)
{
	/*
	* TODO
	* Check to see if this IP happens to be a bot IP as well (from the built in search bots list).  Give a notice if it is.
	* Check the logs table
	* Poll Votes table?
	* Posts table
	* Private messages
	* Topics table
	* Users table
	*/
	global $db, $template, $user;

	if (!$ip)
	{
		return;
	}

	$sql_ip = $db->sql_escape($ip);

	$cnt = 0;
	$output = '';
	switch ($type)
	{
		case 'bot_check' :
			$sql = 'SELECT * FROM ' . BOTS_TABLE . ' WHERE bot_ip = \'' . $sql_ip . '\'';
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$cnt++;
				if ($cnt == 1)
				{
					$output .= asacp_display_table_head($row);
				}

				$output .= asacp_display_table_row($row, $cnt);
			}
			$db->sql_freeresult($result);
		break;
		// case 'bot_check' :

		case 'logs' :
			$sql = 'SELECT * FROM ' . LOG_TABLE . ' WHERE log_ip = \'' . $sql_ip . '\'';
			$result = $db->sql_query($sql);
			while ($row = $db->sql_fetchrow($result))
			{
				$cnt++;
				if ($cnt == 1)
				{
					$output .= asacp_display_table_head($row);
				}

				$output .= asacp_display_table_row($row, $cnt);
			}
			$db->sql_freeresult($result);
		break;
		// case 'logs' :

		case 'poll_votes' :
		break;
		// case 'poll_votes' :

		case 'posts' :
		break;
		// case 'posts' :

		case 'privmsgs' :
		break;
		//case 'privmsgs' :

		case 'topics' :
		break;
		// case 'topics' :

		case 'users' :
		break;
		// case 'topics' :

		default :
			asacp_display_ip_search('bot_check', $ip);
			asacp_display_ip_search('logs', $ip);
			asacp_display_ip_search('poll_votes', $ip);
			asacp_display_ip_search('posts', $ip);
			asacp_display_ip_search('privmsgs', $ip);
			asacp_display_ip_search('topics', $ip);
			asacp_display_ip_search('users', $ip);
			return;
		break;
	}

	if ($output)
	{
		$template->assign_block_vars('ip_search', array(
			'TITLE'		=> (isset($user->lang['ASACP_IP_SEARCH_' . strtoupper($type)])) ? $user->lang['ASACP_IP_SEARCH_' . strtoupper($type)] : 'ASACP_IP_SEARCH_' . strtoupper($type),
			'DATA'		=> $output,
		));
	}
}

function asacp_display_options(&$options, &$error, $u_action)
{
	global $config, $template, $user;

	$submit = (isset($_POST['submit'])) ? true : false;

	$new_config = $config;
	$cfg_array = (isset($_REQUEST['config'])) ? utf8_normalize_nfc(request_var('config', array('' => ''), true)) : $new_config;

	validate_config_vars($options, $cfg_array, $error);
	foreach ($options as $config_name => $null)
	{
		if (!isset($cfg_array[$config_name]) || strpos($config_name, 'legend') !== false)
		{
			continue;
		}

		$new_config[$config_name] = $config_value = $cfg_array[$config_name];

		if ($submit && !sizeof($error))
		{
			set_config($config_name, $config_value);
		}
	}

	if ($submit && !sizeof($error))
	{
		add_log('admin', 'LOG_ASACP_SETTINGS');

		trigger_error($user->lang['ASACP_SETTINGS_UPDATED'] . adm_back_link($u_action));
	}

	foreach ($options as $config_key => $vars)
	{
		if (!is_array($vars) && strpos($config_key, 'legend') === false)
		{
			continue;
		}

		if (strpos($config_key, 'legend') !== false)
		{
			$template->assign_block_vars('options', array(
				'S_LEGEND'		=> true,
				'LEGEND'		=> (isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
			);

			continue;
		}

		$type = explode(':', $vars['type']);

		$l_explain = '';
		if ($vars['explain'] && isset($vars['lang_explain']))
		{
			$l_explain = (isset($user->lang[$vars['lang_explain']])) ? $user->lang[$vars['lang_explain']] : $vars['lang_explain'];
		}
		else if ($vars['explain'])
		{
			$l_explain = (isset($user->lang[$vars['lang'] . '_EXPLAIN'])) ? $user->lang[$vars['lang'] . '_EXPLAIN'] : '';
		}

		$content = build_cfg_template($type, $config_key, $new_config, $config_key, $vars);

		if (empty($content))
		{
			continue;
		}

		$template->assign_block_vars('options', array(
			'KEY'			=> $config_key,
			'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
			'S_EXPLAIN'		=> $vars['explain'],
			'TITLE_EXPLAIN'	=> $l_explain,
			'CONTENT'		=> $content,
			)
		);

		unset($options[$config_key]);
	}
}
?>