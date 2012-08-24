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
	'ANTISPAM'						=> 'Anti-Spam',
	'ASACP_FLAG_LIST'				=> 'Flagged User List',
	'ASACP_FLAG_LOG'				=> 'Flag Log',
	'ASACP_IP_SEARCH'				=> 'IP Search',
	'ASACP_PROFILE_FIELDS'			=> 'Profile Fields',
	'ASACP_SETTINGS'				=> 'Anti-Spam ACP Settings',
	'ASACP_SPAM_LOG'				=> 'Spam Log',
	'ASACP_SPAM_WORDS'				=> 'Spam Words',

	'LOG_ADDED_POST'				=> 'Added a post',
	'LOG_ALTERED_PROFILE'			=> 'Altered profile information',
	'LOG_ALTERED_SIGNATURE'			=> 'Altered Signature',
	'LOG_ASACP_SETTINGS'			=> 'Updated Anti-Spam ACP Settings',
	'LOG_CLEAR_FLAG_LOG'			=> 'Cleared Flag Log',
	'LOG_CLEAR_SPAM_LOG'			=> 'Cleared Spam Log',
	'LOG_EDITED_POST'				=> 'Edited a post',
	'LOG_INCORRECT_CODE'			=> 'Entered in wrong confirm code.',
	'LOG_INCORRECT_CODE_DATA'		=> 'Code Shown: "%s"<br />Code Entered: "%s"',
	'LOG_USER_SFS_ACTIVATION'		=> '%s registered and was flagged as a possible spam account by Stop Forum Spam.',
	'LOG_SENT_PM'					=> 'Sent a PM<br />To list: %s',
	'LOG_SPAM_PM_DENIED'			=> 'A private message was flagged as spam and denied from being sent.<br />The message subject was:<br />%s<br /><br />The message was:<br />%s',
	'LOG_SPAM_PM_DENIED_AKISMET'	=> 'A private message was flagged by Akismet as spam and denied from being sent.<br />The message subject was:<br />%s<br /><br />The message was:<br />%s',
	'LOG_SPAM_POST_DENIED'			=> 'A post was flagged as spam and denied from posting.<br />The message subject was:<br />%s<br /><br />The message was:<br />%s',
	'LOG_SPAM_POST_DENIED_AKISMET'	=> 'A post was flagged as spam by Akismet and denied from posting.<br />The message subject was:<br />%s<br /><br />The message was:<br />%s',
	'LOG_SPAM_PROFILE_DENIED'		=> 'One or more profile fields entered were flagged as spam.<br />The information submitted:<br /><br />%s',
	'LOG_SPAM_SIGNATURE_DENIED'		=> 'Signature was flagged as spam.<br />The signature was:<br />%s',
	'LOG_SPAM_USER_DENIED_SFS'		=> 'User was blocked from registering by the Stop Forum Spam settings.<br />The query was:<br />%s',
	'LOG_USER_FLAGGED'				=> '%s was flagged.',
	'LOG_USER_UNFLAGGED'			=> 'The flag on %s was removed.',

	'acl_a_asacp'					=> array(
		'lang'						=> 'Can manage Anti-Spam ACP',
		'cat'						=> 'settings',
	),

	'acl_m_asacp_ban'				=> array(
		'lang'						=> 'Can "One Click Ban" users<br /><em>See .MODS-&gt;Anti-Spam ACP Settings.</em>',
		'cat'						=> 'misc',
	),

	'acl_m_asacp_ip_search'			=> array(
		'lang'						=> 'Can use IP Search',
		'cat'						=> 'misc',
	),

	'acl_a_asacp_profile_fields'	=> array(
		'lang'						=> 'Can change Profile Fields settings',
		'cat'						=> 'settings',
	),

	'acl_m_asacp_spam_log'			=> array(
		'lang'						=> 'Can view Spam Log',
		'cat'						=> 'misc',
	),

	'acl_a_asacp_spam_words'		=> array(
		'lang'						=> 'Can manage Spam Words',
		'cat'						=> 'settings',
	),

	'acl_m_asacp_user_flag'			=> array(
		'lang'						=> 'Can Flag users, view the Flag Log, and view the Flagged User List',
		'cat'						=> 'misc',
	),

));

?>