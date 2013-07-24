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

define('ASACP_VERSION', '1.0.6');

define('SPAM_WORDS_TABLE', $table_prefix . 'spam_words');
define('SPAM_LOG_TABLE', $table_prefix . 'spam_log');

if (!isset($config['asacp_version']) || version_compare(ASACP_VERSION, $config['asacp_version'], '>'))
{
	if (!file_exists($phpbb_root_path . 'includes/acp/info/acp_asacp.' . $phpEx) || !file_exists($phpbb_root_path . 'includes/mcp/info/mcp_asacp.' . $phpEx))
	{
		// The module info files do not exist, they should for proper installing/updating
		trigger_error('includes/acp/info/acp_asacp.php or includes/mcp/info/mcp_asacp.php is missing');
	}

	include($phpbb_root_path . 'antispam/update_asacp.' . $phpEx);
}

class antispam
{
	/**
	* Profile Fields
	*	First is the name for the template side (in ucp_profile_profile_info.html)
	* 	lang holds the language name for when giving an error/displaying
	*	db holds the name of the field in the db used when resetting the fields to blank.
	*	field_type is the type used for building the field if required/available during registration
	*	beyond that is custom information required for when creating the fields
	*
	* field type reference (from includes/functions_profile_fields)
	*	var $profile_types = array(FIELD_INT => 'int', FIELD_STRING => 'string', FIELD_TEXT => 'text', FIELD_BOOL => 'bool', FIELD_DROPDOWN => 'dropdown', FIELD_DATE => 'date');
	*/
	public static $profile_fields = array(
		'icq'			=> array('lang' => 'UCP_ICQ', 'db' => 'user_icq', 'field_type' => FIELD_STRING),
		'aim'			=> array('lang' => 'UCP_AIM', 'db' => 'user_aim', 'field_type' => FIELD_STRING),
		'msn'			=> array('lang' => 'UCP_MSNM', 'db' => 'user_msnm', 'field_type' => FIELD_STRING),
		'yim'			=> array('lang' => 'UCP_YIM', 'db' => 'user_yim', 'field_type' => FIELD_STRING),
		'jabber'		=> array('lang' => 'UCP_JABBER', 'db' => 'user_jabber', 'field_type' => FIELD_STRING),
		'website'		=> array('lang' => 'WEBSITE', 'db' => 'user_website', 'field_type' => FIELD_STRING),
		'location'		=> array('lang' => 'LOCATION', 'db' => 'user_from', 'field_type' => FIELD_STRING),
		'occupation'	=> array('lang' => 'OCCUPATION', 'db' => 'user_occ', 'field_type' => FIELD_STRING),
		'interests'		=> array('lang' => 'INTERESTS', 'db' => 'user_interests', 'field_type' => FIELD_STRING),
		'signature'		=> array('lang' => 'SIGNATURE', 'db' => 'user_sig', 'field_type' => 'disabled'),
	);

	// True if marked as Stop Forum Spam as a spam user
	private static $sfs_spam = false;

	/**
	* UCP Pre-Register Operations (Pre-Registration Captcha)
	*/
	public static function ucp_preregister()
	{
		global $config, $db, $phpbb_root_path, $phpEx, $template, $user;

		if (!$config['asacp_enable'])
		{
			return array();
		}

		$user->add_lang('mods/asacp');

		if (!class_exists('custom_profile'))
		{
			include($phpbb_root_path . 'includes/functions_profile_fields.' . $phpEx);
		}

		// Profile fields stuff
		$cp = new custom_profile();

		foreach (self::$profile_fields as $field => $data)
		{
			if ($data['field_type'] == 'disabled')
			{
				continue;
			}

			// Special stuff for $cp->process_field_row
			$data['var_name'] = $data['field_ident'] = $field;
			$data['lang_default_value'] = '';

			switch ($config['asacp_profile_' . $field])
			{
				case 1 :
					// Required Field
					$template->assign_block_vars('profile_fields', array(
						'FIELD_ID'		=> $field,
						'LANG_NAME'		=> (isset($user->lang[$data['lang']])) ? $user->lang[$data['lang']] : $data['lang'],
						'S_REQUIRED'	=> true,
						'LANG_EXPLAIN'	=> (isset($user->lang[$data['lang'] . '_EXPLAIN'])) ? $user->lang[$data['lang'] . '_EXPLAIN'] : '',
						'FIELD' 		=> $cp->process_field_row('change', $data),
					));
				break;

				case 2 :
					if ($config['asacp_profile_during_reg'])
					{
						// Normal Field
						$template->assign_block_vars('profile_fields', array(
							'FIELD_ID'		=> $field,
							'LANG_NAME'		=> (isset($user->lang[$data['lang']])) ? $user->lang[$data['lang']] : $data['lang'],
							'LANG_EXPLAIN'	=> (isset($user->lang[$data['lang'] . '_EXPLAIN'])) ? $user->lang[$data['lang'] . '_EXPLAIN'] : '',
							'FIELD' 		=> $cp->process_field_row('change', $data),
						));
					}
				break;
			}
		}

		if (!$config['asacp_reg_captcha'])
		{
			return array();
		}
	}
	//public static function ucp_preregister()

	/**
	* UCP Register
	*
	* @param array $data Data from ucp_register
	* @param array $error
	*/
	public static function ucp_register($data, &$error)
	{
		global $config, $user;

		if (!$config['asacp_enable'])
		{
			return;
		}

		// Profile fields stuff
		foreach (self::$profile_fields as $field => $ary)
		{
			if ($ary['field_type'] == 'disabled')
			{
				continue;
			}

			switch ($config['asacp_profile_' . $field])
			{
				case 1 :
					// Required
					if (isset($_POST[$field]) && !request_var($field, ''))
					{
						$error[] = sprintf($user->lang['FIELD_REQUIRED'], $user->lang[$ary['lang']]);
					}
				break;

				case 2 :
					// Normal
					if (!$config['asacp_profile_during_reg'] && isset($_POST[$field]) && request_var($field, ''))
					{
						$error[] = sprintf($user->lang['FIELD_TOO_LONG'], $user->lang[$ary['lang']], 0);
					}
				break;

				case 3 :
					// Never allowed
				case 4 :
					// Post Count
					if (isset($_POST[$field]) && request_var($field, ''))
					{
						$error[] = sprintf($user->lang['FIELD_TOO_LONG'], $user->lang[$ary['lang']], 0);
					}
				break;
			}
		}

		// Stop Forum Spam stuff
		if (!sizeof($error) && $config['asacp_sfs_action'] > 1)
		{
			if (!function_exists('get_remote_file'))
			{
				global $phpbb_root_path, $phpEx;
				include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
			}

			$stop_forum_spam_urls = array(
				'api?username=' . urlencode($data['username']),
				'api?email=' . urlencode($data['email']),
				//'api?ip=' . $user->ip,
			);

			foreach ($stop_forum_spam_urls as $url)
			{
				$errstr = $errno = '';
				$file = get_remote_file('stopforumspam.com', '', $url, $errstr, $errno);

				if ($file !== false)
				{
					$file = str_replace("\r\n", "\n", $file);
					$file = explode("\n", $file);

					$appears = $frequency = false;
					foreach ($file as $line)
					{
						if (strpos($line, '<appears>') !== false && strpos($line, '</appears>') !== false)
						{
							$start = strpos($line, '<appears>') + 9;
							$end = strpos($line, '</appears>') - $start;
							$appears = (substr($line, $start, $end) == 'yes') ? true : false;
						}
						else if (strpos($line, '<frequency>') !== false && strpos($line, '</frequency>') !== false)
						{
							$start = strpos($line, '<frequency>') + 11;
							$end = strpos($line, '</frequency>') - $start;
							$frequency = (int) substr($line, $start, $end);
						}
					}

					if ($appears && $frequency >= $config['asacp_sfs_min_freq'])
					{
						self::$sfs_spam = true;

						if ($config['asacp_sfs_action'])
						{
							self::add_log('LOG_SPAM_USER_DENIED_SFS', array($url));
						}

						break;
					}
				}
			}

			if (self::$sfs_spam)
			{
				switch ($config['asacp_sfs_action'])
				{
					case 3 :
						$config['require_activation'] = USER_ACTIVATION_SELF;
					break;

					case 4 :
						$config['require_activation'] = USER_ACTIVATION_ADMIN;
					break;

					case 5 :
            			$user->add_lang('mods/asacp');
						$error[] = $user->lang['PROFILE_SPAM_DENIED'];
					break;
				}
			}
		}
	}
	//public static function ucp_register()

	/**
	* Stop Forum Spam registration hook (used to flag a user if their profile info was marked as spam and action is set to flag the user.
	*
	* @param mixed $user_id
	*/
	public static function ucp_postregister($user_id, $user_row)
	{
		global $config, $db, $phpbb_root_path, $phpEx;

		if (!$config['asacp_enable'])
		{
			return;
		}

		// stuff to be updated
		$profile_data = array();

		// Stop forum Spam stuff
		if (self::$sfs_spam && $config['asacp_sfs_action'] == 2)
		{
			$profile_data['user_flagged'] = 1;
			add_log('admin', 'LOG_USER_FLAGGED', $user_row['username']);
		}
		else if (self::$sfs_spam && ($config['asacp_sfs_action'] == 3 || $config['asacp_sfs_action'] == 4))
		{
			self::add_log('LOG_USER_SFS_ACTIVATION', array($user_id));
		}

		// Profile fields stuff
		foreach (self::$profile_fields as $field => $ary)
		{
			if ($ary['field_type'] == 'disabled')
			{
				continue;
			}

			switch ($config['asacp_profile_' . $field])
			{
				case 1 :
					// Required
					$profile_data[$ary['db']] = utf8_normalize_nfc(request_var($field, '', true));
				break;

				case 2 :
					// Normal
					if ($config['asacp_profile_during_reg'])
					{
						$profile_data[$ary['db']] = utf8_normalize_nfc(request_var($field, '', true));
					}
				break;
			}
		}

		// Update time
		if (sizeof($profile_data))
		{
			$db->sql_query('UPDATE ' . USERS_TABLE . ' SET ' . $db->sql_build_array('UPDATE', $profile_data) . ' WHERE user_id = ' . (int) $user_id);
		}
	}
	//public static function sfs_register($user_id)

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

		$user->add_lang('mods/asacp');

		if ($config['asacp_spam_words_profile_action'] && self::spam_words($data))
		{
			$spam_message = self::build_spam_log_message($data);
			self::add_log('LOG_SPAM_PROFILE_DENIED', $spam_message);
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

		$user->add_lang('mods/asacp');

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

		$user->add_lang('mods/asacp');

		$submit = (isset($_POST['submit'])) ? true : false;

		if ($submit)
		{
			if ($config['asacp_profile_signature'] == 3 || ($config['asacp_profile_signature'] == 4 && $user->data['user_posts'] < $config['asacp_profile_signature_post_limit']))
			{
				$error[] = sprintf($user->lang['FIELD_TOO_LONG'], $user->lang['SIGNATURE'], 0);
			}

			if ($config['asacp_spam_words_profile_action'] && self::spam_words($signature))
			{
				self::add_log('LOG_SPAM_SIGNATURE_DENIED', $signature);
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
	* Page Header
	*
	* A function used for a few things that needs to be done when page_header is called.
	*/
	public static function page_header()
	{
		global $auth, $config, $db, $user, $phpbb_root_path, $phpEx;

		if (!isset($user->lang['ASACP_BAN']))
		{
			$user->add_lang('mods/asacp');
		}

		$user_id = request_var('u', 0);
		$username = request_var('un', '', true);
		if (request_var('mode', '') == 'viewprofile' && ($user_id || $username))
		{
			$sql = 'SELECT user_id, user_ip, user_flagged FROM ' . USERS_TABLE . ' WHERE ' . (($user_id) ? 'user_id = ' . $user_id : "username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'");
			$result = $db->sql_query($sql);
			$user_row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if ($user_row)
			{
				$user_id = $user_row['user_id'];

				// Output Flagged section
				self::flagged_output($user_row['user_id'], $user_row, 'custom_fields');

				// Output One Click Ban section
				if ($auth->acl_get('m_asacp_ban') && $user_id != $user->data['user_id'])
				{
					$sql = 'SELECT ban_id FROM ' . BANLIST_TABLE . ' WHERE ban_userid = ' . $user_id;
					$result = $db->sql_query($sql);
					$ban_id = $db->sql_fetchfield('ban_id');
					$db->sql_freeresult($result);

					if (!$ban_id)
					{
						$asacp_ban = '[ <a href="' . append_sid("{$phpbb_root_path}antispam/index.$phpEx", 'mode=ocban&amp;u=' . $user_id, true, $user->session_id) . '">' . $user->lang['ASACP_BAN'] . '</a> ]';
						self::cp_row_output($user->lang['ASACP_BAN'], $asacp_ban, 'custom_fields');
					}
				}

				// Output IP Search section
				if ($auth->acl_get('m_asacp_ip_search'))
				{
					$ip_search = array();
					$u_ip_search = '<a href="' . append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=asacp&amp;mode=ip_search&amp;ip={IP}', true, $user->session_id) . '">{IP}</a>';

					if ($user_row['user_ip'])
					{
						$ip_search[] = str_replace('{IP}', $user_row['user_ip'], $u_ip_search);
					}

					$sql = 'SELECT DISTINCT(poster_ip), post_id FROM ' . POSTS_TABLE . "
						WHERE poster_id = $user_id
						AND poster_ip <> '{$user_row['user_ip']}'
						ORDER BY post_id DESC";
					$result = $db->sql_query_limit($sql, 5);
					while ($row = $db->sql_fetchrow($result))
					{
						$ip_search[] = str_replace('{IP}', $row['poster_ip'], $u_ip_search);
					}
					$db->sql_freeresult($result);

					if (($user_row['user_ip'] && sizeof($ip_search) == 6) || (!$user_row['user_ip'] && sizeof($ip_search) == 5))
					{
						$ip_search[4] = '<a href="' . append_sid("{$phpbb_root_path}antispam/index.$phpEx", 'mode=display_ips&amp;u=' . $user_id) . '">' . $user->lang['MORE'] . '...</a>';
						unset($ip_search[5]);
					}

					self::cp_row_output($user->lang['IP_SEARCH'], implode('<br />', $ip_search), 'custom_fields');
				}
			}
		}

		if ($user->data['user_flag_new'] && $config['asacp_notify_new_flag'] && $auth->acl_get('m_asacp_user_flag'))
		{
			global $phpbb_root_path, $phpEx, $template;
			$template->assign_var('U_USER_FLAG_NEW', append_sid("{$phpbb_root_path}mcp.$phpEx","i=asacp&amp;mode=flag"));
		}
	}
	//public static function page_header()

	/**
	* Flagged Output
	*
	* For outputting the flagged information to the CP fields
	*
	* @param int $poster_id The poster_id
	* @param array $poster_row The array of information on the user
	* @param string $template_block The template block to output the field to
	* @param int $post_id The post ID
	*/
	public static function flagged_output($poster_id, &$poster_row, $template_block, $post_id = 0)
	{
		global $auth, $config, $phpbb_root_path, $phpEx, $user, $template;

		if (!isset($user->lang['USER_FLAGGED']))
		{
			$user->add_lang('mods/asacp');
		}

		// Output One Click Ban section
		if ($auth->acl_get('m_asacp_ban') && $poster_id != $user->data['user_id'] && $post_id)
		{
			$asacp_ban = '[ <a href="' . append_sid("{$phpbb_root_path}antispam/index.$phpEx", 'mode=ocban&amp;u=' . $poster_id . '&amp;p=' . $post_id) . '">' . $user->lang['ASACP_BAN'] . '</a> ]';
			self::cp_row_output($user->lang['ASACP_BAN'], $asacp_ban, $template_block);
		}

		if (!$config['asacp_enable'] || !$config['asacp_user_flag_enable'] || !$auth->acl_get('m_asacp_user_flag'))
		{
			return;
		}

		if (isset($poster_row['user_flagged']))
		{
			if ($poster_row['user_flagged'])
			{
				$flagged_value = '<span class="error">' . $user->lang['YES'] . '</span> [ <a href="' . append_sid("{$phpbb_root_path}antispam/index.$phpEx", "mode=user_unflag&amp;u={$poster_id}&amp;p=$post_id") . '">' . $user->lang['USER_UNFLAG']. '</a> ]';
			}
			else
			{
				$flagged_value = $user->lang['NO'] . ' [ <a href="' . append_sid("{$phpbb_root_path}antispam/index.$phpEx", "mode=user_flag&amp;u={$poster_id}&amp;p=$post_id") . '">' . $user->lang['USER_FLAG']. '</a> ]';
			}

			self::cp_row_output($user->lang['USER_FLAGGED'], $flagged_value, $template_block);
		}
	}
	//public static function flagged_output($poster_id, &$poster_row, $template_block, $post_id = 0)

	/**
	* Custom Profile row output
	*
	* @param mixed $name
	* @param mixed $value
	* @param mixed $template_block
	*/
	public static function cp_row_output($name, $value, $template_block)
	{
		global $template;

		$template->assign_block_vars($template_block, array(
			'PROFILE_FIELD_NAME'		=> $name,
			'PROFILE_FIELD_VALUE'		=> $value,
			'S_FIELD_VT'				=> true, // For compatibility with the Select viewable Custom Profiles Mod
			'S_FIELD_VP'				=> true, // For compatibility with the Select viewable Custom Profiles Mod
		));
	}
	//public static function cp_row_output($name, $value, $template_block)

	/**
	* Submit Post
	*
	* Should be run when a post is submitted to check the user flag
	*
	* @param string $mode
	* @param int $post_id
	*/
	public static function submit_post($mode, $post_id)
	{
		global $config, $user;

		if (!$config['asacp_enable'])
		{
			return;
		}

		$post_id = (int) $post_id;

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
	//public static function submit_post($mode, $post_id, $is_spam)

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

		if (!$config['asacp_enable'] || !$config['asacp_spam_words_enable'])
		{
			return false;
		}

		if ($user->data['is_registered'])
		{
			if ($post_count === false)
			{
				$post_count = $user->data['user_posts'];
			}

			if ($post_count > $config['asacp_spam_words_post_limit'] && $config['asacp_spam_words_post_limit'] > 0)
			{
				return false;
			}
		}
		// else the user is a guest

		if (!class_exists('spam_words'))
		{
			global $phpbb_root_path, $phpEx;
			include($phpbb_root_path . 'antispam/spam_words.' . $phpEx);
		}

		$spam_words = new spam_words();
		$spam_words->messages = (!is_array($data)) ? array($data) : $data;
		$spam_words->check_messages();

		$flag_limit = (is_numeric($flag_limit) && $flag_limit > 0) ? (int) $flag_limit : $config['asacp_spam_words_flag_limit'];
		return ($spam_words->spam_flags >= $flag_limit) ? true : false;
	}
	//public static function spam_words($data, $post_count = false)

	/**
	* Akismet Operations
	*
	* Send a message to check for spam.  If the message is flagged as spam, true is returned.
	*
	* @param string|array $data The message to check
	*
	* @return bool True if the message is flagged as spam, false if not
	*/
	public static function akismet($data)
	{
		global $cache, $config, $db, $user;

		if (!$config['asacp_enable'] || !$config['asacp_akismet_enable'] || !$config['asacp_akismet_key'])
		{
			return false;
		}

		if ($user->data['is_registered'])
		{
			if ($user->data['user_posts'] > $config['asacp_akismet_post_limit'] && $config['asacp_akismet_post_limit'] > 0)
			{
				return false;
			}
		}
		// else the user is a guest

		if (!class_exists('Akismet'))
		{
			global $phpbb_root_path, $phpEx;
			include($phpbb_root_path . 'antispam/Akismet.class.' . $phpEx);
		}

		$akismet = new Akismet($config['asacp_akismet_domain'], $config['asacp_akismet_key']);
		$akismet->setUserIP($user->ip);
		$akismet->setCommentType('comment');
		$akismet->setCommentAuthor($user->data['username']);
		$akismet->setCommentAuthorEmail($user->data['user_email']);
		$akismet->setCommentContent((string) $data);

		return ($akismet->isCommentSpam()) ? true : false;
	}
	//public static function akismet($data)

	/**
	* Add spam log event
	*
	* @param string $type The type of log.  spam for the normal spam log, flag for an event by a flagged user.
	*/
	public static function add_log($action, $data = array(), $type = 'spam')
	{
		global $config, $db, $user, $forum_id, $topic_id;

		if (!$config['asacp_enable'])
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
				if (!$config['asacp_user_flag_enable'])
				{
					return;
				}

				$log_type = 2;

				// The user flag new notification
				if ($config['asacp_notify_new_flag'])
				{
					$db->sql_query('UPDATE ' . USERS_TABLE . ' SET user_flag_new = 1');
				}
			break;

			case 'spam' :
			default :
				if (!$config['asacp_log'])
				{
					return;
				}

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
}
?>
