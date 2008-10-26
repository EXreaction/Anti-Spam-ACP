<?php
/**
* @package phpBB3 UMIF - Unified MOD Install File
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
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
	'ACTION'						=> 'Action',
	'AUTH_CACHE_PURGE'				=> 'Purging the Auth Cache',

	'CACHE_PURGE'					=> 'Purging your forum\'s cache',
	'CONFIGURE'						=> 'Configure',
	'CONFIG_ADD'					=> 'Adding new config variable: %s',
	'CONFIG_ALREADY_EXISTS'			=> 'ERROR: Config variable %s already exists.',
	'CONFIG_NOT_EXIST'				=> 'ERROR: Config variable %s does not exist.',
	'CONFIG_REMOVE'					=> 'Removing config variable: %s',
	'CONFIG_UPDATE'					=> 'Updating config variable: %s',

	'FAIL'							=> 'Fail',

	'IMAGESET_CACHE_PURGE'			=> 'Refreshing the %s imageset',
	'INSTALL'						=> 'Install',

	'MODULE_ADD'					=> 'Adding %1$s module: %2$s',
	'MODULE_NOT_EXIST'				=> 'ERROR: Module does not exist.',
	'MODULE_REMOVE'					=> 'Removing %1$s module: %2$s',

	'PERMISSION_ADD'				=> 'Adding new permission option: %s',
	'PERMISSION_ALREADY_EXISTS'		=> 'ERROR: Permission option %s already exists.',
	'PERMISSION_NOT_EXIST'			=> 'ERROR: Permission option %s does not exist.',
	'PERMISSION_REMOVE'				=> 'Removing permission option: %s',

	'SUCCESS'						=> 'Success',

	'TABLE_ADD'						=> 'Adding a new database table: %s',
	'TABLE_ALREADY_EXISTS'			=> 'ERROR: Database table %s already exists.',
	'TABLE_COLUMN_ADD'				=> 'Adding a new column named %2$s to table %1$s',
	'TABLE_COLUMN_ALREADY_EXISTS'	=> 'ERROR: The column %2$s already exists on table %1$s.',
	'TABLE_COLUMN_NOT_EXIST'		=> 'ERROR: The column %2$s does not exist on table %1$s.',
	'TABLE_COLUMN_REMOVE'			=> 'Removing the column named %2$s from table %1$s',
	'TABLE_COLUMN_UPDATE'			=> 'Updating a column named %2$s from table %1$s',
	'TABLE_KEY_ADD'					=> 'Adding a key named %2$s to table %1$s',
	'TABLE_KEY_ALREADY_EXIST'		=> 'ERROR: The index %2$s already exists on table %1$s.',
	'TABLE_KEY_NOT_EXIST'			=> 'ERROR: The index %2$s does not exist on table %1$s.',
	'TABLE_KEY_REMOVE'				=> 'Removing a key named %2$s from table %1$s',
	'TABLE_NOT_EXIST'				=> 'ERROR: Database table %s does not exist.',
	'TABLE_REMOVE'					=> 'Removing database table: %s',
	'TEMPLATE_CACHE_PURGE'			=> 'Refreshing the %s template',
	'THEME_CACHE_PURGE'				=> 'Refreshing the %s theme',

	'UNINSTALL'						=> 'Uninstall',
	'UNKNOWN'						=> 'Unknown',
));

?>