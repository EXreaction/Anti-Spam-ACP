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

if (!class_exists('auth_admin'))
{
	include($phpbb_root_path . 'includes/acp/auth.' . $phpEx);
}
$auth_admin = new auth_admin();

if (!function_exists('create_tables'))
{
	include($phpbb_root_path . 'includes/antispam/create_tables.' . $phpEx);
}

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
		set_config('asacp_reg_captcha', false);
	case '0.1.2' :
		set_config('asacp_log', true);
	case '0.1.3' :
		$schema_data = array(
			SPAM_WORDS_TABLE	=> array(
				'COLUMNS'		=> array(
					'word_id'			=> array('UINT', NULL, 'auto_increment'),
					'word_text'			=> array('VCHAR_UNI', ''),
					'word_regex'		=> array('BOOL', 0),
					'word_regex_auto'	=> array('BOOL', 0),
				),
				'PRIMARY_KEY'	=> 'word_id',
			),
		);
		$db->sql_query(create_tables($schema_data, $dbms));
	case '0.1.4' :
		set_config('asacp_spam_words_enable', false);
		set_config('asacp_spam_words_post_limit', 5);
	case '0.1.5' :
		set_config('asacp_spam_words_flag_limit', 1);
	case '0.1.6' :
		set_config('asacp_spam_words_posting_action', 2);
	case '0.1.7' :
		set_config('asacp_spam_words_profile_action', 1);
	case '0.1.8' :
		set_config('asacp_spam_words_pm_action', 1);
	case '0.1.9' :
		set_config('asacp_profile_icq', 2);
		set_config('asacp_profile_icq_post_limit', 5);
		set_config('asacp_profile_aim', 2);
		set_config('asacp_profile_aim_post_limit', 5);
		set_config('asacp_profile_msn', 2);
		set_config('asacp_profile_msn_post_limit', 5);
		set_config('asacp_profile_yim', 2);
		set_config('asacp_profile_yim_post_limit', 5);
		set_config('asacp_profile_jabber', 2);
		set_config('asacp_profile_jabber_post_limit', 5);
		set_config('asacp_profile_website', 2);
		set_config('asacp_profile_website_post_limit', 5);
		set_config('asacp_profile_location', 2);
		set_config('asacp_profile_location_post_limit', 5);
		set_config('asacp_profile_occupation', 2);
		set_config('asacp_profile_occupation_post_limit', 5);
		set_config('asacp_profile_interests', 2);
		set_config('asacp_profile_interests_post_limit', 5);
}

set_config('asacp_version', ASACP_VERSION);

$cache->purge();

?>