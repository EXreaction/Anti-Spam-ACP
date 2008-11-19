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

// To prevent issues in case the user forgets to upload the update file
define('ASACP_UPDATE_VERSION', '0.7.0');

include($phpbb_root_path . 'umil/umil.' . $phpEx);
$umil = new umil();

if (!isset($config['asacp_version']))
{
	$umil->config_add('asacp_enable', true);
	$umil->config_add('asacp_version', '0.1.0');
}

switch ($config['asacp_version'])
{
	case '0.1.0' :
		$umil->permission_add('a_asacp', true);
	case '0.1.1' :
		$umil->config_add('asacp_reg_captcha', false);
	case '0.1.2' :
		$umil->config_add('asacp_log', true);
	case '0.1.3' :
		$umil->table_add(SPAM_WORDS_TABLE, array(
			'COLUMNS'		=> array(
				'word_id'			=> array('UINT', NULL, 'auto_increment'),
				'word_text'			=> array('VCHAR_UNI', ''),
				'word_regex'		=> array('BOOL', 0),
				'word_regex_auto'	=> array('BOOL', 0),
			),
			'PRIMARY_KEY'	=> 'word_id',
		));
	case '0.1.4' :
		$umil->config_add('asacp_spam_words_enable', false);
		$umil->config_add('asacp_spam_words_post_limit', 5);
	case '0.1.5' :
		$umil->config_add('asacp_spam_words_flag_limit', 1);
	case '0.1.6' :
		$umil->config_add('asacp_spam_words_posting_action', 2);
	case '0.1.7' :
		$umil->config_add('asacp_spam_words_profile_action', 1);
	case '0.1.8' :
		$umil->config_add('asacp_spam_words_pm_action', 1);
	case '0.1.9' :
		$umil->config_add('asacp_profile_icq', 2);
		$umil->config_add('asacp_profile_icq_post_limit', 5);
		$umil->config_add('asacp_profile_aim', 2);
		$umil->config_add('asacp_profile_aim_post_limit', 5);
		$umil->config_add('asacp_profile_msn', 2);
		$umil->config_add('asacp_profile_msn_post_limit', 5);
		$umil->config_add('asacp_profile_yim', 2);
		$umil->config_add('asacp_profile_yim_post_limit', 5);
		$umil->config_add('asacp_profile_jabber', 2);
		$umil->config_add('asacp_profile_jabber_post_limit', 5);
		$umil->config_add('asacp_profile_website', 2);
		$umil->config_add('asacp_profile_website_post_limit', 5);
		$umil->config_add('asacp_profile_location', 2);
		$umil->config_add('asacp_profile_location_post_limit', 5);
		$umil->config_add('asacp_profile_occupation', 2);
		$umil->config_add('asacp_profile_occupation_post_limit', 5);
		$umil->config_add('asacp_profile_interests', 2);
		$umil->config_add('asacp_profile_interests_post_limit', 5);
	case '0.1.10' :
		$umil->config_add('asacp_profile_signature', 2);
		$umil->config_add('asacp_profile_signature_post_limit', 5);
	case '0.1.11' :
	case '0.3.0' :
		$umil->table_column_add(USERS_TABLE, 'user_flagged', array('BOOL', 0));
	case '0.3.1' :
		$umil->table_add(SPAM_LOG_TABLE, array(
			'COLUMNS'		=> array(
				'log_id'				=> array('UINT', NULL, 'auto_increment'),
				'log_type'				=> array('TINT:4', 1),
				'user_id'				=> array('UINT', 0),
				'forum_id'				=> array('UINT', 0),
				'topic_id'				=> array('UINT', 0),
				'reportee_id'			=> array('UINT', 0),
				'log_ip'				=> array('VCHAR:40', ''),
				'log_time'				=> array('TIMESTAMP', 0),
				'log_operation'			=> array('TEXT_UNI', ''),
				'log_data'				=> array('MTEXT_UNI', ''),
			),
			'PRIMARY_KEY'	=> 'log_id',
			'KEYS'			=> array(
				'log_type'				=> array('INDEX', 'log_type'),
				'forum_id'				=> array('INDEX', 'forum_id'),
				'topic_id'				=> array('INDEX', 'topic_id'),
				'reportee_id'			=> array('INDEX', 'reportee_id'),
				'user_id'				=> array('INDEX', 'user_id'),
			),
		));

		// Moving the Spam log from the Log table to the Spam Log table.
		$sql = 'SELECT * FROM ' . LOG_TABLE . ' WHERE log_type = ' . LOG_SPAM;
		$result = $db->sql_query($sql);
		$insert_ary = array();
		while ($row = $db->sql_fetchrow($result))
		{
			unset($row['log_id']);

			$row['log_type'] = 1;
			$insert_ary[] = $row;
		}
		$db->sql_freeresult($result);

		$db->sql_multi_insert(SPAM_LOG_TABLE, $insert_ary);

		$db->sql_query('DELETE FROM ' . LOG_TABLE . ' WHERE log_type = ' . LOG_SPAM);
	case '0.3.2' :
	case '0.3.3' :
		$umil->table_column_add(USERS_TABLE, 'user_flag_new', array('BOOL', 0));
		$umil->config_add('asacp_notify_new_flag', true);
		$umil->config_add('asacp_user_flag_enable', true);
	case '0.3.4' :
		$umil->permission_add('a_asacp_ip_search', true);
		$umil->permission_add('a_asacp_spam_log', true);
		$umil->permission_add('a_asacp_user_flag', true);
		$umil->permission_add('a_asacp_profile_fields', true);
		$umil->permission_add('a_asacp_spam_words', true);

		// Remove the Modules (the permissions for each module was updated)
		$umil->module_remove('acp', false, 'ASACP_SETTINGS');
		$umil->module_remove('acp', false, 'ASACP_SPAM_LOG');
		$umil->module_remove('acp', false, 'ASACP_FLAG_LOG');
		$umil->module_remove('acp', false, 'ASACP_FLAG_LIST');
		$umil->module_remove('acp', false, 'ASACP_IP_SEARCH');
		$umil->module_remove('acp', false, 'ASACP_SPAM_WORDS');
		$umil->module_remove('acp', false, 'ASACP_PROFILE_FIELDS');
		$umil->module_remove('acp', 'ACP_CAT_DOT_MODS', 'ANTISPAM');
}

// Add the modules if they do not exist.
if (!$umil->module_exists('acp', 'ACP_CAT_DOT_MODS', 'ANTISPAM'))
{
	$umil->module_add('acp', 'ACP_CAT_DOT_MODS', 'ANTISPAM'); // Category
}

if (!$umil->module_exists('acp', 'ANTISPAM', 'ASACP_SETTINGS'))
{
	$umil->module_add('acp', 'ANTISPAM', array('module_basename' => 'asacp')); // All the Anti-Spam ACP Modules
}

$umil->config_update('asacp_version', ASACP_UPDATE_VERSION);
$umil->cache_purge();

?>