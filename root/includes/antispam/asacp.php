<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/* DO NOT FORGET
uncomment //trigger_error('TOO_MANY_REGISTERS');
update url in ACP for new version
*/

if (!defined('IN_PHPBB'))
{
	exit;
}

$user->add_lang('mods/asacp');
define('ASACP_VERSION', '0.1.2');
if (!isset($config['asacp_version']) || $config['asacp_version'] != ASACP_VERSION)
{
	antispam::update_db();
}

class antispam
{
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

		$wrong_confirm = false;
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
				if (strcasecmp($row['code'], $asacp_code) !== 0)
				{
					$wrong_confirm = true;
				}
			}
			else
			{
				$wrong_confirm = true;
			}
		}
		else
		{
			$wrong_confirm = true;
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
				//trigger_error('TOO_MANY_REGISTERS');
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

	/**
	* Update/Install Database section
	*/
	public static function update_db()
	{
		global $cache, $config, $phpbb_root_path, $phpEx;

		if (!class_exists('auth_admin'))
		{
			include($phpbb_root_path . 'includes/acp/auth.' . $phpEx);
		}
		$auth_admin = new auth_admin();

		if (!isset($config['asacp_version']))
		{
			set_config('asacp_enable', true);
			set_config('asacp_version', '0.1.0');
		}

		switch ($config['asacp_version'])
		{
			case '0.1.0' :
				$auth_admin->acl_add_option(array(
					'local'		=> array(),
					'global'	=> array(
						'a_asacp',
					),
				));
			case '0.1.1' :
				set_config('asacp_reg_captcha', true);
		}

		set_config('asacp_version', ASACP_VERSION);

		$cache->purge();
	}
	//public static function update_db()
}
?>