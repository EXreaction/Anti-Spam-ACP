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

include($phpbb_root_path . 'umif/umif_frontend.' . $phpEx);
$umif = new umif_frontend('REMOVE_ASACP', true);

if ($umif->confirm_box(true))
{
	$umif->display_stages(array('CONFIRM', 'UNINSTALL'), 2);

	// Remove the Modules
	$umif->module_remove('acp', false, 'ASACP_SETTINGS');
	$umif->module_remove('acp', false, 'ASACP_SPAM_LOG');
	$umif->module_remove('acp', false, 'ASACP_FLAG_LOG');
	$umif->module_remove('acp', false, 'ASACP_FLAG_LIST');
	$umif->module_remove('acp', false, 'ASACP_IP_SEARCH');
	$umif->module_remove('acp', false, 'ASACP_SPAM_WORDS');
	$umif->module_remove('acp', false, 'ASACP_PROFILE_FIELDS');
	$umif->module_remove('acp', 'ACP_CAT_DOT_MODS', 'ANTISPAM');

	// 0.3.5
	$umif->permission_remove('a_asacp_ip_search', true);
	$umif->permission_remove('a_asacp_spam_log', true);
	$umif->permission_remove('a_asacp_user_flag', true);
	$umif->permission_remove('a_asacp_profile_fields', true);
	$umif->permission_remove('a_asacp_spam_words', true);

	// 0.3.4
	$umif->table_column_remove(USERS_TABLE, 'user_flag_new');
	$umif->config_remove('asacp_notify_new_flag');
	$umif->config_remove('asacp_user_flag_enable');

	// 0.3.2
	$umif->table_remove(SPAM_LOG_TABLE);

	// 0.3.1
	$umif->table_column_remove(USERS_TABLE, 'user_flagged');

	// 0.1.11
	$umif->config_remove('asacp_profile_signature');
	$umif->config_remove('asacp_profile_signature_post_limit');

	// 0.1.10
	$umif->config_remove('asacp_profile_icq');
	$umif->config_remove('asacp_profile_icq_post_limit');
	$umif->config_remove('asacp_profile_aim');
	$umif->config_remove('asacp_profile_aim_post_limit');
	$umif->config_remove('asacp_profile_msn');
	$umif->config_remove('asacp_profile_msn_post_limit');
	$umif->config_remove('asacp_profile_yim');
	$umif->config_remove('asacp_profile_yim_post_limit');
	$umif->config_remove('asacp_profile_jabber');
	$umif->config_remove('asacp_profile_jabber_post_limit');
	$umif->config_remove('asacp_profile_website');
	$umif->config_remove('asacp_profile_website_post_limit');
	$umif->config_remove('asacp_profile_location');
	$umif->config_remove('asacp_profile_location_post_limit');
	$umif->config_remove('asacp_profile_occupation');
	$umif->config_remove('asacp_profile_occupation_post_limit');
	$umif->config_remove('asacp_profile_interests');
	$umif->config_remove('asacp_profile_interests_post_limit');

	// 0.1.9
	$umif->config_remove('asacp_spam_words_pm_action');

	// 0.1.8
	$umif->config_remove('asacp_spam_words_profile_action');

	// 0.1.7
	$umif->config_remove('asacp_spam_words_posting_action');

	// 0.1.6
	$umif->config_remove('asacp_spam_words_flag_limit');

	// 0.1.5
	$umif->config_remove('asacp_spam_words_enable');
	$umif->config_remove('asacp_spam_words_post_limit');

	// 0.1.4
	$umif->table_remove(SPAM_WORDS_TABLE);

	// 0.1.3
	$umif->config_remove('asacp_log');

	// 0.1.2
	$umif->config_remove('asacp_reg_captcha');

	// 0.1.1
	$umif->permission_remove('a_asacp', true);

	// 0.1.0
	$umif->config_remove('asacp_enable');
	$umif->config_remove('asacp_version');

	$umif->cache_purge();
}
else
{
	$umif->display_stages(array('CONFIRM', 'UNINSTALL'));
	$umif->confirm_box(false, 'REMOVE_ASACP');
}
$umif->done();

?>