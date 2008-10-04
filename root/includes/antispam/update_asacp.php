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
define('ASACP_UPDATE_VERSION', '0.3.1');

if (!class_exists('umif'))
{
	include($phpbb_root_path . 'umif/umif.' . $phpEx);
}
$umif = new umif();

if (!isset($config['asacp_version']))
{
	$umif->config_add('asacp_enable', true);
	$umif->config_add('asacp_version', '0.1.0');
}

switch ($config['asacp_version'])
{
	case '0.1.0' :
		$umif->permission_add('a_asacp', true);
	case '0.1.1' :
		$umif->config_add('asacp_reg_captcha', true);
	case '0.1.2' :
		$umif->config_add('asacp_log', true);
	case '0.1.3' :
		$schema_data = array(
			'COLUMNS'		=> array(
				'word_id'			=> array('UINT', NULL, 'auto_increment'),
				'word_text'			=> array('VCHAR_UNI', ''),
				'word_regex'		=> array('BOOL', 0),
				'word_regex_auto'	=> array('BOOL', 0),
			),
			'PRIMARY_KEY'	=> 'word_id',
		);
		$umif->table_add(SPAM_WORDS_TABLE, $schema_data);
	case '0.1.4' :
		$umif->config_add('asacp_spam_words_enable', false);
		$umif->config_add('asacp_spam_words_post_limit', 5);
	case '0.1.5' :
		$umif->config_add('asacp_spam_words_flag_limit', 1);
	case '0.1.6' :
		$umif->config_add('asacp_spam_words_posting_action', 2);
	case '0.1.7' :
		$umif->config_add('asacp_spam_words_profile_action', 1);
	case '0.1.8' :
		$umif->config_add('asacp_spam_words_pm_action', 1);
	case '0.1.9' :
		$umif->config_add('asacp_profile_icq', 2);
		$umif->config_add('asacp_profile_icq_post_limit', 5);
		$umif->config_add('asacp_profile_aim', 2);
		$umif->config_add('asacp_profile_aim_post_limit', 5);
		$umif->config_add('asacp_profile_msn', 2);
		$umif->config_add('asacp_profile_msn_post_limit', 5);
		$umif->config_add('asacp_profile_yim', 2);
		$umif->config_add('asacp_profile_yim_post_limit', 5);
		$umif->config_add('asacp_profile_jabber', 2);
		$umif->config_add('asacp_profile_jabber_post_limit', 5);
		$umif->config_add('asacp_profile_website', 2);
		$umif->config_add('asacp_profile_website_post_limit', 5);
		$umif->config_add('asacp_profile_location', 2);
		$umif->config_add('asacp_profile_location_post_limit', 5);
		$umif->config_add('asacp_profile_occupation', 2);
		$umif->config_add('asacp_profile_occupation_post_limit', 5);
		$umif->config_add('asacp_profile_interests', 2);
		$umif->config_add('asacp_profile_interests_post_limit', 5);
	case '0.1.10' :
		$umif->config_add('asacp_profile_signature', 2);
		$umif->config_add('asacp_profile_signature_post_limit', 5);
	case '0.1.11' :
	case '0.3.0' :
		$umif->table_column_add(USERS_TABLE, 'user_flagged', array('BOOL', 0));
}

$umif->config_update('asacp_version', ASACP_UPDATE_VERSION);

$cache->purge();

?>