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


	// Error when user_add() fails
	'PHPBBEXTERNALINTEGRATION_REGISTRATION_FAILURE' => 'There was an error creating the user account. Please contact an administrator for assistance.',

	// Error when an authenticator provider isn't set (should this ever happen?)
	'PHPBBEXTERNALINTEGRATION_NO_AUTH_PROVIDER' => 'Authentication method not found',

	'PHPBBEXTERNALINTEGRATION_LOGIN_UNSUPPORTED_STATUS' => 'Unsupported authentication status',
	'PHPBBEXTERNALINTEGRATION_LOGIN_INCORRECT_CREDENTIALS' => 'The username or password was incorrect',


	'ACP_PHPBBEXTERNALINTEGRATION_SETTING_SAVED'	=> 'Settings have been saved successfully!',

	'ACP_PHPBBEXTERNALINTEGRATION_API_KEYS' => 'API Keys',
	'ACP_PHPBBEXTERNALINTEGRATION_API_KEY_CREATED'	=> 'API key has been created successfully!',
	'ACP_PHPBBEXTERNALINTEGRATION_API_KEY_REMOVED'	=> 'API key has been deleted successfully!',
	'ACP_PHPBBEXTERNALINTEGRATION_API_KEY_UPDATED'	=> 'API key has been updated successfully!',


	// Override the registration text
	'UCP_REGISTER_DISABLE' => 'Registration through the forum is disabled. Please use the new registration link.',
]);
