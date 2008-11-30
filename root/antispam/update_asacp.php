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