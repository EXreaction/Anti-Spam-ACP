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

class acp_asacp_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_asacp',
			'title'		=> 'ANTISPAM',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'settings'			=> array('title' => 'ASACP_SETTINGS', 'auth' => 'acl_a_asacp', 'cat' => array('ANTISPAM')),
				'log'				=> array('title' => 'ASACP_SPAM_LOG', 'auth' => 'acl_a_asacp_spam_log', 'cat' => array('ANTISPAM')),
				'flag'				=> array('title' => 'ASACP_FLAG_LOG', 'auth' => 'acl_a_asacp_user_flag', 'cat' => array('ANTISPAM')),
				'flag_list'			=> array('title' => 'ASACP_FLAG_LIST', 'auth' => 'acl_a_asacp_user_flag', 'cat' => array('ANTISPAM')),
				'ip_search'			=> array('title' => 'ASACP_IP_SEARCH', 'auth' => 'acl_a_asacp_ip_search', 'cat' => array('ANTISPAM')),
				'spam_words'		=> array('title' => 'ASACP_SPAM_WORDS', 'auth' => 'acl_a_asacp_spam_words', 'cat' => array('ANTISPAM')),
				'profile_fields'	=> array('title' => 'ASACP_PROFILE_FIELDS', 'auth' => 'acl_a_asacp_profile_fields', 'cat' => array('ANTISPAM')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}

?>