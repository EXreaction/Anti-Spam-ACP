<?php
/**
*
* @package Anti-Spam ACP
* @copyright (c) 2008 EXreaction
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : '../';
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
	define('SPAM_LOG_TABLE', $table_prefix . 'spam_log');
}

if (!file_exists($phpbb_root_path . 'umil/umil_frontend.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

include($phpbb_root_path . 'umil/umil_frontend.' . $phpEx);
$umil = new umil_frontend('REMOVE_ASACP', true);

$stages = array(
	'CONFIRM' => array('url' => append_sid($phpbb_root_path . 'antispam/remove_asacp.' . $phpEx)),
	'UNINSTALL'
);

if ($umil->confirm_box(true))
{
	$umil->display_stages($stages, 2);

	include($phpbb_root_path . 'antispam/asacp_versions.' . $phpEx);

	$umil->run_actions('uninstall', $versions, 'asacp_version');
}
else
{
	$umil->display_stages($stages);
	$umil->confirm_box(false, 'REMOVE_ASACP');
}
$umil->done();

?>