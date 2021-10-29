<?php
/**
 *
 * phpBB External Integration. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Scott Wichser, https://github.com/blast007
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
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
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, [
	'ACP_PHPBBEXTERNALINTEGRATION_TITLE'	=> 'phpBB External Integration Module',
	'ACP_PHPBBEXTERNALINTEGRATION'			=> 'phpBB External Integration API Key Management',

	'ACP_PHPBBEXTERNALINTEGRATION_KEY_NAME'=>		'Key Name',
	'ACP_PHPBBEXTERNALINTEGRATION_ALLOWED_IPS'		=> 'Allowed IPs',
	'ACP_PHPBBEXTERNALINTEGRATION_ALLOWED_IPS_HELP'	=> 'Comma separated addresses, IPv4/6, with CIDR support',
	'ACP_PHPBBEXTERNALINTEGRATION_PERMISSIONS'		=> 'Permissions',
	'ACP_PHPBBEXTERNALINTEGRATION_PERM_REGISTER'	=> 'Register',
	'ACP_PHPBBEXTERNALINTEGRATION_PERM_LOGIN'		=> 'Login',
	'ACP_PHPBBEXTERNALINTEGRATION_PERM_MANAGE'		=> 'Manage',
	'ACP_PHPBBEXTERNALINTEGRATION_ACTIONS'			=> 'Actions',

	'LOG_ACP_PHPBBEXTERNALINTEGRATION_KEY_ADDED'	=> '<strong>phpBB External Integration key added</strong>',
	'LOG_ACP_PHPBBEXTERNALINTEGRATION_KEY_DELETED'	=> '<strong>phpBB External Integration key deleted</strong>',

	'ACP_PHPBBEXTERNALINTEGRATION_KEY_ADD_SUCCESS'		=> 'API key successfully added.',
	'ACP_PHPBBEXTERNALINTEGRATION_KEY_DELETE_SUCCESS'	=> 'API key successfully deleted.',

	'ACP_PHPBBEXTERNALINTEGRATION_KEY_ADD_ERRORED'		=> 'API key could not be added.',
	'ACP_PHPBBEXTERNALINTEGRATION_KEY_DELETE_ERRORED'	=> 'API key could not be deleted.',

	'ACP_PHPBBEXTERNALINTEGRATION_KEY_CONFIRM_DELETE'	=> 'Are you sure you want to delete this API key?',
]);
