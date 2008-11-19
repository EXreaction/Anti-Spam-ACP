<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup('mods/asacp');
$user->add_lang('mods/info_acp_asacp');

if (!$user->data['is_registered'])
{
	login_box();
}

if ($user->data['user_type'] != USER_FOUNDER)
{
	trigger_error('FOUNDER_ONLY');
}

if (!defined('SPAM_WORDS_TABLE'))
{
	define('SPAM_WORDS_TABLE', $table_prefix . 'spam_words');
	define('SPAM_LOG_TABLE', $table_prefix . 'spam_log');
	define('LOG_SPAM', 6); // Removed as of 0.3.2, keeping for updates
}

include($phpbb_root_path . 'umil/umil_frontend.' . $phpEx);
$umil = new umil_frontend('REMOVE_ASACP', true);

if ($umil->confirm_box(true))
{
	$umil->display_stages(array('CONFIRM', 'UNINSTALL'), 2);

	// Remove the Modules
	$umil->module_remove('acp', false, 'ASACP_SETTINGS');
	$umil->module_remove('acp', false, 'ASACP_SPAM_LOG');
	$umil->module_remove('acp', false, 'ASACP_FLAG_LOG');
	$umil->module_remove('acp', false, 'ASACP_FLAG_LIST');
	$umil->module_remove('acp', false, 'ASACP_IP_SEARCH');
	$umil->module_remove('acp', false, 'ASACP_SPAM_WORDS');
	$umil->module_remove('acp', false, 'ASACP_PROFILE_FIELDS');
	$umil->module_remove('acp', 'ACP_CAT_DOT_MODS', 'ANTISPAM');

	// 0.3.5
	$umil->permission_remove('a_asacp_ip_search', true);
	$umil->permission_remove('a_asacp_spam_log', true);
	$umil->permission_remove('a_asacp_user_flag', true);
	$umil->permission_remove('a_asacp_profile_fields', true);
	$umil->permission_remove('a_asacp_spam_words', true);

	// 0.3.4
	$umil->table_column_remove(USERS_TABLE, 'user_flag_new');
	$umil->config_remove('asacp_notify_new_flag');
	$umil->config_remove('asacp_user_flag_enable');

	// 0.3.2
	$umil->table_remove(SPAM_LOG_TABLE);

	// 0.3.1
	$umil->table_column_remove(USERS_TABLE, 'user_flagged');

	// 0.1.11
	$umil->config_remove('asacp_profile_signature');
	$umil->config_remove('asacp_profile_signature_post_limit');

	// 0.1.10
	$umil->config_remove('asacp_profile_icq');
	$umil->config_remove('asacp_profile_icq_post_limit');
	$umil->config_remove('asacp_profile_aim');
	$umil->config_remove('asacp_profile_aim_post_limit');
	$umil->config_remove('asacp_profile_msn');
	$umil->config_remove('asacp_profile_msn_post_limit');
	$umil->config_remove('asacp_profile_yim');
	$umil->config_remove('asacp_profile_yim_post_limit');
	$umil->config_remove('asacp_profile_jabber');
	$umil->config_remove('asacp_profile_jabber_post_limit');
	$umil->config_remove('asacp_profile_website');
	$umil->config_remove('asacp_profile_website_post_limit');
	$umil->config_remove('asacp_profile_location');
	$umil->config_remove('asacp_profile_location_post_limit');
	$umil->config_remove('asacp_profile_occupation');
	$umil->config_remove('asacp_profile_occupation_post_limit');
	$umil->config_remove('asacp_profile_interests');
	$umil->config_remove('asacp_profile_interests_post_limit');

	// 0.1.9
	$umil->config_remove('asacp_spam_words_pm_action');

	// 0.1.8
	$umil->config_remove('asacp_spam_words_profile_action');

	// 0.1.7
	$umil->config_remove('asacp_spam_words_posting_action');

	// 0.1.6
	$umil->config_remove('asacp_spam_words_flag_limit');

	// 0.1.5
	$umil->config_remove('asacp_spam_words_enable');
	$umil->config_remove('asacp_spam_words_post_limit');

	// 0.1.4
	$umil->table_remove(SPAM_WORDS_TABLE);

	// 0.1.3
	$umil->config_remove('asacp_log');

	// 0.1.2
	$umil->config_remove('asacp_reg_captcha');

	// 0.1.1
	$umil->permission_remove('a_asacp', true);

	// 0.1.0
	$umil->config_remove('asacp_enable');
	$umil->config_remove('asacp_version');

	$umil->cache_purge();
}
else
{
	$umil->display_stages(array('CONFIRM', 'UNINSTALL'));
	$umil->confirm_box(false, 'REMOVE_ASACP');
}
$umil->done();

?>