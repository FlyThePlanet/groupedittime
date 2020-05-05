<?php
/**
 *
 * Group Edit Time extension for the phpBB Forum Software package
 *
 * @copyright (c) 2020, Kailey Truscott, https://www.layer-3.org/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kinerity\groupedittime\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Group Edit Time event listener
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var string */
	protected $root_path;

	/** @var string */
	protected $php_ext;

	/** @var array */
	protected $tables;

	/** @var array */
	private $group_id_ary = [];

	/**
	 * Constructor
	 *
	 * @param \phpbb\db\driver\driver_interface  $db
	 * @param \phpbb\request\request             $request
	 * @param \phpbb\template\template           $template
	 * @param \phpbb\user                        $user
	 * @param string                             $root_path
	 * @param string                             $php_ext
	 * @param array                              $tables
	 */
	public function __construct(\phpbb\db\driver\driver_interface $db, \phpbb\request\request $request, \phpbb\template\template $template, \phpbb\user $user, $root_path, $php_ext, $tables)
	{
		$this->db = $db;
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
		$this->root_path = $root_path;
		$this->php_ext = $php_ext;
		$this->tables = $tables;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.acp_manage_group_request_data'	=> 'acp_manage_group_request_data',
			'core.acp_manage_group_initialise_data'	=> 'acp_manage_group_initialise_data',
			'core.acp_manage_group_display_form'	=> 'acp_manage_group_display_form',

			'core.posting_modify_cannot_edit_conditions'	=> 'main',
			'core.viewtopic_modify_post_action_conditions'	=> 'main',

			'core.viewtopic_modify_post_data'	=> 'viewtopic_modify_post_data',

			'core.user_setup'	=> 'load_language_on_setup',
		];
	}

	public function acp_manage_group_request_data($event)
	{
		$submit_ary = $event['submit_ary'];
		$submit_ary['edit_time'] = $this->request->variable('group_edit_time', 0);
		$event['submit_ary'] = $submit_ary;
	}

	public function acp_manage_group_initialise_data($event)
	{
		$test_variables = $event['test_variables'];
		$test_variables['edit_time'] = 'int';
		$event['test_variables'] = $test_variables;
	}

	public function acp_manage_group_display_form($event)
	{
		$group_row = $event['group_row'];

		$this->template->assign_vars([
			'GROUP_EDIT_TIME'	=> (isset($group_row['group_edit_time'])) ? $group_row['group_edit_time'] : 0,
		]);
	}

	public function viewtopic_modify_post_data()
	{
		$sql = 'SELECT group_id
			FROM ' . $this->tables['groups'] . '
			WHERE group_edit_time <> ' . (int) 0;
		$result = $this->db->sql_query($sql);
		while ($row = $this->db->sql_fetchrow($result))
		{
			$this->group_id_ary[] = (int) $row['group_id'];
		}
		$this->db->sql_freeresult($result);
	}

	public function main($event)
	{
		if (!function_exists('group_memberships'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$event['s_cannot_edit_time'] = group_memberships($this->group_id_ary, $this->user->data['user_id'], true) ? true : false;
	}

	/**
	 * Load common language files during user setup
	 */
	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = array(
			'ext_name' => 'kinerity/groupedittime',
			'lang_set' => 'common',
		);
		$event['lang_set_ext'] = $lang_set_ext;
	}
}
