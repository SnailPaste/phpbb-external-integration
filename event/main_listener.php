<?php
/**
 *
 * phpBB External Integration. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Scott Wichser, https://github.com/blast007
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace snailpaste\phpbbexternalintegration\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * phpBB External Integration Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [
			'core.login_box_modify_template_data'	=> 'replace_login_box_vars',
			'core.user_setup'			=> 'load_language_on_setup',
			'core.permissions'			=> 'add_permissions',
		];
	}

	/** @var \phpbb\config\config */
	protected $config;

	/* @var \phpbb\language\language */
	protected $language;

	/* @var \phpbb\controller\helper */
	protected $helper;

	/* @var \phpbb\template\template */
	protected $template;

	/** @var string phpEx */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config		$config		Config object
	 * @param \phpbb\language\language	$language	Language object
	 * @param \phpbb\controller\helper	$helper		Controller helper object
	 * @param \phpbb\template\template	$template	Template object
	 * @param string                    $php_ext    phpEx
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\template\template $template, $php_ext)
	{
		$this->config = $config;
		$this->language = $language;
		$this->helper   = $helper;
		$this->template = $template;
		$this->php_ext  = $php_ext;
	}

	public function replace_login_box_vars($event)
	{
		$event['login_box_template_data'] = array_merge($event['login_box_template_data'], [

		]);

		// Replace the resend activation URL if it isn't empty
		if (!empty($event['login_box_template_data']['U_RESEND_ACTIVATION']))
		{
			$event['login_box_template_data'] = array_merge($event['login_box_template_data'], [
				'U_RESEND_ACTIVATION' => 'bacon'
			]);
		}
	}

	/**
	 * Load common language files during user setup
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'snailpaste/phpbbexternalintegration',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	/**
	 * Add permissions to the ACP -> Permissions settings page
	 * This is where permissions are assigned language keys and
	 * categories (where they will appear in the Permissions table):
	 * actions|content|forums|misc|permissions|pm|polls|post
	 * post_actions|posting|profile|settings|topic_actions|user_group
	 *
	 * Developers note: To control access to ACP, MCP and UCP modules, you
	 * must assign your permissions in your module_info.php file. For example,
	 * to allow only users with the a_phpbbexternalintegration permission
	 * access to your ACP module, you would set this in your acp/main_info.php:
	 *    'auth' => 'ext_snailpaste/phpbbexternalintegration && acl_a_phpbbexternalintegration'
	 *
	 * @param \phpbb\event\data	$event	Event object
	 */
	public function add_permissions($event)
	{
		$permissions = $event['permissions'];

		$permissions['a_phpbbexternalintegration'] = ['lang' => 'ACL_A_PHPBBEXTERNALINTEGRATION', 'cat' => 'misc'];

		$event['permissions'] = $permissions;
	}
}
