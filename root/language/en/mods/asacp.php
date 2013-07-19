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
	'ASACP_AKISMET'			=> 'Akismet',
	'ASACP_AKISMET_SUBMIT'	=> 'Submit the following post to Akismet (spam only)',
	'ASACP_BAN'				=> 'One Click Ban',
	'ASACP_BAN_ACTIONS'		=> 'The following actions will be performed: %s',
	'ASACP_BAN_COMPLETE'	=> 'You have successfully banned the user.<br /><br /><a href="%s">Click here to return to the user\'s profile.</a>',
	'ASACP_BAN_CONFIRM'		=> 'Are you sure you want to ban the user %s? <strong class="error">This can not be undone!</strong>',
	'ASACP_BAN_REASON'		=> 'Ban Reason',
	'ASACP_BAN_REASON_EXPLAIN'	=> 'Please enter the ban reason (private).',
	'ASACP_BAN_REASON_SHOWN_TO_USER'			=> 'Ban reason shown to the user',
	'ASACP_BAN_REASON_SHOWN_TO_USER_EXPLAIN'	=> 'If a message is entered here, it will be shown to the user who was banned.',
	'ASACP_CREDITS'			=> '',
	'ASACP_EVIDENCE_SFS'	=> 'If submitting information to Stop Forum Spam, you must enter evidence here.<br />(8,000 character limit)',

	'FOUNDER_ONLY'			=> 'You must be a Board Founder to access this page.',

	'IP_SEARCH'				=> 'IP Search',

	'MORE'					=> 'More',

	'PROFILE_SPAM_DENIED'	=> 'One or more of the fields entered was marked as spam.',

	'REMOVE_ASACP'			=> 'Remove Anti-Spam ACP',
	'REMOVE_ASACP_CONFIRM'	=> 'Are you sure you want to remove the database alterations made by the Anti-Spam ACP Mod?<br /><br />Before you do this make sure you remove the mod edits from the files or the database section will automatically get added again.',

	'SFS_SUBMIT'			=> 'Submit profile information to <a href="http://www.stopforumspam.com/">Stop Forum Spam</a><br /><br /><strong>Note that submitting users for something other than spam is not allowed and can result in a ban from Stop Forum Spam.</strong>',
	'SIGNATURE_DISABLED'	=> 'You are not allowed to use a signature.',
	'SPAM_DENIED'			=> 'This message was flagged as spam and has been denied.',
	'STOP_FORUM_SPAM'		=> 'Stop Forum Spam',

	'USER_FLAG'				=> 'Flag',
	'USER_FLAGGED'			=> 'User Flagged',
	'USER_FLAG_CONFIRM'		=> 'Are you sure you want to flag the user %s?',
	'USER_FLAG_NEW'			=> 'New Flags Logged',
	'USER_FLAG_SUCCESS'		=> 'The user has been flagged successfully.',
	'USER_UNFLAG'			=> 'Remove Flag',
	'USER_UNFLAG_CONFIRM'	=> 'Are you sure you want to remove the flag from the user %s?',
	'USER_UNFLAG_SUCCESS'	=> 'The flag has been removed from this user successfully.',
));

?>