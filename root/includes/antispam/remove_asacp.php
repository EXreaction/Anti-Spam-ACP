<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../../';
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
	define('LOG_SPAM', 6);
}

include($phpbb_root_path . 'umif/umif_frontend.' . $phpEx);
$umif = new umif_frontend('REMOVE_ASACP');

if ($umif->confirm_box(true))
{
	$umif->display_stages(array('CONFIRM', 'UNINSTALL'), 2);

	$umif->config_remove('asacp_profile_signature');
	$umif->display_results();
	$umif->config_remove('asacp_profile_signature_post_limit');
	$umif->display_results();

	$umif->config_remove('asacp_profile_icq');
	$umif->display_results();
	$umif->config_remove('asacp_profile_icq_post_limit');
	$umif->display_results();
	$umif->config_remove('asacp_profile_aim');
	$umif->display_results();
	$umif->config_remove('asacp_profile_aim_post_limit');
	$umif->display_results();
	$umif->config_remove('asacp_profile_msn');
	$umif->display_results();
	$umif->config_remove('asacp_profile_msn_post_limit');
	$umif->display_results();
	$umif->config_remove('asacp_profile_yim');
	$umif->display_results();
	$umif->config_remove('asacp_profile_yim_post_limit');
	$umif->display_results();
	$umif->config_remove('asacp_profile_jabber');
	$umif->display_results();
	$umif->config_remove('asacp_profile_jabber_post_limit');
	$umif->display_results();
	$umif->config_remove('asacp_profile_website');
	$umif->display_results();
	$umif->config_remove('asacp_profile_website_post_limit');
	$umif->display_results();
	$umif->config_remove('asacp_profile_location');
	$umif->display_results();
	$umif->config_remove('asacp_profile_location_post_limit');
	$umif->display_results();
	$umif->config_remove('asacp_profile_occupation');
	$umif->display_results();
	$umif->config_remove('asacp_profile_occupation_post_limit');
	$umif->display_results();
	$umif->config_remove('asacp_profile_interests');
	$umif->display_results();
	$umif->config_remove('asacp_profile_interests_post_limit');
	$umif->display_results();

	$umif->config_remove('asacp_spam_words_pm_action');
	$umif->display_results();

	$umif->config_remove('asacp_spam_words_profile_action');
	$umif->display_results();

	$umif->config_remove('asacp_spam_words_posting_action');
	$umif->display_results();

	$umif->config_remove('asacp_spam_words_flag_limit');
	$umif->display_results();

	$umif->config_remove('asacp_spam_words_enable');
	$umif->display_results();
	$umif->config_remove('asacp_spam_words_post_limit');
	$umif->display_results();

	$umif->table_remove(SPAM_WORDS_TABLE);
	$umif->display_results();

	$umif->config_remove('asacp_log');
	$umif->display_results();

	$umif->config_remove('asacp_reg_captcha');
	$umif->display_results();

	$umif->permission_remove('a_asacp', 'global');
	$umif->display_results();

	$umif->config_remove('asacp_version');
	$umif->display_results();

	// Remove the Modules
	$umif->module_remove('acp', 'ASACP_SETTINGS');
	$umif->display_results();
	$umif->module_remove('acp', 'ASACP_SPAM_LOG');
	$umif->display_results();
	$umif->module_remove('acp', 'ASACP_IP_SEARCH');
	$umif->display_results();
	$umif->module_remove('acp', 'ASACP_SPAM_WORDS');
	$umif->display_results();
	$umif->module_remove('acp', 'ASACP_PROFILE_FIELDS');
	$umif->display_results();
	$umif->module_remove('acp', 'ANTISPAM');
	$umif->display_results();

	// Clear the log from spam entries
	$db->sql_query('DELETE FROM ' . LOG_TABLE . ' WHERE log_type = ' . LOG_SPAM);
	$umif->display_results('CLEARING_SPAM_LOG', 'SUCCESS');

	$umif->purge_cache();
	$umif->display_results();
}
else
{
	$umif->display_stages(array('CONFIRM', 'UNINSTALL'));
	$umif->confirm_box(false, 'REMOVE_ASACP');
}
$umif->done();

?>