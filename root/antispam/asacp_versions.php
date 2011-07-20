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

$versions = array(
	'0.1.0'		=> array(
		'config_add'	=> array(
			array('asacp_enable', true),
		),

		'module_add'	=> array(
			array('acp', 'ACP_CAT_DOT_MODS', 'ANTISPAM'),
			array('acp', 'ANTISPAM', array('module_basename' => 'asacp')),
		),
	),
	'0.1.1'		=> array(
		'permission_add'	=> array(
			array('a_asacp', true),
		),
	),
	'0.1.2'		=> array(
		'config_add'	=> array(
			array('asacp_reg_captcha', false),
		),
	),
	'0.1.3'		=> array(
		'config_add'	=> array(
			array('asacp_log', true),
		),
	),
	'0.1.4'		=> array(
		'table_add'	=> array(
			array('phpbb_spam_words', array(
				'COLUMNS'		=> array(
					'word_id'			=> array('UINT', NULL, 'auto_increment'),
					'word_text'			=> array('VCHAR_UNI', ''),
					'word_regex'		=> array('BOOL', 0),
					'word_regex_auto'	=> array('BOOL', 0),
				),
				'PRIMARY_KEY'	=> 'word_id',
			)),
		),
	),
	'0.1.5'		=> array(
		'config_add'	=> array(
			array('asacp_spam_words_enable', false),
			array('asacp_spam_words_post_limit', 5),
		),
	),
	'0.1.6'		=> array(
		'config_add'	=> array(
			array('asacp_spam_words_flag_limit', 1),
		),
	),
	'0.1.7'		=> array(
		'config_add'	=> array(
			array('asacp_spam_words_posting_action', 2),
		),
	),
	'0.1.8'		=> array(
		'config_add'	=> array(
			array('asacp_spam_words_profile_action', 1),
		),
	),
	'0.1.8'		=> array(
		'config_add'	=> array(
			array('asacp_spam_words_pm_action', 1),
		),
	),
	'0.1.9'		=> array(
		'config_add'	=> array(
			array('asacp_spam_words_profile_action', 1),
		),
	),
	'0.1.10'		=> array(
		'config_add'	=> array(
			array('asacp_profile_icq', 2),
			array('asacp_profile_icq_post_limit', 5),
			array('asacp_profile_aim', 2),
			array('asacp_profile_aim_post_limit', 5),
			array('asacp_profile_msn', 2),
			array('asacp_profile_msn_post_limit', 5),
			array('asacp_profile_yim', 2),
			array('asacp_profile_yim_post_limit', 5),
			array('asacp_profile_jabber', 2),
			array('asacp_profile_jabber_post_limit', 5),
			array('asacp_profile_website', 2),
			array('asacp_profile_website_post_limit', 5),
			array('asacp_profile_location', 2),
			array('asacp_profile_location_post_limit', 5),
			array('asacp_profile_occupation', 2),
			array('asacp_profile_occupation_post_limit', 5),
			array('asacp_profile_interests', 2),
			array('asacp_profile_interests_post_limit', 5),
		),
	),
	'0.1.11'		=> array(
		'config_add'	=> array(
			array('asacp_profile_signature', 2),
			array('asacp_profile_signature_post_limit', 5),
		),
	),
	'0.3.0'		=> array(),
	'0.3.1'		=> array(
		'table_column_add'	=> array(
			array('phpbb_users', 'user_flagged', array('BOOL', 0)),
		),
	),
	'0.3.2'		=> array(
		'table_add'	=> array(
			array('phpbb_spam_log', array(
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
			)),
		),

		'custom'	=> 'asacp_update',
	),
	'0.3.3'		=> array(),
	'0.3.4'		=> array(
		'table_column_add'	=> array(
			array('phpbb_users', 'user_flag_new', array('BOOL', 0)),
		),
		'config_add'	=> array(
			array('asacp_notify_new_flag', true),
			array('asacp_user_flag_enable', true),
		),
	),
	'0.7.0'		=> array(
		'permission_add'	=> array(
			'a_asacp_ip_search',
			'a_asacp_spam_log',
			'a_asacp_user_flag',
			'a_asacp_profile_fields',
			'a_asacp_spam_words',
		),

		'custom'	=> 'asacp_update',
	),
	'0.7.1'		=> array(),
	'0.9.0'		=> array(
		'config_add'	=> array(
			array('asacp_sfs_action', 2),
			array('asacp_sfs_min_freq', 2),
			array('asacp_sfs_key', ''),
			array('asacp_ocban_username', true),
			array('asacp_ocban_move_to_group', 0),
			array('asacp_ocban_delete_posts', false),
			array('asacp_ocban_delete_avatar', false),
			array('asacp_ocban_delete_signature', false),
			array('asacp_ocban_delete_profile_fields', false),
		),

		'permission_add'	=> array(
			'a_asacp_ban',
		),
	),
	'0.9.1'		=> array(
		'cache_purge'		=> array(),
	),
	'1.0.0'		=> array(
		'config_add'	=> array(
			array('asacp_profile_during_reg', false),
		),
	),
	'1.0.1'		=> array(
		'config_add'	=> array(
			array('asacp_spam_words_guest_always', 1),
		),
	),
  '1.0.2'    => array(
    'config_add'    => array(
      array('asacp_ocban_clear_outbox', false),
    ),
    'table_index_add'  => array(
      array('phpbb_spam_words', 'word_text'),
    ),
  ),
  '1.0.3'       => array(),
	'1.0.3-pl1'		=> array(),
);

function asacp_update($action, $version)
{
	global $db, $umil;

	if ($action != 'update')
	{
		return;
	}

	switch ($version)
	{
		case '0.3.2' :
			// Moving the Spam log from the Log table to the Spam Log table.
			$sql = 'SELECT * FROM ' . LOG_TABLE . ' WHERE log_type = 6';
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

			$db->sql_query('DELETE FROM ' . LOG_TABLE . ' WHERE log_type = 6');
		break;

		case '0.7.0' :
			// Permissions changed, re-adding modules
			$umil->module_remove('acp', 'ANTISPAM', array('module_basename' => 'asacp'));
			$umil->module_remove('acp', 'ACP_CAT_DOT_MODS', 'ANTISPAM');

			$umil->module_add('acp', 'ANTISPAM', array('module_basename' => 'asacp'));
			$umil->module_add('acp', 'ACP_CAT_DOT_MODS', 'ANTISPAM');
		break;
	}
}

?>