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
	'ASACP_ENABLE'					=> 'Enable Anti-Spam ACP',
	'ASACP_ENABLE_EXPLAIN'			=> 'Set to no to disable the entire Anti-Spam ACP system.',
	'ASACP_IP_SEARCH_BOT_CHECK'		=> 'Bot Check',
	'ASACP_IP_SEARCH_EXPLAIN'		=> 'Search through the entire forum for actions made from a certain IP Address.',
	'ASACP_IP_SEARCH_LOGS'			=> 'Log Actions',
	'ASACP_IP_SEARCH_POLL_VOTES'	=> 'Poll Votes',
	'ASACP_IP_SEARCH_POSTS'			=> 'Posts',
	'ASACP_IP_SEARCH_PRIVMSGS'		=> 'Private Messages',
	'ASACP_IP_SEARCH_USERS'			=> 'Users',
	'ASACP_LOG'						=> 'Enable Spam Log',
	'ASACP_LOG_EXPLAIN'				=> 'If disabled new items will not be added to the spam log.',
	'ASACP_REGISTER_SETTINGS'		=> 'Registration Settings',
	'ASACP_REG_CAPTCHA'				=> 'Pre-Registration Captcha',
	'ASACP_REG_CAPTCHA_EXPLAIN'		=> 'This controls the display of the initial captcha shown before the registration process begins.<br />If enabled you should consider disabling "Enable visual confirmation for registrations" in General->Board configuration->User registration settings so the user does not have to fill out two captchas to register.',
	'ASACP_SETTINGS_UPDATED'		=> 'Anti-Spam ACP Settings have been updated successfully.',
	'ASACP_VERSION'					=> 'Version Information',
	'ASACP_SPAM_WORDS_EXPLAIN'		=> 'Enter and manage trigger words for the spam words system.',
	'SPAM_WORD_TEXT' => 'Spam Word Text',
	'NO_SPAM_WORDS' => 'No Spam Words in database.',
	'REGEX' => 'Regular Expression',
	'REGEX_EXPLAIN' => 'Select Yes to use a regular expression match from the given spam word text.',
	'REGEX_AUTO' => 'Auto Regex',
	'REGEX_AUTO_EXPLAIN' => 'Select Yes to have the system automatically create a regular expression match from the given spam word text.',
	'NO_SPAM_WORD' => 'The selected word does not exist.',
	'SPAM_WORD_ADD_SUCCESS' => 'Spam word added successfully.',
	'SPAM_WORD_EDIT_SUCCESS' => 'Spam word edited successfully.',
	'SPAM_WORD_DELETE_SUCCESS' => 'Spam word deleted successfully.',
	'DELETE_SPAM_WORD' => 'Delete Spam Word',
	'DELETE_SPAM_WORD_CONFIRM' => 'Are you sure you want to delete this spam word?',
	'ASACP_SPAM_WORDS_FLAG_LIMIT' => 'Flag Count before marking as spam',
	'ASACP_SPAM_WORDS_FLAG_LIMIT_EXPLAIN' => 'If the messages are marked as spam more than this many or more times the post will be either denied or require approval.',
	'ASACP_SPAM_WORDS_POSTING_ACTION' => 'Action for Spam Posts',
	'ASACP_SPAM_WORDS_POSTING_ACTION_EXPLAIN' => 'Select the action you would like performed when a post is flagged as spam.',
	'ASACP_SPAM_WORDS_PROFILE_ACTION' => 'Action for Spam Profile Information',
	'ASACP_SPAM_WORDS_PROFILE_ACTION_EXPLAIN' => 'Select the action you would like performed when information entered into a user\'s profile is flagged as spam.',
	'DENY_SUBMISSION' => 'Deny Submission',
	'REQUIRE_APPROVAL' => 'Require moderator approval',
	'NOTHING' => 'Nothing',
	'ADD_WORD' => 'Add Word',
	'ASACP_SPAM_WORDS_ENABLE' => 'Enable Spam Words',
	'ASACP_SPAM_WORDS_ENABLE_EXPLAIN' => 'Set to no to disable the entire Spam Words system.',
	'ASACP_SPAM_WORDS_POST_LIMIT' => 'Post count',
	'ASACP_SPAM_WORDS_POST_LIMIT_EXPLAIN' => 'If the user has a post count higher than submitted here the spam words check will not be used on that user.<br /><strong>If 0 is entered the spam words check will always run.</strong>',

	'INSTALLED_VERSION'				=> 'Installed Version',

	'LATEST_VERSION'				=> 'Latest Version',

	'NO_ITEMS'						=> 'No results from the given IP address.',
	'NOT_AVAILABLE'					=> 'Not Available',

	'VERSION'						=> 'Version',
));

?>