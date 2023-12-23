<?php
/**
 *
 * phpBB External Integration. An extension for the phpBB Forum Software package.
 *
 * @copyright (c) 2021, Scott Wichser, https://github.com/blast007
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace snailpaste\phpbbexternalintegration\acp;

/**
 * phpBB External Integration ACP module.
 */
class main_module
{
	public $page_title;
	public $tpl_name;
	public $u_action;

	/**
	 * Main ACP module
	 *
	 * @param int    $id   The module ID
	 * @param string $mode The module mode (for example: manage or settings)
	 * @throws \Exception
	 */
	public function main($id, $mode)
	{
		global $phpbb_container;

		/** @var \phpbb\language\language $language */
		$language = $phpbb_container->get('language');

		// Add our common language file
		$language->add_lang('common', 'snailpaste/phpbbexternalintegration');

		/** @var \phpbb\request\request $request */
		$request = $phpbb_container->get('request');

		/** @var \snailpaste\phpbbexternalintegration\controller\acp_controller $acp_controller */
		$acp_controller = $phpbb_container->get('snailpaste.phpbbexternalintegration.controller.acp');

		// Requests
		$action = $request->variable('action', '');
		$api_key_id = $request->variable('api_key_id', 0);

		// Make the $u_action url available in our ACP controller
		$acp_controller->set_page_url($this->u_action);

		// Load a template from adm/style for our ACP page
		$this->tpl_name = 'acp_phpbbexternalintegration_body';

		// Set the page title for our ACP page
		$this->page_title = $language->lang('ACP_PHPBBEXTERNALINTEGRATION_TITLE');

		switch($action)
		{
			case 'add':
				$acp_controller->add_key();
				return;

			case 'delete':
				if (confirm_box(true)) {
					$acp_controller->delete_key($api_key_id);
				}
				else {
					confirm_box(false, $language->lang('ACP_PHPBBEXTERNALINTEGRATION_KEY_CONFIRM_DELETE'), build_hidden_fields([
						'api_key_id' => $api_key_id,
						'mode' => $mode,
						'action' => $action,
					]));
				}
				return;
		}

		// Load the display options handle in our ACP controller
		$acp_controller->display_options();
	}
}
