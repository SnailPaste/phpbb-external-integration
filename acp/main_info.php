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
 * phpBB External Integration ACP module info.
 */
class main_info
{
	public function module()
	{
		return [
			'filename'	=> '\snailpaste\phpbbexternalintegration\acp\main_module',
			'title'		=> 'ACP_PHPBBEXTERNALINTEGRATION_TITLE',
			'modes'		=> [
				'settings'	=> [
					'title'	=> 'ACP_PHPBBEXTERNALINTEGRATION',
					'auth'	=> 'ext_snailpaste/phpbbexternalintegration && acl_a_phpbbexternalintegration',
					'cat'	=> ['ACP_PHPBBEXTERNALINTEGRATION_TITLE'],
				],
			],
		];
	}
}
