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
$user->setup();

$mode = request_var('mode', '');
$user_id = request_var('u', 0);
$post_id = request_var('p', 0);

$return = append_sid("{$phpbb_root_path}memberlist.$phpEx", "mode=viewprofile&amp;u=$user_id");
if ($post_id)
{
	$return = append_sid("{$phpbb_root_path}viewtopic.$phpEx", "p=$post_id#p$post_id");
}
$return = '<br /><br />' . sprintf($user->lang['RETURN_PAGE'], '<a href="' . $return . '">', '</a>');

switch ($mode)
{
	case 'user_flag' :
		if (!$auth->acl_get('a_asacp'))
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

			add_log('mod', 0, 0, 'LOG_USER_FLAGGED', $username);
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
		if (!$auth->acl_get('a_asacp'))
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

			add_log('mod', 0, 0, 'LOG_USER_UNFLAGGED', $username);
			add_log('admin', 'LOG_USER_UNFLAGGED', $username);
			trigger_error($user->lang['USER_UNFLAG_SUCCESS'] . $return);
		}
		else
		{
			$user->lang['USER_UNFLAG_CONFIRM'] = sprintf($user->lang['USER_UNFLAG_CONFIRM'], $username);
			confirm_box(false, 'USER_UNFLAG');
		}
	break;

	default :
		trigger_error('NO_MODE');
	break;
}

?>