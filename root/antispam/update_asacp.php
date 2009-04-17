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

if (!file_exists($phpbb_root_path . 'umil/umil.' . $phpEx))
{
	if (!file_exists($phpbb_root_path . 'antispam/umil.' . $phpEx))
	{
		trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
	}
	else
	{
		include($phpbb_root_path . 'antispam/umil.' . $phpEx);
	}
}
else
{
	include($phpbb_root_path . 'umil/umil.' . $phpEx);
}

$umil = new umil(true);

include($phpbb_root_path . 'antispam/asacp_versions.' . $phpEx);

$umil->run_actions('update', $versions, 'asacp_version');

?>