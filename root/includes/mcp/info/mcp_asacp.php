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

class mcp_asacp_info
{
	function module()
	{
		return array(
			'filename'	=> 'mcp_asacp',
			'title'		=> 'ANTISPAM',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'log'				=> array('title' => 'ASACP_SPAM_LOG', 'auth' => 'acl_m_asacp_spam_log', 'cat' => array('ANTISPAM')),
				'flag'				=> array('title' => 'ASACP_FLAG_LOG', 'auth' => 'acl_m_asacp_user_flag', 'cat' => array('ANTISPAM')),
				'flag_list'			=> array('title' => 'ASACP_FLAG_LIST', 'auth' => 'acl_m_asacp_user_flag', 'cat' => array('ANTISPAM')),
				'ip_search'			=> array('title' => 'ASACP_IP_SEARCH', 'auth' => 'acl_m_asacp_ip_search', 'cat' => array('ANTISPAM')),
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