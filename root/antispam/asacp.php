<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/* TODO
* Spam information in MCP/Moderator Permissions
*/

/* DO NOT FORGET
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

define('ASACP_VERSION', '0.3.3'); // Do not forget to update in update_asacp.php

define('SPAM_WORDS_TABLE', $table_prefix . 'spam_words');
define('SPAM_LOG_TABLE', $table_prefix . 'spam_log');
define('LOG_SPAM', 6); // Removed as of 0.3.2, keeping for updates

$user->add_lang('mods/asacp');

if (!isset($config['asacp_version']) || $config['asacp_version'] != ASACP_VERSION)
{
	include($phpbb_root_path . 'antispam/update_asacp.' . $phpEx);
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
		'signature'		=> array('lang' => 'SIGNATURE', 'db' => 'user_sig'),
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
					if (isset($data[$field]) && !$data[$field])
					{
						$error[] = sprintf($user->lang['FIELD_REQUIRED'], $user->lang[$ary['lang']]);
					}
				break;

				case 2 :
					// Normal
				break;

				case 3 :
					// Never allowed
					if (isset($data[$field]) && $data[$field])
					{
						$error[] = sprintf($user->lang['FIELD_TOO_LONG'], $user->lang[$ary['lang']], 0);
					}
				break;

				case 4 :
					// Post Count
					if ($user->data['user_posts'] < $config['asacp_profile_' . $field . '_post_limit'])
					{
						if (isset($data[$field]) && $data[$field])
						{
							$error[] = sprintf($user->lang['FIELD_TOO_LONG'], $user->lang[$ary['lang']], 0);
						}
					}
				break;
			}
		}

		if (!sizeof($error) && $user->data['user_flagged'])
		{
			self::add_log('LOG_ALTERED_PROFILE', array(), 'flag');
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
		global $config, $template, $user;

		if (!$config['asacp_enable'])
		{
			return;
		}

		$submit = (isset($_POST['submit'])) ? true : false;

		if ($submit)
		{
			if ($config['asacp_profile_signature'] == 3 || ($config['asacp_profile_signature'] == 4 && $user->data['user_posts'] < $config['asacp_profile_signature_post_limit']))
			{
				$error[] = sprintf($user->lang['FIELD_TOO_LONG'], $user->lang['SIGNATURE'], 0);
			}

			if ($config['asacp_spam_words_posting_action'] && self::spam_words($signature))
			{
				antispam::add_log('LOG_SPAM_SIGNATURE_DENIED', $signature);
				$error[] = $user->lang['PROFILE_SPAM_DENIED'];
			}

			if (!sizeof($error) && $user->data['user_flagged'])
			{
				self::add_log('LOG_ALTERED_SIGNATURE', array(), 'flag');
			}
		}

		if ($config['asacp_profile_signature'] == 3 || ($config['asacp_profile_signature'] == 4 && $user->data['user_posts'] < $config['asacp_profile_signature_post_limit']))
		{
			$template->assign_var('S_SIGNATURE_DISABLED', true);
		}
	}
	//public static function ucp_signature($signature, &$error)

	/**
	* Viewtopic Flagged Output
	*
	* @param int $poster_id The poster_id
	* @param array $poster_row The array of information on the user
	* @param int $post_id The post ID
	*/
	public static function viewtopic_flagged_output($poster_id, $poster_row, $post_id)
	{
		global $auth, $config;

		if (!$config['asacp_enable'] || !$auth->acl_get('a_asacp'))
		{
			return;
		}

		if (isset($poster_row['user_flagged']))
		{
			global $phpbb_root_path, $phpEx, $user, $template;

			if ($poster_row['user_flagged'])
			{
				$flagged_value = '<span class="error">' . $user->lang['YES'] . '</span> [ <a href="' . append_sid("{$phpbb_root_path}antispam/index.$phpEx", "mode=user_unflag&amp;u={$poster_id}&amp;p=$post_id") . '">' . $user->lang['USER_UNFLAG']. '</a> ]';
			}
			else
			{
				$flagged_value = $user->lang['NO'] . ' [ <a href="' . append_sid("{$phpbb_root_path}antispam/index.$phpEx", "mode=user_flag&amp;u={$poster_id}&amp;p=$post_id") . '">' . $user->lang['USER_FLAG']. '</a> ]';
			}

			$template->assign_block_vars('postrow.custom_fields', array(
				'PROFILE_FIELD_NAME'		=> $user->lang['USER_FLAGGED'],
				'PROFILE_FIELD_VALUE'		=> $flagged_value,
				'S_FIELD_VT'				=> true, // For compatibility with the Select viewable Custom Profiles Mod
				'S_FIELD_VP'				=> true, // For compatibility with the Select viewable Custom Profiles Mod
			));
		}
	}
	//public static function viewtopic_flagged_output($poster_id, $poster_row)

	/**
	* Submit Post
	*
	* Should be run when a post is submitted to check the user flag
	*
	* @param mixed $mode
	* @param mixed $post_id
	*/
	public static function submit_post($mode, $post_id)
	{
		global $user;

		if ($user->data['user_flagged'])
		{
			if ($mode == 'edit')
			{
				self::add_log('LOG_EDITED_POST', array('post_id' => $post_id), 'flag');
			}
			else
			{
				self::add_log('LOG_ADDED_POST', array('post_id' => $post_id), 'flag');
			}
		}
	}
	//public static function submit_post($mode, $post_id)

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
	*
	* @param string $type The type of log.  spam for the normal spam log, flag for an event by a flagged user.
	*/
	public static function add_log($action, $data = array(), $type = 'spam')
	{
		global $config, $db, $user, $forum_id, $topic_id;

		if (!$config['asacp_enable'] || !$config['asacp_log'])
		{
			return;
		}

		if (!is_array($data))
		{
			$data = array($data);
		}

		switch ($type)
		{
			case 'flag' :
				$log_type = 2;
			break;

			case 'spam' :
			default :
				$log_type = 1;
			break;
		}

		$sql_ary = array(
			'log_type'		=> $log_type,
			'user_id'		=> (int) (empty($user->data)) ? ANONYMOUS : $user->data['user_id'],
			'forum_id'		=> ($forum_id) ? (int) $forum_id : request_var('f', 0),
			'topic_id'		=> ($topic_id) ? (int) $topic_id : request_var('t', 0),
			'log_ip'		=> $user->ip,
			'log_time'		=> time(),
			'log_operation'	=> $action,
			'log_data'		=> serialize($data),
		);

		$db->sql_query('INSERT INTO ' . SPAM_LOG_TABLE . ' ' . $db->sql_build_array('INSERT', $sql_ary));

		return $db->sql_nextid();
	}
	//public static function add_log($action, $data = array(), $type = 'spam')

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