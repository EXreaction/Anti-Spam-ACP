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
if (!defined('IN_PHPBB'))
{
	exit;
}

class mcp_asacp
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache;
		global $config, $phpbb_root_path, $phpEx;

		$user->add_lang(array('acp/board', 'mods/asacp', 'mods/acp_asacp', 'install', 'acp/common'));
		include($phpbb_root_path . 'antispam/acp_functions.' . $phpEx);

		$error = $notify = array();
		$submit = (isset($_POST['submit'])) ? true : false;
		$action = request_var('action', '');

		add_form_key('as_acp');
		if ($submit && !check_form_key('as_acp'))
		{
			trigger_error($user->lang['FORM_INVALID'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$error = $options = array();

		switch ($mode)
		{
			case 'ip_search' :
				$this->tpl_name = 'antispam/mcp_asacp';
				$this->page_title = 'ASACP_IP_SEARCH';

				$ip = request_var('ip', '');
				$type = request_var('type', '');
				if ($ip)
				{
					asacp_display_ip_search($type, $ip, $this->u_action . '&amp;ip=' . $ip, request_var('start', 0));
				}

				$template->assign_vars(array(
					'L_TITLE'				=> $user->lang['ASACP_IP_SEARCH'],
					'L_TITLE_EXPLAIN'		=> $user->lang['ASACP_IP_SEARCH_EXPLAIN'],
					'S_DATA_OUTPUT'			=> true,
					'S_DISPLAY_IP_INPUT'	=> ($ip) ? false : true,

					'U_BACK'				=> ($type) ? $this->u_action . '&amp;ip=' . $ip : false,
					'U_BACK_NONE'			=> $this->u_action,
				));
			break;
			// case 'ip_search' :

			case 'log' :
			case 'flag' :
				$this->tpl_name = 'mcp_logs';

				if ($mode == 'log')
				{
					$this->page_title = $user->lang['ASACP_SPAM_LOG'];
				}
				else
				{
					$this->page_title = $user->lang['ASACP_FLAG_LOG'];

					// Reset the user flag new notification
					if ($user->data['user_flag_new'])
					{
						$db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_flag_new = 0 WHERE user_id = ' . $user->data['user_id']);
					}
				}

				$user->add_lang('mcp');

				// Set up general vars
				$start		= request_var('start', 0);
				$action = request_var('action', array('' => ''));
				if (is_array($action))
				{
					list($action, ) = each($action);
				}
				else
				{
					$action = request_var('action', '');
				}
				$deletemark = (!empty($_POST['delmarked']) || $action == 'del_marked') ? true : false;
				$deleteall	= (!empty($_POST['delall']) || $action == 'del_all') ? true : false;
				$marked		= request_var('mark', array(0));

				// Sort keys
				$sort_days	= request_var('st', 0);
				$sort_key	= request_var('sk', 't');
				$sort_dir	= request_var('sd', 'd');

				$keywords = utf8_normalize_nfc(request_var('keywords', '', true));
				$keywords_param = !empty($keywords) ? '&amp;keywords=' . urlencode(htmlspecialchars_decode($keywords)) : '';

				// Delete entries if requested and able
				if (($deletemark || $deleteall) && $auth->acl_get('a_clearlogs'))
				{
					if (confirm_box(true))
					{
						clear_spam_log($mode, (($deletemark) ? false : $deleteall), $marked, $keywords);
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
							'keywords'	=> $keywords,
							'i'			=> $id,
							'mode'		=> $mode,
							'action'	=> $this->u_action))
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

				if ($mode == 'log')
				{
					view_spam_log('spam', $log_data, $log_count, $config['topics_per_page'], $start, '', $sql_days, $sql_sort, $keywords);
				}
				else
				{
					view_spam_log('flag', $log_data, $log_count, $config['topics_per_page'], $start, '', $sql_days, $sql_sort, $keywords);
				}

				$template->assign_vars(array(
					'L_TITLE'		=> $this->page_title,
					'L_EXPLAIN'		=> '',

					'S_ON_PAGE'		=> on_page($log_count, $config['topics_per_page'], $start),
					'PAGE_NUMBER'	=> on_page($log_count, $config['topics_per_page'], $start),
					'PAGINATION'	=> generate_pagination($this->u_action . "&amp;$u_sort_param$keywords_param", $log_count, $config['topics_per_page'], $start),
					'TOTAL'			=> ($log_count == 1) ? $user->lang['TOTAL_LOG'] : sprintf($user->lang['TOTAL_LOGS'], $log_count),

					'S_LIMIT_DAYS'			=> $s_limit_days, // Yes, these duplicates are shit, but the acp/mcp use different variables
					'S_SELECT_SORT_DAYS'	=> $s_limit_days,
					'S_SORT_KEY'			=> $s_sort_key,
					'S_SELECT_SORT_KEY'		=> $s_sort_key,
					'S_SORT_DIR'			=> $s_sort_dir,
					'S_SELECT_SORT_DIR'		=> $s_sort_dir,

					'S_CLEARLOGS'	   		=> $auth->acl_get('a_clearlogs'),
					'S_CLEAR_ALLOWED'	   	=> $auth->acl_get('a_clearlogs'),
					'S_KEYWORDS'			=> $keywords,
					'S_LOGS'	   			=> ($log_count > 0) ? true : false,
				));

				foreach ($log_data as $row)
				{
					$template->assign_block_vars('log', array(
						'USERNAME'			=> $row['username_full'],
						'REPORTEE_USERNAME'	=> ($row['reportee_username'] && $row['user_id'] != $row['reportee_id']) ? $row['reportee_username_full'] : '',

						'IP'				=> '<a href="' . append_sid("{$phpbb_root_path}mcp.$phpEx", "i={$id}&amp;mode=ip_search&amp;ip={$row['ip']}") . '">' . $row['ip'] . '</a>',
						'DATE'				=> $user->format_date($row['time']),
						'ACTION'			=> $row['action'],
						'DATA'				=> (sizeof($row['data'])) ? @vsprintf($user->lang[$row['operation'] . '_DATA'], $row['data']) : '',
						'ID'				=> $row['id'],
					));
				}
			break;
			//case 'log' :
			//case 'flag' :

			case 'flag_list' :
				$user->add_lang('memberlist');
				$this->tpl_name = 'antispam/mcp_asacp';
				$this->page_title = 'ASACP_FLAG_LIST';

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

					if ($auth->acl_get('m_asacp_ip_search'))
					{
						$row['user_ip'] = '<a href="' . append_sid("{$phpbb_root_path}mcp.$phpEx", "i={$id}&amp;mode=ip_search&amp;ip={$row['user_ip']}") . '">' . $row['user_ip'] . '</a>';
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
					'PAGINATION'	=> ($total) ? generate_pagination($this->u_action . "&amp;limit=$limit", $total, $limit, $start, false, 'data_output') : '',
				));
			break;
			//case 'flag_list' :

			default:
				trigger_error('NO_MODE');
			break;
		}
		// switch($mode)

		// Display the options if there are any (setup similar to acp_board)
		if (sizeof($options))
		{
			asacp_display_options($options, $error, $this->u_action);
		}

        $template->assign_vars(array(
			'ERROR'			=> implode('<br />', $error),
			'U_ACTION'		=> $this->u_action,
			'U_POST_ACTION'	=> $this->u_action,
		));
	}

	function group_list($value, $key)
	{
		global $db, $user;

		$return = '<select name="config[' . $key . ']"><option value="0">--------</option>';

		$sql = 'SELECT group_id, group_founder_manage, group_name FROM ' . GROUPS_TABLE;
		$result = $db->sql_query($sql);

		while ($row = $db->sql_fetchrow($result))
		{
			if (!$row['group_founder_manage'] || $user->data['user_type'] == USER_FOUNDER)
			{
				$lang = (isset($user->lang[$row['group_name']])) ? $user->lang[$row['group_name']] : ((isset($user->lang['G_' . $row['group_name']])) ? $user->lang['G_' . $row['group_name']] : $row['group_name']);
				$return .= '<option value="' . $row['group_id'] . '"' . (($value == $row['group_id']) ? ' selected="selected"' : '') . '>' . $lang . '</option>';
			}
		}
		$db->sql_freeresult($result);

		$return .= '</select>';

		return $return;
	}

	function sfs_action($value, $key)
	{
		global $user;

		$key1	= ($value == 1) ? ' checked="checked"' : '';
		$key2	= ($value == 2) ? ' checked="checked"' : '';
		$key3	= ($value == 3) ? ' checked="checked"' : '';
		$key4	= ($value == 4) ? ' checked="checked"' : '';
		$key5	= ($value == 5) ? ' checked="checked"' : '';

		return '<label><input type="radio" name="config[' . $key . ']" value="1"' . $key1 . ' class="radio" /> ' . $user->lang['NOTHING'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="2"' . $key2 . ' class="radio" /> ' . $user->lang['FLAG_USER'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="3"' . $key3 . ' class="radio" /> ' . $user->lang['REQUIRE_USER_ACTIVATION'] . '</label><br /><br />
<label><input type="radio" name="config[' . $key . ']" value="4"' . $key4 . ' class="radio" /> ' . $user->lang['REQUIRE_ADMIN_ACTIVATION'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="5"' . $key5 . ' class="radio" /> ' . $user->lang['DENY_SUBMISSION'] . '</label>';
	}

	function profile_fields_select($value, $key)
	{
		global $user;

		$key1	= ($value == 1) ? ' checked="checked"' : '';
		$key2	= ($value == 2) ? ' checked="checked"' : '';
		$key3	= ($value == 3) ? ' checked="checked"' : '';
		$key4	= ($value == 4) ? ' checked="checked"' : '';

		return '<label><input type="radio" name="config[' . $key . ']" value="1"' . $key1 . ' class="radio" /> ' . $user->lang['REQUIRE_FIELD'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="2"' . $key2 . ' class="radio" /> ' . $user->lang['ALLOW_FIELD'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="3"' . $key3 . ' class="radio" /> ' . $user->lang['DENY_FIELD'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="4"' . $key4 . ' class="radio" /> ' . $user->lang['POST_COUNT'] . '</label>';
	}

	function spam_words_nothing_deny_approval_action($value, $key)
	{
		global $user;

		$key0	= ($value == 0) ? ' checked="checked"' : '';
		$key1	= ($value == 1) ? ' checked="checked"' : '';
		$key2	= ($value == 2) ? ' checked="checked"' : '';

		return '<label><input type="radio" name="config[' . $key . ']" value="0"' . $key0 . ' class="radio" /> ' . $user->lang['NOTHING'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="1"' . $key1 . ' class="radio" /> ' . $user->lang['DENY_SUBMISSION'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="2"' . $key2 . ' class="radio" /> ' . $user->lang['REQUIRE_APPROVAL'] . '</label>';
	}

	function spam_words_nothing_deny_action($value, $key)
	{
		global $user;

		$key0	= ($value == 0) ? ' checked="checked"' : '';
		$key1	= ($value == 1) ? ' checked="checked"' : '';

		return '<label><input type="radio" name="config[' . $key . ']" value="0"' . $key0 . ' class="radio" /> ' . $user->lang['NOTHING'] . '</label>
<label><input type="radio" name="config[' . $key . ']" value="1"' . $key1 . ' class="radio" /> ' . $user->lang['DENY_SUBMISSION'] . '</label>';
	}

    function asacp_latest_version()
	{
		global $user, $config;

		$latest_version = antispam::version_check();
		if ($latest_version === false)
		{
			$version = $user->lang['NOT_AVAILABLE'];
			$version .= '<br />' . sprintf($user->lang['CLICK_CHECK_NEW_VERSION'], '<a href="http://www.lithiumstudios.org/phpBB3/viewtopic.php?f=31&amp;t=941">', '</a>');
		}
		else
		{
			$version = $latest_version;
			if (version_compare(ASACP_VERSION, $latest_version, '<'))
			{
				$version .= '<br />' . sprintf($user->lang['CLICK_GET_NEW_VERSION'], '<a href="http://www.lithiumstudios.org/phpBB3/viewtopic.php?f=31&amp;t=941">', '</a>');
			}
		}

		return $version;
	}
}

?>