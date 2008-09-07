<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

class acp_asacp_info
{
	function module()
	{
		return array(
			'filename'	=> 'acp_asacp',
			'title'		=> 'ANTISPAM',
			'version'	=> '1.0.0',
			'modes'		=> array(
				'settings'		=> array('title' => 'ASACP_SETTINGS', 'auth' => 'acl_a_asacp', 'cat' => array('ANTISPAM')),
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