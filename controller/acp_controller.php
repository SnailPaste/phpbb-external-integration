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

/**
 * phpBB External Integration ACP controller.
 */
class acp_controller
{
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var ContainerInterface */
	protected $container;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\log\log */
	protected $log;

	/** @var \snailpaste\phpbbexternalintegration\operators\api_key */
	protected $api_key_operator;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string Custom form action */
	protected $u_action;

	/**
	 * Constructor.
	 *
	 * @param \phpbb\config\config		$config		Config object
	 * @param \phpbb\db\driver\driver_interface $db Database object
	 * @param ContainerInterface $container Container object
	 * @param \phpbb\language\language	$language	Language object
	 * @param \phpbb\log\log			$log		Log object
	 * @param \snailpaste\phpbbexternalintegration\operators\api_key $api_key_operator	API keys operator object
	 * @param \phpbb\request\request	$request	Request object
	 * @param \phpbb\template\template	$template	Template object
	 * @param \phpbb\user				$user		User object
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, ContainerInterface $container, \phpbb\language\language $language, \phpbb\log\log $log, \snailpaste\phpbbexternalintegration\operators\api_key $api_key_operator, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user)
	{
		$this->config	= $config;
		$this->db	= $db;
		$this->container	= $container;
		$this->language	= $language;
		$this->log		= $log;
		$this->api_key_operator	= $api_key_operator;
		$this->request	= $request;
		$this->template	= $template;
		$this->user		= $user;
	}

	public function populate_keys()
	{
		$apikeys = $this->api_key_operator->get_api_keys();

		foreach($apikeys as $apikey)
		{
			// Set output block vars for display in the template
			$this->template->assign_block_vars('apikeys', array(
				'KEY_NAME'	=> $apikey->get_name(),

				'ALLOWED_IPS'	=> $apikey->get_allowed_ips(),
				'PERM_REGISTER'	=> $apikey->register_allowed(),
				'PERM_LOGIN'	=> $apikey->login_allowed(),
				'PERM_MANAGE'	=> $apikey->manage_allowed(),

				'U_DELETE'		=> "{$this->u_action}&amp;action=delete&amp;api_key_id=" . $apikey->get_id(),
			));
		}
	}

	/**
	 * Display the options a user can configure for this extension.
	 *
	 * @return void
	 */
	public function display_options()
	{
		// Create a form key for preventing CSRF attacks
		add_form_key('snailpaste_phpbbexternalintegration_acp');

		$this->populate_keys();

		// Set output variables for display in the template
		$this->template->assign_vars([
			'U_ACTION'		=> $this->u_action,
		]);
	}


	public function add_key()
	{
		// Keep track of errors
		$errors = [];

		// Create a form key for preventing CSRF attacks
		add_form_key('snailpaste_phpbbexternalintegration_acp');

		// Must be a valid form submission
		if (!$this->request->is_set_post('submit') || !check_form_key('snailpaste_phpbbexternalintegration_acp')) {
			$errors[] = $this->language->lang('FORM_INVALID');
		}
		else
		{
			// Collect form data
			$data = [
				'api_key_name'					=> $this->request->variable('snailpaste_phpbbexternalintegration_key_name', '', true),
				// TODO: Look into how best to generate this
				'api_key_value'				 => bin2hex(random_bytes(64)),
				'api_key_allowed_ips'			=> $this->request->variable('snailpaste_phpbbexternalintegration_allowed_ips', ''),
				'api_key_perm_register'			=> $this->request->variable('snailpaste_phpbbexternalintegration_perm_register', ''),
				'api_key_perm_login'			=> $this->request->variable('snailpaste_phpbbexternalintegration_perm_login', ''),
				'api_key_perm_manage'			=> $this->request->variable('snailpaste_phpbbexternalintegration_perm_manage', ''),
			];

			// Map the data to the setters
			$map_fields = [
				'set_name' => $data['api_key_name'],
				'set_key' => $data['api_key_value'],
				'set_allowed_ips' => $data['api_key_allowed_ips'],
				'set_perm_register' => $data['api_key_perm_register'],
				'set_perm_login' => $data['api_key_perm_login'],
				'set_perm_manage' => $data['api_key_perm_manage'],
			];

			// Create an entity
			/* @var $entity \snailpaste\phpbbexternalintegration\entity\api_key */
			$entity = $this->container->get('snailpaste.phpbbexternalintegration.entity');

			foreach($map_fields as $entity_function => $key_data)
			{
				try
				{
					$entity->$entity_function($key_data);
				}
				catch(\snailpaste\phpbbexternalintegration\exception\base $e)
				{
					$errors[] = $e->get_message($this->language);
				}
			}

			unset($map_fields);

			// If no errors, process the form data
			if (empty($errors))
			{
				$entity = $this->api_key_operator->add_api_key($entity);

				$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PHPBBEXTERNALINTEGRATION_KEY_ADDED', time(), array($entity->get_name()));

				trigger_error($this->language->lang('ACP_PHPBBEXTERNALINTEGRATION_KEY_ADD_SUCCESS') . '<br><input type="text" value="' . $data['api_key_value'] . '">' . adm_back_link($this->u_action));
			}
		}

		$this->populate_keys();

		$s_errors = !empty($errors);

		// Set output variables for display in the template
		$this->template->assign_vars([
			'S_ERROR'		=> $s_errors,
			'ERROR_MSG'		=> $s_errors ? implode('<br />', $errors) : '',
			'U_ACTION'		=> $this->u_action,
		]);
	}

	public function delete_key($api_key_id)
	{
		// TODO: Verify we got a valid ID and got an API key?
		// TODO: Should we be using check_form_key here too?
		$entity = $this->container->get('snailpaste.phpbbexternalintegration.entity')->load($api_key_id);

		try
		{
			$this->api_key_operator->delete_api_key($api_key_id);
		}
		catch (\snailpaste\phpbbexternalintegration\exception\base $e)
		{
			// TODO: Should we generate a JSON response for our errors?
			trigger_error($this->lang->lang('ACP_PHPBBEXTERNALINTEGRATION_KEY_DELETE_ERRORED') . adm_back_link($this->u_action), E_USER_WARNING);
		}

		// Log the action
		$this->log->add('admin', $this->user->data['user_id'], $this->user->ip, 'LOG_ACP_PHPBBEXTERNALINTEGRATION_KEY_DELETED', time(), array($entity->get_name()));

		// If AJAX was used, show user a result message
		if ($this->request->is_ajax())
		{
			$json_response = new \phpbb\json_response;
			$json_response->send(array(
				'MESSAGE_TITLE' => $this->language->lang('INFORMATION'),
				'MESSAGE_TEXT'  => $this->language->lang('ACP_PHPBBEXTERNALINTEGRATION_KEY_DELETE_SUCCESS'),
				'REFRESH_DATA'  => array(
					'time'  => 3
				)
			));
		}
	}

	/**
	 * Set custom form action.
	 *
	 * @param string	$u_action	Custom form action
	 * @return void
	 */
	public function set_page_url($u_action)
	{
		$this->u_action = $u_action;
	}
}
