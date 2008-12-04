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

include($phpbb_root_path . 'umil/umil.' . $phpEx);
$umil = new umil(true);

include($phpbb_root_path . 'antispam/asacp_versions.' . $phpEx);

$umil->run_actions('update', $versions, 'asacp_version');

?>