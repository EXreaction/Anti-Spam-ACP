<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/* DO NOT FORGET
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

define('ASACP_VERSION', '0.1.10');
define('SPAM_WORDS_TABLE', $table_prefix . 'spam_words');
define('LOG_SPAM', 6);

$user->add_lang('mods/asacp');

if (!isset($config['asacp_version']) || $config['asacp_version'] != ASACP_VERSION)
{
	include($phpbb_root_path . 'includes/antispam/update_asacp.' . $phpEx);
}

class antispam
{
	// Profile Fields name => language
	public static $profile_fields = array(
		'icq'			=> array('lang' => 'UCP_ICQ', 'db' => 'user_icq'),
		'aim'			=> array('lang' => 'UCP_AIM', 'db' => 'user_aim'),
		'msn'			=> array('lang' => 'UCP_MSNM', 'db' => 'user_msnm'),
		'yim'			=> array('lang' => 'UCP_YIM', 'db' => 'user_yim'),
		'jabber'		=> array('lang' => 'UCP_JABBER', 'db' => 'user_jabber'),
		'website'		=> array('lang' => 'WEBSITE', 'db' => 'user_website'),
		'location'		=> array('lang' => 'LOCATION', 'db' => 'user_from'),
		'occupation'	=> array('lang' => 'OCCUPATION', 'db' => 'user_occ'),
		'interests'		=> array('lang' => 'INTERESTS', 'db' => 'user_interests'),
	);

	/**
	* UCP Register Operations
	*/
	public static function ucp_register()
	{
		global $config, $db, $phpbb_root_path, $phpEx, $template, $user;

		if (!$config['asacp_enable'] || !$config['asacp_reg_captcha'])
		{
			return array();
		}

		$asacp_id = request_var('asacp_id', '');
		$asacp_code = request_var('asacp_code', '');

		$wrong_confirm = true;
		if ($asacp_id)
		{
			$sql = 'SELECT code
				FROM ' . CONFIRM_TABLE . "
				WHERE confirm_id = '" . $db->sql_escape($asacp_id) . "'
					AND session_id = '" . $db->sql_escape($user->session_id) . "'
					AND confirm_type = " . CONFIRM_REG;
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if ($row)
			{
				if (strcasecmp($row['code'], $asacp_code) === 0)
				{
					$wrong_confirm = false;
				}
				else
				{
					self::add_log('LOG_INCORRECT_CODE', array($row['code'], $asacp_code));
				}
			}
		}

		if ($wrong_confirm)
		{
			$user->confirm_gc(CONFIRM_REG);

			$sql = 'SELECT COUNT(session_id) AS attempts
				FROM ' . CONFIRM_TABLE . "
				WHERE session_id = '" . $db->sql_escape($user->session_id) . "'
					AND confirm_type = " . CONFIRM_REG;
			$result = $db->sql_query($sql);
			$attempts = (int) $db->sql_fetchfield('attempts');
			$db->sql_freeresult($result);

			if ($config['max_reg_attempts'] && $attempts > $config['max_reg_attempts'])
			{
				trigger_error('TOO_MANY_REGISTERS');
			}

			$code = gen_rand_string(mt_rand(5, 8));
			$asacp_id = md5(unique_id($user->ip));
			$seed = hexdec(substr(unique_id(), 4, 10));

			// compute $seed % 0x7fffffff
			$seed -= 0x7fffffff * floor($seed / 0x7fffffff);

			$sql = 'INSERT INTO ' . CONFIRM_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'confirm_id'	=> (string) $asacp_id,
				'session_id'	=> (string) $user->session_id,
				'confirm_type'	=> (int) CONFIRM_REG,
				'code'			=> (string) $code,
				'seed'			=> (int) $seed,
			));
			$db->sql_query($sql);

			$template->assign_vars(array(
				'CONFIRM_IMG'			=> '<img src="' . append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=confirm&amp;id=' . $asacp_id . '&amp;type=' . CONFIRM_REG) . '" alt="" title="" />',

				'S_CONFIRM_CODE_WRONG'	=> (isset($_POST['submit'])) ? true : false,
				'S_HIDDEN_FIELDS'		=> '<input type="hidden" name="asacp_id" value="' . $asacp_id . '" />',
				'S_UCP_ACTION'			=> append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=register'),

				'L_CONFIRM_EXPLAIN'		=> sprintf($user->lang['CONFIRM_EXPLAIN'], '<a href="mailto:' . htmlspecialchars($config['board_contact']) . '">', '</a>'),
			));

			return false;
		}
		else
		{
			return array(
				'asacp_id'		=> $asacp_id,
				'asacp_code'	=> $asacp_code,
			);
		}
	}
	//public static function ucp_register()

	/**
	* UCP Profile Fields Operations
	*/
	public static function ucp_profile($data, &$error)
	{
		global $config, $user;

		if (!$config['asacp_enable'])
		{
			return;
		}

		if ($config['asacp_spam_words_posting_action'] && self::spam_words($data))
		{
			$spam_message = antispam::build_spam_log_message($data);
			antispam::add_log('LOG_SPAM_PROFILE_DENIED', $spam_message);
			$error[] = $user->lang['PROFILE_SPAM_DENIED'];
		}

		foreach (self::$profile_fields as $field => $ary)
		{
			switch ($config['asacp_profile_' . $field])
			{
				case 1 :
					// Required
					if (!$data[$field])
					{
						$error[] = sprintf($user->lang['FIELD_REQUIRED'], $user->lang[$ary['lang']]);
					}
				break;

				case 2 :
					// Normal
				break;

				case 3 :
					// Never allowed
					if ($data[$field])
					{
						$error[] = sprintf($user->lang['FIELD_TOO_LONG'], $user->lang[$ary['lang']], 0);
					}
				break;

				case 4 :
					// Post Count
					if ($user->data['user_posts'] < $config['asacp_profile_' . $field . '_post_limit'])
					{
						if ($data[$field])
						{
							$error[] = sprintf($user->lang['FIELD_TOO_LONG'], $user->lang[$ary['lang']], 0);
						}
					}
				break;
			}
		}
	}
	//public static function ucp_profile($data, &$error)

	public static function ucp_profile_display()
	{
		global $config, $user, $template;

		if (!$config['asacp_enable'])
		{
			return;
		}

		foreach (self::$profile_fields as $field => $lang)
		{
			switch ($config['asacp_profile_' . $field])
			{
				case 1 :
					// Required
					$template->assign_var('S_' . strtoupper($field) . '_REQUIRED', true);
				break;

				case 2 :
					// Normal
				break;

				case 3 :
					// Never allowed
					$template->assign_var('S_' . strtoupper($field) . '_DISABLED', true);
				break;

				case 4 :
					// Post Count
					if ($user->data['user_posts'] < $config['asacp_profile_' . $field . '_post_limit'])
					{
						$template->assign_var('S_' . strtoupper($field) . '_DISABLED', true);
					}
				break;
			}
		}
	}
	//public static function ucp_profile_display()

	public static function ucp_signature($signature, &$error)
	{
		global $config, $user;

		if (!$config['asacp_enable'])
		{
			return;
		}

		if ($config['asacp_spam_words_posting_action'] && self::spam_words($signature))
		{
			antispam::add_log('LOG_SPAM_SIGNATURE_DENIED', $signature);
			$error[] = $user->lang['PROFILE_SPAM_DENIED'];
		}
	}
	//public static function ucp_signature()

	/**
	* Spam Word Operations
	*
	* Send a message or array of messages.  If the message (or any in the array of messages) are flagged as spam, true is returned.
	*
	* @param string|array $data The message or array of messages to check
	* @param int|bool $post_count The post count that you would like to use (for example, if the check is ran for a different user).  Leave as false to use $user->data['user_posts']
	* @param int|bool $flag_limit The flag limit to see if we will flag a message as spam.  Leave as false to use $config['asacp_spam_words_flag_limit']
	*
	* @return bool True if the message(s) are flagged as spam, false if not
	*/
	public static function spam_words($data, $post_count = false, $flag_limit = false)
	{
		global $cache, $config, $db, $user;

		if (!$config['asacp_enable'] || !$config['asacp_spam_words_enable'] || ($post_count > $config['asacp_spam_words_post_limit'] && $config['asacp_spam_words_post_limit'] > 0))
		{
			return false;
		}

		if ($post_count === false)
		{
			$post_count = $user->data['user_posts'];
		}

		if (!class_exists('spam_words'))
		{
			global $phpbb_root_path, $phpEx;
			include($phpbb_root_path . 'includes/antispam/spam_words.' . $phpEx);
		}

		$spam_words = new spam_words();
		$spam_words->messages = (!is_array($data)) ? array($data) : $data;
		$spam_words->check_messages();

		$flag_limit = (is_numeric($flag_limit) && $flag_limit > 0) ? $flag_limit : $config['asacp_spam_words_flag_limit'];
		return ($spam_words->spam_flags >= $flag_limit) ? true : false;
	}
	//public static function spam_words($data, $post_count = false)

	/**
	* Add spam log event
	*/
	public static function add_log($action, $data = array())
	{
		global $config, $db, $user;

		if (!$config['asacp_enable'] || !$config['asacp_log'])
		{
			return;
		}

		if (!is_array($data))
		{
			$data = array($data);
		}

		$sql_ary = array(
			'log_type'		=> LOG_SPAM,
			'user_id'		=> (empty($user->data)) ? ANONYMOUS : $user->data['user_id'],
			'log_ip'		=> $user->ip,
			'log_time'		=> time(),
			'log_operation'	=> $action,
			'log_data'		=> serialize($data),
		);

		$db->sql_query('INSERT INTO ' . LOG_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));

		return $db->sql_nextid();
	}
	//public static function add_log($mode, $action, $data = '')

	/**
	* View log
	*/
	public static function view_log(&$log, &$log_count, $limit = 0, $offset = 0, $limit_days = 0, $sort_by = 'l.log_time DESC')
	{
		global $db, $user, $auth, $phpEx, $phpbb_root_path, $phpbb_admin_path;

		$is_auth = $is_mod = array();

		$profile_url = (defined('IN_ADMIN')) ? append_sid("{$phpbb_admin_path}index.$phpEx", 'i=users&amp;mode=overview') : append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile');

		$sql = "SELECT l.*, u.username, u.username_clean, u.user_colour
			FROM " . LOG_TABLE . " l, " . USERS_TABLE . " u
			WHERE l.log_type = " . LOG_SPAM . "
				AND u.user_id = l.user_id
				" . (($limit_days) ? "AND l.log_time >= $limit_days" : '') . "
			ORDER BY $sort_by";
		$result = $db->sql_query_limit($sql, $limit, $offset);

		$i = 0;
		$log = array();
		while ($row = $db->sql_fetchrow($result))
		{
			$log[$i] = array(
				'id'				=> $row['log_id'],

				'reportee_id'			=> '',
				'reportee_username'		=> '',
				'reportee_username_full'=> '',

				'user_id'			=> $row['user_id'],
				'username'			=> $row['username'],
				'username_full'		=> get_username_string('full', $row['user_id'], $row['username'], $row['user_colour'], false, $profile_url),

				'ip'				=> $row['log_ip'],
				'time'				=> $row['log_time'],
				'forum_id'			=> $row['forum_id'],
				'topic_id'			=> $row['topic_id'],

				'viewforum'			=> false,
				'action'			=> (isset($user->lang[$row['log_operation']])) ? $user->lang[$row['log_operation']] : '{' . ucfirst(str_replace('_', ' ', $row['log_operation'])) . '}',
				'operation'			=> $row['log_operation'],
				'data'				=> unserialize($row['log_data']),
			);

			if (!empty($row['log_data']))
			{
				$log_data_ary = unserialize($row['log_data']);

				if (isset($user->lang[$row['log_operation']]))
				{
					// We supress the warning about inappropriate number of passed parameters here due to possible changes within LOG strings from one version to another.
					$log[$i]['action'] = @vsprintf($log[$i]['action'], $log_data_ary);

					// If within the admin panel we do not censor text out
					if (defined('IN_ADMIN'))
					{
						$log[$i]['action'] = bbcode_nl2br($log[$i]['action']);
					}
					else
					{
						$log[$i]['action'] = bbcode_nl2br(censor_text($log[$i]['action']));
					}
				}
				else
				{
					$log[$i]['action'] .= '<br />' . implode('', $log_data_ary);
				}

				/* Apply make_clickable... has to be seen if it is for good. :/
				// Seems to be not for the moment, reconsider later...
				$log[$i]['action'] = make_clickable($log[$i]['action']);
				*/
			}

			$i++;
		}
		$db->sql_freeresult($result);

		$sql = 'SELECT COUNT(l.log_id) AS total_entries
			FROM ' . LOG_TABLE . " l
			WHERE l.log_type = " . LOG_SPAM . "
				AND l.log_time >= $limit_days";
		$result = $db->sql_query($sql);
		$log_count = (int) $db->sql_fetchfield('total_entries');
		$db->sql_freeresult($result);

		return;
	}
	//public static function view_log(&$log, &$log_count, $limit = 0, $offset = 0, $limit_days = 0, $sort_by = 'l.log_time DESC')

	/**
	* Builds a single message for the spam log from multiple items
	*
	* Designed for the ucp_profile LOG_SPAM_PROFILE_DENIED section
	*/
	public function build_spam_log_message($data)
	{
		global $user;

		$skip = array('bday_year', 'bday_month', 'bday_day', 'user_birthday');

		$message = '';
		foreach ($data as $name => $value)
		{
			if (!in_array($name, $skip) && $value)
			{
				if (isset($user->lang[strtoupper($name)]))
				{
					$message .= $user->lang[strtoupper($name)] . '<br />';
				}
				else
				{
					$message .= strtoupper($name) . '<br />';
				}

				$message .= $value . '<br /><br />';
			}
		}

		return $message;
	}
	//public function build_spam_log_message($data)

	/**
	* Get the latest version number from Lithium Studios
	*/
	public static function version_check()
	{
		global $cache;

		$version = $cache->get('asacp_version');
		if ($version === false)
		{
			if (!function_exists('get_remote_file'))
			{
				global $phpbb_root_path, $phpEx;
				include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
			}

			$errstr = $errno = '';
			$version = get_remote_file('lithiumstudios.org', '/updatecheck', 'anti_spam_acp_3_version.txt', $errstr, $errno);

			$cache->put('asacp_version', $version, 3600);
		}

		return $version;
	}
	//public static function version_check()
}
?>