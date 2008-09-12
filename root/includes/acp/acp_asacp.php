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

class acp_asacp
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $user, $auth, $template, $cache;
		global $config, $phpbb_admin_path, $phpbb_root_path, $phpEx;

		$user->add_lang(array('acp/board', 'mods/acp_asacp', 'install'));
		include($phpbb_root_path . 'includes/antispam/acp_asacp.' . $phpEx);

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
				$this->tpl_name = 'acp_asacp';
				$this->page_title = 'ASACP_IP_SEARCH';

				$ip = request_var('ip', '');
				$type = request_var('type', '');
				if ($ip)
				{
					asacp_display_ip_search($type, $ip, $this->u_action . '&amp;ip=' . $ip, request_var('start', 0));
				}

				$template->assign_vars(array(
					'L_TITLE'			=> $user->lang['ASACP_IP_SEARCH'],
					'L_TITLE_EXPLAIN'	=> $user->lang['ASACP_IP_SEARCH_EXPLAIN'],
					'S_IP_SEARCH'		=> true,
					'S_DISPLAY_INPUT'	=> ($ip) ? false : true,

					'U_BACK'			=> ($type) ? $this->u_action . '&amp;ip=' . $ip : false,
				));
			break;
			// case 'ip_search' :

			case 'log' :
				$this->tpl_name = 'acp_logs';
				$this->page_title = $user->lang['ASACP_SPAM_LOG'];

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
							$sql = 'DELETE FROM ' . LOG_TABLE . '
								WHERE log_type = ' . LOG_SPAM .
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
							'i'			=> $id,
							'mode'		=> $mode,
							'action'	=> $action))
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
				$sql_where = ($sort_days) ? (time() - ($sort_days * 86400)) : 0;
				$sql_sort = $sort_by_sql[$sort_key] . ' ' . (($sort_dir == 'd') ? 'DESC' : 'ASC');

				// Grab log data
				$log_data = array();
				$log_count = 0;
				antispam::view_log($log_data, $log_count, $config['topics_per_page'], $start, $sql_where, $sql_sort);

				$template->assign_vars(array(
					'L_TITLE'		=> $user->lang['ASACP_SPAM_LOG'],
					'L_EXPLAIN'		=> '',

					'S_ON_PAGE'		=> on_page($log_count, $config['topics_per_page'], $start),
					'PAGINATION'	=> generate_pagination($this->u_action . "&amp;$u_sort_param", $log_count, $config['topics_per_page'], $start, true),

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

						'IP'				=> '<a href="' . append_sid("{$phpbb_admin_path}index.$phpEx", "i={$id}&amp;mode=ip_search&amp;ip={$row['ip']}") . '">' . $row['ip'] . '</a>',
						'DATE'				=> $user->format_date($row['time']),
						'ACTION'			=> $row['action'],
						'DATA'				=> (sizeof($row['data'])) ? @vsprintf($user->lang[$row['operation'] . '_DATA'], $row['data']) : '',
						'ID'				=> $row['id'],
					));
				}
			break;
			// case 'log' :

			default :
				$this->tpl_name = 'acp_asacp';
				$this->page_title = 'ASACP_SETTINGS';

				$options = array(
					'legend1'				=> 'ASACP_SETTINGS',
					'asacp_enable'			=> array('lang' => 'ASACP_ENABLE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
					'asacp_log'				=> array('lang' => 'ASACP_LOG', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),

					'legend2'				=> 'ASACP_REGISTER_SETTINGS',
					'asacp_reg_captcha'		=> array('lang' => 'ASACP_REG_CAPTCHA', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
				);

				$template->assign_vars(array(
					'L_TITLE'			=> $user->lang['ASACP_SETTINGS'],
					'L_TITLE_EXPLAIN'	=> '',
					'S_SETTINGS'		=> true,

					'CURRENT_VERSION'	=> ASACP_VERSION,
					'LATEST_VERSION'	=> $this->asacp_latest_version(),
				));
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
		));
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