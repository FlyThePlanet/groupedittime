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
	private $s_cannot_edit_time = bool;

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

			'core.posting_modify_cannot_edit_conditions'	=> 'posting_modify_cannot_edit_conditions',
			'core.viewtopic_modify_post_action_conditions'	=> 'viewtopic_modify_post_action_conditions',

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

	public function posting_modify_cannot_edit_conditions($event)
	{
		$sql = 'SELECT group_id, group_edit_time
			FROM ' . $this->tables['groups'] . '
			WHERE group_edit_time <> ' . (int) 0;
		$result = $this->db->sql_query($sql);
		$group_id_ary = $group_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_id_ary[$row['group_id']] = (int) $row['group_edit_time'];
		}
		$this->db->sql_freeresult($result);

		if (!function_exists('group_memberships'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		foreach ($group_id_ary as $group_id => $group_edit_time)
		{
			if ($event['post_data']['post_time'] >= time() - ($group_edit_time * 60))
			{
				$group_ids[] = (int) $group_id;
			}
		}

		$event['post_data']['s_group_edit'] = true;

		if ($group_ids)
		{
			$event['post_data']['s_group_edit'] = !group_memberships($group_ids, $this->user->data['user_id'], true);
		}
	}

	public function viewtopic_modify_post_data($event)
	{
		$sql = 'SELECT group_id, group_edit_time
			FROM ' . $this->tables['groups'] . '
			WHERE group_edit_time <> ' . (int) 0;
		$result = $this->db->sql_query($sql);
		$group_id_ary = $group_ids = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_id_ary[$row['group_id']] = (int) $row['group_edit_time'];
		}
		$this->db->sql_freeresult($result);

		if (!function_exists('group_memberships'))
		{
			include($this->root_path . 'includes/functions_user.' . $this->php_ext);
		}

		$rowset = $event['rowset'];

		foreach ($rowset as $post_id => $post_data)
		{
			foreach ($group_id_ary as $group_id => $group_edit_time)
			{
				if ($post_data['post_time'] >= time() - ($group_edit_time * 60))
				{
					$group_ids[] = (int) $group_id;
				}
			}

			$post_data['s_group_edit'] = true;

			if ($group_ids)
			{
				$post_data['s_group_edit'] = !group_memberships($group_ids, $this->user->data['user_id'], true);
			}

			$rowset[$post_id] = $post_data;
		}

		$event['rowset'] = $rowset;
	}

	public function viewtopic_modify_post_action_conditions($event)
	{
		$event['s_cannot_edit_time'] = !$event['row']['s_group_edit'];
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
