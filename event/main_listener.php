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
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * phpBB External Integration Event listener.
 */
class main_listener implements EventSubscriberInterface
{
	public static function getSubscribedEvents()
	{
		return [
			'core.login_box_modify_template_data'	=> 'replace_login_box_vars',
			'core.session_ip_after'		=> 'replace_remote_addr',
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

	/* @var \snailpaste\phpbbexternalintegration\operators\api_key */
	protected $api_key_operator;

	/** @var string phpEx */
	protected $php_ext;

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config		$config		Config object
	 * @param \phpbb\language\language	$language	Language object
	 * @param \phpbb\controller\helper	$helper		Controller helper object
	 * @param \phpbb\template\template	$template	Template object
	 * @param \snailpaste\phpbbexternalintegration\operators\api_key $api_key_operator	API keys operator object
	 * @param string                    $php_ext    phpEx
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\language\language $language, \phpbb\controller\helper $helper, \phpbb\template\template $template, \snailpaste\phpbbexternalintegration\operators\api_key $api_key_operator, $php_ext)
	{
		$this->config = $config;
		$this->language = $language;
		$this->helper   = $helper;
		$this->template = $template;
		$this->api_key_operator = $api_key_operator;
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

    // Store the actual user's IP address provided by the API_REMOTE_ADDR header
    // TODO: Is there a better way to do this that doesn't involve checking the API key again?
    public function replace_remote_addr($event)
    {
        // Duplicating some of the API key logic from users_controller for now
        $headers = array_change_key_case(apache_request_headers(), CASE_UPPER);
        if (!array_key_exists('AUTHORIZATION', $headers) || substr($headers['AUTHORIZATION'], 0, strlen('Bearer ')) !== 'Bearer ')
            return;
        if (!array_key_exists('API_REMOTE_ADDR', $headers))
            return;

        $bearer_token = substr($headers['AUTHORIZATION'], strlen('Bearer '));

        if (empty($bearer_token))
            return;

        try {
            // Try to fetch the API key information
            $key = $this->api_key_operator->get_api_key_by_value($bearer_token);

            // If there is a list of allowed IPs, and the request IP is within one of the ranges, update our ACL
            $allowed_ips = $key->get_allowed_ips();
            // TODO: Fetch the IP using request->server('REMOTE_ADDR') in case another ext has modified the user IP
            if (strlen($allowed_ips) > 0 && IpUtils::checkIp($event['ip'], explode(',', $allowed_ips))) {
                $event['ip'] = $headers['API_REMOTE_ADDR'];
            }
        }
        catch(\snailpaste\phpbbexternalintegration\exception\base $e)
        {
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
