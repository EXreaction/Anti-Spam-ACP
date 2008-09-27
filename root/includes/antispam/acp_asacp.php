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

function asacp_display_ip_search($type, $ip, $url, $start = 0)
{
	global $db, $template, $user, $phpbb_admin_path, $phpbb_root_path, $phpEx;

	if (!$ip)
	{
		return;
	}

	$user->add_lang('memberlist');

	$sql_ip = $db->sql_escape($ip);
	$start = (int) $start;
	$limit = 10;

	$cnt = $total = 0;
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
			$db->sql_query('SELECT count(log_id) AS total FROM ' . LOG_TABLE . '
				WHERE log_ip = \'' . $sql_ip . '\'');
			$total = $db->sql_fetchfield('total');
			$sql = 'SELECT l.log_id, l.log_type, l.user_id, u.username, u.user_colour, l.log_time, l.forum_id, l.topic_id, l.reportee_id, l.log_operation, l.log_data
				FROM ' . LOG_TABLE . ' l, ' . USERS_TABLE . ' u
				WHERE log_ip = \'' . $sql_ip . '\'
				AND u.user_id = l.user_id
				ORDER BY log_time desc';
			$result = $db->sql_query_limit($sql, $limit, $start);
			while ($row = $db->sql_fetchrow($result))
			{
				$row['log_action'] = (isset($user->lang[$row['log_operation']])) ? $user->lang[$row['log_operation']] : '{' . ucfirst(str_replace('_', ' ', $row['log_operation'])) . '}';
				if (!empty($row['log_data']))
				{
					// We supress the warning about inappropriate number of passed parameters here due to possible changes within LOG strings from one version to another.
					$row['log_action'] = @vsprintf($row['log_action'], unserialize($row['log_data']));
					$row['log_action'] = bbcode_nl2br($row['log_action']);

					if ($row['log_type'] == LOG_SPAM)
					{
						if (isset($user->lang[$row['log_operation'] . '_DATA']))
						{
							// We supress the warning about inappropriate number of passed parameters here due to possible changes within LOG strings from one version to another.
							$row['log_action'] .= '<br />' . @vsprintf($user->lang[$row['log_operation'] . '_DATA'], unserialize($row['log_data']));
						}
					}
				}
				$row['username'] = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);

				unset($row['user_id'], $row['user_colour'], $row['log_operation'], $row['log_data']);

				$cnt++;
				if ($cnt == 1)
				{
					$output .= asacp_display_table_head($row);
				}

				$row['log_time'] = $user->format_date($row['log_time']);
				$output .= asacp_display_table_row($row, $cnt);
			}
			$db->sql_freeresult($result);
		break;
		// case 'logs' :

		case 'poll_votes' :
			$db->sql_query('SELECT count(vote_user_ip) AS total FROM ' . POLL_VOTES_TABLE . '
				WHERE vote_user_ip = \'' . $sql_ip . '\'');
			$total = $db->sql_fetchfield('total');
			$sql = 'SELECT * FROM ' . POLL_VOTES_TABLE . '
				WHERE vote_user_ip = \'' . $sql_ip . '\'
				ORDER BY topic_id desc';
			$result = $db->sql_query_limit($sql, $limit, $start);
			while ($row = $db->sql_fetchrow($result))
			{
				$cnt++;
				if ($cnt == 1)
				{
					$output .= asacp_display_table_head($row);
				}

				$row['topic_id'] = '<a href="' . append_sid("{$phpbb_root_path}viewtopic.$phpEx", 't=' . $row['topic_id']) . '">' . $row['topic_id'] . '</a>';
				$row['vote_user_id'] = '<a href="' . append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . $row['vote_user_id']) . '">' . $row['vote_user_id'] . '</a>';
				$output .= asacp_display_table_row($row, $cnt);
			}
			$db->sql_freeresult($result);
		break;
		// case 'poll_votes' :

		case 'posts' :
			$db->sql_query('SELECT count(post_id) AS total FROM ' . POSTS_TABLE . '
				WHERE poster_ip = \'' . $sql_ip . '\'');
			$total = $db->sql_fetchfield('total');
			$sql = 'SELECT p.post_subject, p.topic_id, p.post_id, p.poster_id, u.username, u.user_colour, p.post_username, p.post_time FROM ' . POSTS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE poster_ip = \'' . $sql_ip . '\'
				AND u.user_id = p.poster_id
				ORDER BY post_time desc';
			$result = $db->sql_query_limit($sql, $limit, $start);
			while ($row = $db->sql_fetchrow($result))
			{
				$row['post_subject'] = '<a href="' . append_sid("{$phpbb_root_path}viewtopic.$phpEx", "t={$row['topic_id']}&amp;p={$row['post_id']}#p{$row['post_id']}") . '">' . $row['post_subject'] . '</a>';
				$row['username'] = get_username_string('full', $row['poster_id'], $row['username'], $row['user_colour'], $row['post_username']);
				unset($row['poster_id'], $row['post_id'], $row['topic_id'], $row['user_colour'], $row['post_username']);

				$cnt++;
				if ($cnt == 1)
				{
					$output .= asacp_display_table_head($row);
				}

				$row['post_time'] = $user->format_date($row['post_time']);
				$output .= asacp_display_table_row($row, $cnt);
			}
			$db->sql_freeresult($result);
		break;
		// case 'posts' :

		case 'privmsgs' :
			$db->sql_query('SELECT count(msg_id) AS total FROM ' . PRIVMSGS_TABLE . '
				WHERE author_ip = \'' . $sql_ip . '\'');
			$total = $db->sql_fetchfield('total');
			$sql = 'SELECT p.msg_id, p.author_id, u.username, u.user_colour, p.message_time, message_subject, to_address, bcc_address FROM ' . PRIVMSGS_TABLE . ' p, ' . USERS_TABLE . ' u
				WHERE author_ip = \'' . $sql_ip . '\'
				AND u.user_id = p.author_id
				ORDER BY message_time desc';
			$result = $db->sql_query_limit($sql, $limit, $start);
			while ($row = $db->sql_fetchrow($result))
			{
				$row['username'] = get_username_string('full', $row['author_id'], $row['username'], $row['user_colour']);
				unset($row['author_id'], $row['user_colour']);

				$cnt++;
				if ($cnt == 1)
				{
					$output .= asacp_display_table_head($row);
				}

				$row['message_time'] = $user->format_date($row['message_time']);
				$output .= asacp_display_table_row($row, $cnt);
			}
			$db->sql_freeresult($result);
		break;
		//case 'privmsgs' :

		case 'users' :
			$db->sql_query('SELECT count(user_id) AS total FROM ' . USERS_TABLE . '
				WHERE user_ip = \'' . $sql_ip . '\'');
			$total = $db->sql_fetchfield('total');
			$sql = 'SELECT user_id, username, user_regdate, user_email, user_colour  FROM ' . USERS_TABLE . '
				WHERE user_ip = \'' . $sql_ip . '\'
				ORDER BY user_regdate desc';
			$result = $db->sql_query_limit($sql, $limit, $start);
			while ($row = $db->sql_fetchrow($result))
			{
				$row['username'] = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);
				$row[$user->lang['ACTIONS']] = '<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", 'i=users&amp;mode=overview&amp;u=' . $row['user_id']) . '">' . $user->lang['USER_ADMIN'] . '</a>';;
				unset($row['user_colour']);

				$cnt++;
				if ($cnt == 1)
				{
					$output .= asacp_display_table_head($row);
				}

				$row['user_regdate'] = $user->format_date('user_regdate');
				$output .= asacp_display_table_row($row, $cnt);
			}
			$db->sql_freeresult($result);
		break;
		// case 'users' :

		default :
			asacp_display_ip_search('bot_check', $ip, $url);
			asacp_display_ip_search('logs', $ip, $url);
			asacp_display_ip_search('poll_votes', $ip, $url);
			asacp_display_ip_search('posts', $ip, $url);
			asacp_display_ip_search('privmsgs', $ip, $url);
			asacp_display_ip_search('users', $ip, $url);
			return;
		break;
	}

	if ($output)
	{
		$template->assign_block_vars('ip_search', array(
			'TITLE'			=> (isset($user->lang['ASACP_IP_SEARCH_' . strtoupper($type)])) ? $user->lang['ASACP_IP_SEARCH_' . strtoupper($type)] : 'ASACP_IP_SEARCH_' . strtoupper($type),
			'DATA'			=> $output,
			'PAGINATION'	=> ($total) ? generate_pagination($url . '&amp;type=' . $type, $total, $limit, $start, true, 'ip_search') : '',
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