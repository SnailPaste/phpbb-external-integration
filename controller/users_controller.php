<?php
/**
 *
 * phpBB External Integration. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Scott Wichser, https://github.com/blast007
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace snailpaste\phpbbexternalintegration\controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\IpUtils;

// TODO:
//   * Function for editing profile fields or renaming the account
//   * Function for activating an account
//   * Function for deleting an account?

/**
 * phpBB External Integration main controller.
 */
class users_controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\controller\helper */
	protected $helper;

	/** @var ContainerInterface */
	protected $container;

	/** @var \phpbb\auth\provider\db */
	protected $auth_db;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \snailpaste\phpbbexternalintegration\operators\api_key */
	protected $api_key_operator;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\user */
	protected $user;

	/** @var string phpbb_root_path */
	protected $phpbb_root_path;

	/** @var string phpEx */
	protected $php_ext;

	protected $acl;

	// TODO: Extend from a custom base controller that has ACL loading in it? Or have an ACL class we use?

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config		$config		Config object
	 * @param \phpbb\controller\helper	$helper		Controller helper object
	 * @param ContainerInterface $container Container object
	 * @param \phpbb\template\template	$template	Template object
	 * @param \phpbb\language\language	$language	Language object
	 * @param \phpbb\db\driver\driver_interface $db Database object
	 * @param \snailpaste\phpbbexternalintegration\operators\api_key $api_key_operator	API keys operator object
	 * @param \phpbb\request\request	$request	Request object
	 * @param \phpbb\user			   $user	   User object
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\controller\helper $helper, ContainerInterface $container, \phpbb\auth\provider\db $auth_db, \phpbb\template\template $template, \phpbb\language\language $language, \phpbb\db\driver\driver_interface $db, \snailpaste\phpbbexternalintegration\operators\api_key $api_key_operator, \phpbb\request\request $request, \phpbb\user $user, $phpbb_root_path, $phpEx)
	{
		// TODO: phpBB configuration sanity checks to make sure everything works as intended
		$this->config		= $config;
		$this->helper		= $helper;  // UNUSED
		$this->container	= $container;
		$this->auth_db		= $auth_db;
		$this->template		= $template; // UNUSED
		$this->language		= $language;
		$this->db		= $db;
		$this->api_key_operator	= $api_key_operator;
		$this->request		= $request;
		$this->user		= $user;
		$this->phpbb_root_path	= $phpbb_root_path;
		$this->php_ext		= $phpEx;

		// Default to no permission
		$this->acl = [
			'register' => false,
			'login' => false
		];

		// Initialize the token to an empty string
		$bearer_token = '';

		// For Apache, get the header this way for now.
		// TODO: Look into the implications of adding this line to the top level .htaccess, since it would differ
		//  slightly from the commented out version (namely, that this one lacks the ,L part). But with ",L", it breaks
		//  the rewriting of the API routes. If this would work, the Apache function below would be unnecessary.
		//	  RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
		//if (function_exists('apache_request_headers')) {
			// Get all Apache headers with uppercase array keys
			$headers = array_change_key_case(apache_request_headers(), CASE_UPPER);
			// If the AUTHORIZATION header exists and has the correct format, extract the API key
			if (array_key_exists('AUTHORIZATION', $headers) && substr($headers['AUTHORIZATION'], 0, strlen('Bearer ')) == 'Bearer ')
				$bearer_token = substr($headers['AUTHORIZATION'], strlen('Bearer '));
			unset($headers);
		/*}
		else {
			// TODO: Test this with at least one non-Apache web server
			$header = $this->request->header('Authorization');
			if ($header && substr($header, 0, strlen('Bearer ')) == 'Bearer ')
				$bearer_token = substr($header, strlen('Bearer '));
			unset($header);
			die("Untested code path");
		}*/

		// Store the remote address
		// TODO: Check if this is right when behind a load balancer
		$remote_ip = $this->request->server('REMOTE_ADDR');

		// If the key is empty, just bail out here
		if (empty($bearer_token))
			return;

		try {
			// Try to fetch the API key information
			$key = $api_key_operator->get_api_key_by_value($bearer_token);

			// If there is a list of allowed IPs, and the request IP is within one of the ranges, update our ACL
			$allowed_ips = $key->get_allowed_ips();
			if (strlen($allowed_ips) > 0 && IpUtils::checkIp($remote_ip, explode(',', $allowed_ips))) {
				$this->acl['register'] = $key->register_allowed();
				$this->acl['login'] = $key->login_allowed();
			}
		}
		catch(\snailpaste\phpbbexternalintegration\exception\base $e)
		{
			// TODO: Do something in case the provided API key doesn't exist or is not provided?
		}
	}

	protected function has_perm($perm)
	{
		return (array_key_exists($perm, $this->acl) && $this->acl[$perm] === true);
	}

	private function error_response(array $errors)
	{
		// TODO: Switch to using more of the built-in phpBB errors, which would require including the field name.
		//   For example, $this->user->lang('FIELD_REQUIRED', $this->get_field_name($field_data['lang_name']))
		//   Part of this might just to use the built-in validation functions which already do this, such as
		//   validate_string_profile_field() in phpbb/profilefields/type/type_string_common.php

		// Convert language keys to language strings
		$errorStrings = [];
		$errors = array_unique($errors, SORT_STRING);
		foreach($errors as $error)
			$errorStrings[$error] = $this->language->lang($error);

		return new \Symfony\Component\HttpFoundation\JsonResponse([
			'errors' => $errorStrings
		], 200);
	}

	/**
	 * Controller handler for route /api/users/register
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function register()
	{
		if (!$this->has_perm('register'))
			throw new \phpbb\exception\http_exception(404, 'PAGE_NOT_FOUND');

		// TODO: Handle COPPA on the registration page. The account data should not be stored until parental permission.
		//   And at that point, do we even care about using the COPPA group in phpBB?

		// Load the UCP language files for providing validation error messages.
		$this->language->add_lang('ucp');

		// Fetch the registration data
		$registration_info = [
			'username' => $this->request->variable('username', '', true, \phpbb\request\request_interface::POST),
			'password' => $this->request->variable('password', '', true, \phpbb\request\request_interface::POST),
			'email' => $this->request->variable('email', '', true, \phpbb\request\request_interface::POST),
			'ip_address' => $this->request->variable('ip_address', '', false, \phpbb\request\request_interface::POST),
			'is_coppa' => $this->request->variable('is_coppa', false, false, \phpbb\request\request_interface::POST)
		];

		// Normalize the username
		$registration_info['username'] = utf8_normalize_nfc($registration_info['username']);

		// Convert email address to lowercase
		// TODO: Normalize a copy the email address (removing periods before @) to detect duplicate Gmail addresses
		$registration_info['email'] = strtolower($registration_info['email']);

		if (!function_exists('validate_data')) {
			require($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		// Validate username, password, and email
		$errors = validate_data($registration_info, [
			'username' => [
				['string', false, $this->config['min_name_chars'], $this->config['max_name_chars']],
				['username', '']
			],
			'password' => [
				['string', false, $this->config['min_pass_chars'], $this->config['max_pass_chars']],
				['password']
			],
			'email' => [
				['string', false, 6, 60],
				['user_email']
			]
		]);

		// If there were any errors, bail and send those back
		if (sizeof($errors) > 0)
			return $this->error_response($errors);

		// Find the group ID for the designated group, depending on if this is a COPPA user
		// TODO: If we continue to use the COPPA group, should we store the birth date so we can clear the COPPA status
		//   later?  Would that itself cause problems with COPPA?
		$group_name = ($registration_info['is_coppa']) ? 'REGISTERED_COPPA' : 'REGISTERED';
		$sql = 'SELECT group_id
		FROM ' . GROUPS_TABLE . "
		WHERE group_name = '" . $this->db->sql_escape($group_name) . "' AND group_type = " . GROUP_SPECIAL;
		$result = $this->db->sql_query($sql);
		$row = $this->db->sql_fetchrow($result);
		$group_id = $row['group_id'];


		// Get the password manager so we can hash the password
		$passwords_manager = $this->container->get('passwords.manager');

		// Prepare data for user creation
		$user_row = array(
			'username'		=> $registration_info['username'],
			'user_password'		=> $passwords_manager->hash($registration_info['password']),
			'user_email'		=> $registration_info['email'],
			'group_id'		=> (int) $group_id,
			'user_timezone'		=> (float) 0, // UTC
			'user_lang'		=> 'en', // TODO: Support multiple languages
			'user_type'		=> USER_INACTIVE,
			'user_actkey'		=> gen_rand_string(mt_rand(6, 10)),
			'user_ip'		=> $registration_info['ip_address'],
			'user_regdate'		=> time(),
			'user_inactive_reason'	=> INACTIVE_REGISTER,
			'user_inactive_time'	=> time(),
			// TODO: Check other values to provide here, such as user_new or user_allow_viewemail
		);

		// Create the user
		$user_id = user_add($user_row);

		// If we have a valid user ID, send the welcome email
		if ($user_id !== false) {
			// TODO: Custom welcome email template
			$email_template = 'user_welcome_inactive';
			$user_actkey = $user_row['user_actkey'];

			// TODO: Add an endpoint for activating an account and have activation go through the account site

			// Load the messenger class if needed
			if (!class_exists('messenger'))
			{
				require($this->phpbb_root_path . 'includes/functions_messenger.' . $this->php_ext);
			}

			$messenger = new \messenger(false);
			$messenger->template($email_template);
			$messenger->to($registration_info['email'], $registration_info['username']);
			//$messenger->anti_abuse_headers($this->config, $this->user);
			$messenger->assign_vars([
				// TODO: Support other languages?
				'WELCOME_MSG' => htmlspecialchars_decode($this->language->lang('WELCOME_SUBJECT', $this->config['sitename'])),
				'USERNAME'	=> htmlspecialchars_decode($registration_info['username']),
				'U_ACTIVATE'  => generate_board_url() . "/ucp.{$this->php_ext}?mode=activate&u=$user_id&k=$user_actkey"
			]);

			$messenger->send(NOTIFY_EMAIL);

			return new \Symfony\Component\HttpFoundation\JsonResponse([
				'user_id' => $user_id,
				'username' => $user_row['username'],
				'email' => $user_row['user_email']
			], 200);
		} else {
			// TODO: Log failures so we can figure out when/why there is an issue
			return $this->error_response(['PHPBBEXTERNALINTEGRATION_REGISTRATION_FAIL']);
		}
	}

	/**
	 * Controller handler for route /api/users/login
	 *
	 * @return \Symfony\Component\HttpFoundation\Response A Symfony Response object
	 */
	public function login()
	{
		if (!$this->has_perm('login'))
			throw new \phpbb\exception\http_exception(404, 'PAGE_NOT_FOUND');

		// TODO: Determine if all of these returned errors should be end user errors or just logged

		// Load the UCP language files for providing validation error messages.
		$this->language->add_lang('ucp');

		// Fetch the registration data
		$login_info = [
			'username' => $this->request->variable('username', '', true, \phpbb\request\request_interface::POST),
			'password' => $this->request->variable('password', '', true, \phpbb\request\request_interface::POST),
			//'remember_me' => $this->request->variable('password_confirm', false, false, \phpbb\request\request_interface::POST)
		];

		// Normalize the username
		$login_info['username'] = utf8_normalize_nfc($login_info['username']);

		if (!function_exists('user_get_id_name')) {
			require($this->phpbb_root_path . 'includes/functions_user.' . $this->php_ext);
		}

		// Look up the user ID from the username
		/*$user_id_ary = [];
		$username_ary = [$login_info['username']];
		$result = user_get_id_name($user_id_ary, $username_ary, [USER_NORMAL, USER_FOUNDER]);

		if ($result !== false)
		{
			return $this->error_response([$result]);
		}*/

        // FIXME: Is there a point of doing this instead of just immediately using $this->auth_db?
		$provider_collection = $this->container->get('auth.provider_collection');

		$provider = $provider_collection->get_provider();
		if (!$provider)
			return $this->error_response(['PHPBBEXTERNALINTEGRATION_NO_AUTH_PROVIDER']);

		// If the provider is set to OAuth, use the DB authenticator instead (since our site would be the login page)
		if (get_class($provider) === 'phpbb\auth\provider\oauth')
			$provider = $this->auth_db;

		// Attempt the login
		$login = $provider->login($login_info['username'], $login_info['password']);

		// If the auth module wants us to create an empty profile, ignore that request since we'd require explicit
		// registration. I imagine this will never happen since we'd likely always be using the DB provider.
		if ($login['status'] == LOGIN_SUCCESS_CREATE_PROFILE)
			return $this->error_response(['PHPBBEXTERNALINTEGRATION_LOGIN_UNSUPPORTED_STATUS']);

		// If the auth module wants to link to an existing profile, also ignore that request and throw an error.
		if ($login['status'] == LOGIN_SUCCESS_LINK_PROFILE)
			return $this->error_response(['PHPBBEXTERNALINTEGRATION_LOGIN_UNSUPPORTED_STATUS']);

		if ($login['status'] == LOGIN_SUCCESS)
		{
			return new \Symfony\Component\HttpFoundation\JsonResponse([
				'user_id' => $login['user_row']['user_id']
			], 200);
		}

		// TODO: Ensure that phpBB locks out the account/IP after a specific number of invalid attempts

		if ($login['status'] == LOGIN_ERROR_ATTEMPTS)
		{
			return $this->error_response(['LOGIN_ERROR_ATTEMPTS']);
		}

		return $this->error_response(['PHPBBEXTERNALINTEGRATION_LOGIN_INCORRECT_CREDENTIALS']);
	}
}
