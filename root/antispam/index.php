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
		if (!$auth->acl_get('m_asacp_ip_search'))
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

			// Delete the user's blog
			if ($config['asacp_ocban_blog'] && file_exists($phpbb_root_path . 'blog/includes/functions_admin.' . $phpEx))
			{
				if (!function_exists('blog_delete_user'))
				{
					include($phpbb_root_path . 'blog/includes/functions_admin.' . $phpEx);
				}

				blog_delete_user($user_id);
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

			// Submit the spam to Akismet
			if (isset($_POST['akismet_submit']) && $config['asacp_akismet_enable'] && $config['asacp_akismet_key'] && ($post_id = request_var('p', 0)))
			{
				$sql = 'SELECT * FROM ' . POSTS_TABLE . '
					WHERE post_id = ' . $post_id;
				$result = $db->sql_query($sql);
				$post = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				if ($post)
				{
					if (!class_exists('Akismet'))
					{
						global $phpbb_root_path, $phpEx;
						include($phpbb_root_path . 'antispam/Akismet.class.' . $phpEx);
					}

					$post['decoded_text'] = $post['post_text'];
					decode_message($post['decoded_text'], $post['bbcode_uid']);

					$akismet = new Akismet($config['asacp_akismet_domain'], $config['asacp_akismet_key']);
					$akismet->setUserIP($post['poster_ip']);
					$akismet->setReferrer('');
					$akismet->setCommentUserAgent('');
					$akismet->setCommentType('comment');
					$akismet->setCommentAuthor($user_row['username']);
					$akismet->setCommentAuthorEmail($user_row['user_email']);
					$akismet->setCommentContent($post['decoded_text']);

					$akismet->submitSpam();
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
			if ($config['asacp_ocban_blog'] && file_exists($phpbb_root_path . 'blog/includes/functions_admin.' . $phpEx))
			{
				$ban_actions[] = $user->lang['ASACP_BAN_DELETE_BLOG'];
			}

			$post = false;
			if (($post_id = request_var('p', 0)))
			{
				$sql = 'SELECT * FROM ' . POSTS_TABLE . '
					WHERE post_id = ' . $post_id;
				$result = $db->sql_query($sql);
				$post = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);

				if ($post)
				{
					$post['decoded_text'] = $post['post_text'];
					decode_message($post['decoded_text'], $post['bbcode_uid']);
				}
			}

			$template->assign_vars(array(
				'POST_TEXT'		   		=> (is_array($post)) ? $post['post_text'] : false,
				'S_BAN_USER'			=> $config['asacp_ocban_username'],
				'S_AKISMET_SUBMIT'		=> ($config['asacp_akismet_enable'] && $config['asacp_akismet_key'] && is_array($post)) ? true : false,
				'S_SFS_SUBMIT'			=> ($config['asacp_sfs_key']) ? true : false,
				'BAN_REASON'			=> utf8_normalize_nfc(request_var('ban_reason', '', true)),
				'AKISMET_SUBMIT'		=> (isset($_POST['akismet_submit'])) ? true : false,
				'AKISMET_TEXT'	   		=> (is_array($post)) ? $post['decoded_text'] : '',
				'SFS_SUBMIT'			=> (isset($_POST['sfs_submit'])) ? true : false,
				'SFS_EVIDENCE'			=> (!isset($_POST['confirm']) && !request_var('sfs_evidence', '', true) && is_array($post)) ? $post['decoded_text'] : utf8_normalize_nfc(request_var('sfs_evidence', '', true)),
				'SFS_EVIDENCE_ERROR'	=> ($error) ? true : false,

				'L_ASACP_BAN_ACTIONS'	=> sprintf($user->lang['ASACP_BAN_ACTIONS'], implode(', ', $ban_actions)),
			));

			$user->lang['ASACP_BAN_CONFIRM'] = sprintf($user->lang['ASACP_BAN_CONFIRM'], $username);
			confirm_box(false, 'ASACP_BAN', '', 'antispam/oc_ban.html', "antispam/index.{$phpEx}?mode=ocban&amp;u=$user_id&amp;p=$post_id");
		}
	break;

	default :
		trigger_error('NO_MODE');
	break;
}

// Should not get here (unless No selected for the confirm_box)
redirect($return_url);

?>