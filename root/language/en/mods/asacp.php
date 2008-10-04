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

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine

$lang = array_merge($lang, array(
	'ASACP_CREDITS'			=> 'Protected by <a href="http://www.lithiumstudios.org" target="_blank">Anti-Spam ACP</a>',

	'FOUNDER_ONLY'			=> 'You must be a Board Founder to access this page.',

	'PROFILE_SPAM_DENIED'	=> 'One or more of the fields entered was marked as spam.',

	'REMOVE_ASACP'			=> 'Remove Anti-Spam ACP',
	'REMOVE_ASACP_CONFIRM'	=> 'Are you sure you want to remove the database alterations made by the Anti-Spam ACP Mod?<br /><br />Before you do this make sure you remove the mod edits from the files or the database section will automatically get added again.',

	'SIGNATURE_DISABLED'	=> 'You are not allowed to use a signature.',
	'SPAM_DENIED'			=> 'This message was flagged as spam and has been denied.',
));

?>