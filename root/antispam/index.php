<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/asacp');

$mode = request_var('mode', '');
$user_id = request_var('u', 0);
$post_id = request_var('p', 0);

$return_url = append_sid("{$phpbb_root_path}index.$phpEx");
if ($post_id)
{
	$return_url = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "p=$post_id#p$post_id");
}
else if ($user_id)
{
	$return_url = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=viewprofile&amp;u=$user_id");
}
$return = '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $return_url . '">', '</a>');

switch ($mode)
{
	case 'display_ips' :
		if (!$auth->acl_get('a_asacp_ip_search'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$sql = 'SELECT user_ip FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $db->sql_query($sql);
		$user_row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$user_row)
		{
			trigger_error('NO_USER');
		}

		$ip_search = array();
		$u_ip_search = '<a href="' . append_sid("{$phpbb_root_path}adm/index.$phpEx", 'i=asacp&amp;mode=ip_search&amp;ip={IP}', true, $user->session_id) . '">{IP}</a>';

		if ($user_row['user_ip'])
		{
			$ip_search[] = str_replace('{IP}', $user_row['user_ip'], $u_ip_search);
		}

		$sql = 'SELECT DISTINCT(poster_ip) FROM ' . POSTS_TABLE . '
			WHERE poster_id = ' . $user_id . "
			AND poster_ip <> '" . $user_row['user_ip'] . "'
			ORDER BY post_id DESC";
		$result = $db->sql_query($sql);
		while ($row = $db->sql_fetchrow($result))
		{
			$ip_search[] = str_replace('{IP}', $row['poster_ip'], $u_ip_search);
		}
		$db->sql_freeresult($result);

		trigger_error(implode('<br />', $ip_search) . $return);
	break;

	case 'view_flag_log' :
		if (!$auth->acl_get('m_asacp_user_flag'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$user->add_lang(array('acp/common', 'acp/board', 'mods/acp_asacp', 'mods/info_acp_asacp', 'install'));
		include($phpbb_root_path . 'antispam/acp_functions.' . $phpEx);

		$error = $notify = array();
		$submit = (isset($_POST['submit'])) ? true : false;
		$action = request_var('action', '');

		add_form_key('as_acp');
		if ($submit && !check_form_key('as_acp'))
		{
			trigger_error($user->lang['FORM_INVALID'] . ' ' . append_sid("{$phpbb_root_path}antispam/index.$phpEx","mode=view_flag_log"), E_USER_WARNING);
		}

		// Reset the user flag new notification
		if ($user->data['user_flag_new'])
		{
			$db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_flag_new = 0 WHERE user_id = ' . $user->data['user_id']);
		}

		$user->add_lang('mcp');

		// Set up general vars
		$start		= request_var('start', 0);
		$deletemark = (!empty($_POST['delmarked'])) ? true : false;
		$deleteall	= (!empty($_POST['delall'])) ? true : false;
		$marked		= request_var('mark', array(0));

		// Sort keys
		$sort_days	= request_var('st', 0);
		$sort_key	= request_var('sk', 't');
		$sort_dir	= request_var('sd', 'd');

		// Delete entries if requested and able
		if (($deletemark || $deleteall) && $auth->acl_get('a_clearlogs'))
		{
			if (confirm_box(true))
			{
				$where_sql = '';

				if ($deletemark && sizeof($marked))
				{
					$sql_in = array();
					foreach ($marked as $mark)
					{
						$sql_in[] = $mark;
					}
					$where_sql = ' AND ' . $db->sql_in_set('log_id', $sql_in);
					unset($sql_in);
				}

				if ($where_sql || $deleteall)
				{
					$db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_flag_new = 0');

					$sql = 'DELETE FROM ' . SPAM_LOG_TABLE . '
						WHERE log_type = ' . (($mode == 'log') ? 1 : 2) .
						$where_sql;
					$db->sql_query($sql);

					if ($deleteall)
					{
						add_log('admin', 'LOG_CLEAR_SPAM_LOG');
					}
				}
			}
			else
			{
				confirm_box(false, $user->lang['CONFIRM_OPERATION'], build_hidden_fields(array(
					'start'		=> $start,
					'delmarked'	=> $deletemark,
					'delall'	=> $deleteall,
					'mark'		=> $marked,
					'st'		=> $sort_days,
					'sk'		=> $sort_key,
					'sd'		=> $sort_dir,
					'mode'		=> $mode,
					'action'	=> append_sid("{$phpbb_root_path}antispam/index.$phpEx","mode=view_flag_log")))
				);
			}
		}

		// Sorting
		$limit_days = array(0 => $user->lang['ALL_ENTRIES'], 1 => $user->lang['1_DAY'], 7 => $user->lang['7_DAYS'], 14 => $user->lang['2_WEEKS'], 30 => $user->lang['1_MONTH'], 90 => $user->lang['3_MONTHS'], 180 => $user->lang['6_MONTHS'], 365 => $user->lang['1_YEAR']);
		$sort_by_text = array('t' => $user->lang['SORT_DATE'], 'u' => $user->lang['SORT_USERNAME'], 'i' => $user->lang['SORT_IP'], 'o' => $user->lang['SORT_ACTION']);
		$sort_by_sql = array('t' => 'l.log_time', 'u' => 'u.username_clean', 'i' => 'l.log_ip', 'o' => 'l.log_operation');

		$s_limit_days = $s_sort_key = $s_sort_dir = $u_sort_param = '';
		gen_sort_selects($limit_days, $sort_by_text, $sort_days, $sort_key, $sort_dir, $s_limit_days, $s_sort_key, $s_sort_dir, $u_sort_param);

		// Define where and sort sql for use in displaying logs
		$sql_days = ($sort_days) ? (time() - ($sort_days * 86400)) : 0;
		$sql_sort = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

		// Grab log data
		$log_data = array();
		$log_count = 0;

		view_spam_log('flag', $log_data, $log_count, $config['topics_per_page'], $start, '', $sql_days, $sql_sort);

		$template->assign_vars(array(
			'L_TITLE'		=> $user->lang['ASACP_FLAG_LOG'],
			'L_EXPLAIN'		=> '',

			'S_ON_PAGE'		=> on_page($log_count, $config['topics_per_page'], $start),
			'PAGINATION'	=> generate_pagination(append_sid("{$phpbb_root_path}antispam/index.$phpEx","mode=view_flag_log&$u_sort_param"), $log_count, $config['topics_per_page'], $start, true),

			'S_LIMIT_DAYS'	=> $s_limit_days,
			'S_SORT_KEY'	=> $s_sort_key,
			'S_SORT_DIR'	=> $s_sort_dir,
			'S_CLEARLOGS'	=> $auth->acl_get('a_clearlogs'),
		));

		foreach ($log_data as $row)
		{
			$template->assign_block_vars('log', array(
				'USERNAME'			=> $row['username_full'],
				'REPORTEE_USERNAME'	=> ($row['reportee_username'] && $row['user_id'] != $row['reportee_id']) ? $row['reportee_username_full'] : '',

				'IP'				=> '<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", "i=asacp&amp;mode=ip_search&amp;ip={$row['ip']}") . '">' . $row['ip'] . '</a>',
				'DATE'				=> $user->format_date($row['time']),
				'ACTION'			=> $row['action'],
				'DATA'				=> (sizeof($row['data'])) ? @vsprintf($user->lang[$row['operation'] . '_DATA'], $row['data']) : '',
				'ID'				=> $row['id'],
			));
		}

		page_header($user->lang['ASACP_FLAG_LOG'], false);

		$template->set_filenames(array(
			'body' => 'antispam/acp_logs.html',
		));

		page_footer();
	break;
	//case 'flag' :

	case 'flag_list' :
		if (!$auth->acl_get('m_asacp_user_flag'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$user->add_lang(array('acp/common', 'acp/board', 'mods/acp_asacp', 'mods/info_acp_asacp', 'install', 'memberlist'));
		include($phpbb_root_path . 'antispam/acp_functions.' . $phpEx);

		$error = $notify = array();
		$submit = (isset($_POST['submit'])) ? true : false;
		$action = request_var('action', '');

		add_form_key('as_acp');
		if ($submit && !check_form_key('as_acp'))
		{
			trigger_error($user->lang['FORM_INVALID'] . ' ' . append_sid("{$phpbb_root_path}antispam/index.$phpEx","mode=view_flag_log"), E_USER_WARNING);
		}

		$start = request_var('start', 0);
		$limit = request_var('limit', 20);

		$db->sql_query('SELECT count(user_id) as cnt FROM ' . USERS_TABLE . ' WHERE user_flagged = 1');
		$total = $db->sql_fetchfield('cnt');

		$sql = 'SELECT user_id, username, user_colour, user_ip, user_posts FROM ' . USERS_TABLE . ' WHERE user_flagged = 1';
		$result = $db->sql_query_limit($sql, $limit, $start);

		$cnt = 0;
		$output = '';
		while ($row = $db->sql_fetchrow($result))
		{
			$row['username'] = get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);

			if ($auth->acl_get('a_asacp_ip_search'))
			{
				$row['user_ip'] = '<a href="' . append_sid("{$phpbb_root_path}adm/index.$phpEx", "i=asacp&amp;mode=ip_search&amp;ip={$row['user_ip']}", true, $user->session_id) . '">' . $row['user_ip'] . '</a>';
			}

			if ($auth->acl_get('a_user'))
			{
				$row[$user->lang['ACTION']] = '<a href="' . append_sid("{$phpbb_root_path}adm/index.$phpEx", "i=users&amp;mode=overview&amp;u={$row['user_id']}", true, $user->session_id) . '">' . $user->lang['USER_ADMIN'] . '</a>';
			}

			unset($row['user_id'], $row['user_colour']);

			$cnt++;
			if ($cnt == 1)
			{
				$output .= asacp_display_table_head($row);
			}

			$output .= asacp_display_table_row($row, $cnt);
		}
		$db->sql_freeresult($result);

		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang['ASACP_FLAG_LIST'],
			'L_TITLE_EXPLAIN'	=> $user->lang['ASACP_FLAG_LIST_EXPLAIN'],

			'S_DATA_OUTPUT'		=> true,
		));

		$template->assign_block_vars('data_output', array(
			'TITLE'			=> $user->lang['USERS'],
			'DATA'			=> $output,
			'PAGINATION'	=> ($total) ? generate_pagination(append_sid("{$phpbb_root_path}antispam/index.$phpEx","mode=view_flag_log&amp;limit=$limit"), $total, $limit, $start, true, 'data_output') : '',
		));

		page_header($user->lang['ASACP_FLAG_LIST'], false);

		$template->set_filenames(array(
			'body' => 'antispam/acp_asacp.html',
		));

		page_footer();
	break;
	//case 'flag_list' :

	case 'user_flag' :
		if (!$auth->acl_get('m_asacp_user_flag'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$sql = 'SELECT username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			trigger_error('NO_USER');
		}
		$username = get_username_string('full', $user_id, $row['username'], $row['user_colour']);

		if (confirm_box(true))
		{
			$db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_flagged = 1 WHERE user_id = ' . $user_id);

			add_log('admin', 'LOG_USER_FLAGGED', $username);
			trigger_error($user->lang['USER_FLAG_SUCCESS'] . $return);
		}
		else
		{
			$user->lang['USER_FLAG_CONFIRM'] = sprintf($user->lang['USER_FLAG_CONFIRM'], $username);
			confirm_box(false, 'USER_FLAG');
		}
	break;

	case 'user_unflag' :
		if (!$auth->acl_get('m_asacp_user_flag'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$sql = 'SELECT username, user_colour FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $db->sql_query($sql);
		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$row)
		{
			trigger_error('NO_USER');
		}
		$username = get_username_string('full', $user_id, $row['username'], $row['user_colour']);

		if (confirm_box(true))
		{
			$db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_flagged = 0 WHERE user_id = ' . $user_id);

			add_log('admin', 'LOG_USER_UNFLAGGED', $username);
			trigger_error($user->lang['USER_UNFLAG_SUCCESS'] . $return);
		}
		else
		{
			$user->lang['USER_UNFLAG_CONFIRM'] = sprintf($user->lang['USER_UNFLAG_CONFIRM'], $username);
			confirm_box(false, 'USER_UNFLAG');
		}
	break;

	case 'ocban' :
		if (!$auth->acl_get('m_asacp_ban'))
		{
			trigger_error('NOT_AUTHORISED');
		}

		$sql = 'SELECT * FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
		$result = $db->sql_query($sql);
		$user_row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if (!$user_row)
		{
			trigger_error('NO_USER');
		}
		$username = get_username_string('full', $user_id, $user_row['username'], $user_row['user_colour']);

		$error = (isset($_POST['sfs_submit']) && !request_var('sfs_evidence', '')) ? true : false;

		if (confirm_box(true) && !$error)
		{
			if (!function_exists('user_ban'))
			{
				include($phpbb_root_path . 'includes/functions_user.' . $phpEx);
			}
			if (!function_exists('delete_posts'))
			{
				include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
			}

			// Ban the user
			if ($config['asacp_ocban_username'])
			{
				user_ban('user', $user_row['username'], 0, '', false, utf8_normalize_nfc(request_var('ban_reason', '', true)), utf8_normalize_nfc(request_var('ban_reason_shown', '', true)));

				// Remove the flag on the user's account if they are banned
				$db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_flagged = 0 WHERE user_id = ' . $user_id);
			}

			// Deactivate the user
			if ($config['asacp_ocban_deactivate'])
			{
				user_active_flip('deactivate', $user_id, INACTIVE_MANUAL);
			}

			// Move the user to a certain group
			if ($config['asacp_ocban_move_to_group'])
			{
				$sql = 'SELECT group_id FROM ' . USER_GROUP_TABLE . ' WHERE user_id = ' . $user_id;
				$result = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($result))
				{
					group_user_del($row['group_id'], array($user_id), array($username));
				}
				$db->sql_freeresult($result);

				group_user_add($config['asacp_ocban_move_to_group'], array($user_id), array($username), false, true);
			}

			// Delete the user's posts
			if ($config['asacp_ocban_delete_posts'])
			{
				delete_posts('poster_id', $user_id);
			}

			// Delete the user's avatar
			if ($config['asacp_ocban_delete_avatar'] && $user_row['user_avatar'])
			{
				avatar_delete('user', $user_row, true);
			}

			// Delete the user's signature
			if ($config['asacp_ocban_delete_signature'])
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' .	$db->sql_build_array('UPDATE', array('user_sig' => '', 'user_sig_bbcode_uid' => '', 'user_sig_bbcode_bitfield' => '')) . '
					WHERE user_id = ' . $user_id;
				$db->sql_query($sql);
			}

			// Clear the user's outbox
			if ($config['asacp_ocban_clear_outbox'])
			{
				$msg_ids = array();

				$sql = 'SELECT msg_id
					FROM ' . PRIVMSGS_TO_TABLE . "
					WHERE author_id = $user_id
						AND folder_id = " . PRIVMSGS_OUTBOX;
				$result = $db->sql_query($sql);

				if ($row = $db->sql_fetchrow($result))
				{
					if (!function_exists('delete_pm'))
					{
						include($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
					}

					do
					{
						$msg_ids[] = (int) $row['msg_id'];
					}
					while ($row = $db->sql_fetchrow($result));

					$db->sql_freeresult($result);

					delete_pm($user_id, $msg_ids, PRIVMSGS_OUTBOX);

					add_log('admin', 'LOG_USER_DEL_OUTBOX', $user_row['username']);
				}
				$db->sql_freeresult($result);
			}

			// Empty the user's profile fields
			if ($config['asacp_ocban_delete_profile_fields'])
			{
				$sql_ary = array(
					'user_birthday' 	=> '',
					'user_from'			=> '',
					'user_icq'			=> '',
					'user_aim'			=> '',
					'user_yim'			=> '',
					'user_msnm'			=> '',
					'user_jabber'		=> '',
					'user_website'		=> '',
					'user_occ'			=> '',
					'user_interests'	=> '',
				);
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . $user_id;
				$db->sql_query($sql);
			}

			// Submit the information to Stop Forum Spam
			if (isset($_POST['sfs_submit']) && $config['asacp_sfs_key'])
			{
				$data = array(
					'username'	=> $user_row['username'],
					'email'		=> $user_row['user_email'],
					'ip_addr'	=> $user_row['user_ip'],
					'evidence'	=> substr(utf8_normalize_nfc(request_var('sfs_evidence', '', true)), 0, 7999), // Evidence is limited to 8,000 characters
					'api_key'	=> $config['asacp_sfs_key'],
				);

				$errno = $errstr = '';
				$domain = 'www.stopforumspam.com';
				$fp = @fsockopen($domain, 80, $errno, $errstr, 5);
				if ($fp)
				{
					$post = http_build_query($data);

				    $out = "POST /add HTTP/1.0\r\n";
				    $out .= "Host: $domain\r\n";
					$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
				    $out .= 'Content-Length: ' . strlen($post) . "\r\n\r\n";
				    $out .= "$post\r\n";
				    $out .= "Connection: close\r\n";

				    fwrite($fp, $out);
				    fclose($fp);
				}
			}

			trigger_error(sprintf($user->lang['ASACP_BAN_COMPLETE'], append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=viewprofile&amp;u=$user_id")));
		}
		else
		{
			if (isset($_REQUEST['confirm_key']) && $error)
			{
				// Hack to fix the confirm_box if we need to come back to it because of an error
				unset($_REQUEST['confirm_key']);
			}

			// Build the ban actions string
			$user->add_lang('mods/acp_asacp');
			$ban_actions = array();
			if ($config['asacp_ocban_username'])
			{
				$ban_actions[] = $user->lang['ASACP_BAN_USERNAME'];
			}
			if ($config['asacp_ocban_deactivate'])
			{
				$ban_actions[] = $user->lang['ASACP_BAN_DEACTIVATE_USER'];
			}
			if ($config['asacp_ocban_move_to_group'])
			{
				$sql = 'SELECT group_name FROM ' . GROUPS_TABLE . ' WHERE group_id = ' . $config['asacp_ocban_move_to_group'];
				$result = $db->sql_query($sql);
				$group_name = $db->sql_fetchfield('group_name');
				$db->sql_freeresult($result);

				$group_name = (isset($user->lang['G_' . $group_name])) ? $user->lang['G_' . $group_name] : $group_name;

				$ban_actions[] = $user->lang['ASACP_BAN_MOVE_TO_GROUP'] . ': ' . $group_name;
			}
			if ($config['asacp_ocban_delete_posts'])
			{
				$ban_actions[] = $user->lang['ASACP_BAN_DELETE_POSTS'];
			}
			if ($config['asacp_ocban_delete_avatar'])
			{
				$ban_actions[] = $user->lang['ASACP_BAN_DELETE_AVATAR'];
			}
			if ($config['asacp_ocban_delete_signature'])
			{
				$ban_actions[] = $user->lang['ASACP_BAN_DELETE_SIGNATURE'];
			}
			if ($config['asacp_ocban_clear_outbox'])
			{
				$ban_actions[] = $user->lang['ASACP_BAN_CLEAR_OUTBOX'];
			}
			if ($config['asacp_ocban_delete_profile_fields'])
			{
				$ban_actions[] = $user->lang['ASACP_BAN_DELETE_PROFILE_FIELDS'];
			}

			$template->assign_vars(array(
				'S_BAN_USER'			=> $config['asacp_ocban_username'],
				'S_SFS_SUBMIT'			=> ($config['asacp_sfs_key']) ? true : false,
				'BAN_REASON'			=> utf8_normalize_nfc(request_var('ban_reason', '', true)),
				'SFS_SUBMIT'			=> (isset($_POST['sfs_submit'])) ? true : false,
				'SFS_EVIDENCE'			=> utf8_normalize_nfc(request_var('sfs_evidence', '', true)),
				'SFS_EVIDENCE_ERROR'	=> ($error) ? true : false,

				'L_ASACP_BAN_ACTIONS'	=> sprintf($user->lang['ASACP_BAN_ACTIONS'], implode(', ', $ban_actions)),
			));

			$user->lang['ASACP_BAN_CONFIRM'] = sprintf($user->lang['ASACP_BAN_CONFIRM'], $username);
			confirm_box(false, 'ASACP_BAN', '', 'antispam/oc_ban.html', "antispam/index.{$phpEx}?mode=ocban&amp;u=$user_id");
		}
	break;

	default :
		trigger_error('NO_MODE');
	break;
}

// Should not get here (unless No selected for the confirm_box)
redirect($return_url);

?>