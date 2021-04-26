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
	/** @var \phpbb\config\config */
	protected $config;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

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

	/**
	 * Constructor
	 *
	 * @param \phpbb\config\config               $config
	 * @param \phpbb\db\driver\driver_interface  $db
	 * @param \phpbb\language\language           $language
	 * @param \phpbb\request\request             $request
	 * @param \phpbb\template\template           $template
	 * @param \phpbb\user                        $user
	 * @param string                             $root_path
	 * @param string                             $php_ext
	 * @param array                              $tables
	 */
	public function __construct(\phpbb\config\config $config, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\request\request $request, \phpbb\template\template $template, $user, $root_path, $php_ext, $tables)
	{
		$this->config = $config;
		$this->db = $db;
		$this->language = $language;
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
			'core.viewtopic_modify_post_data'				=> 'viewtopic_modify_post_data',

			'core.user_setup'	=> 'load_language_on_setup',
		];
	}

	public function acp_manage_group_request_data($event)
	{
		$event->update_subarray('submit_ary', 'enable_edit_time', $this->request->variable('group_enable_edit_time', 0));
		$event->update_subarray('submit_ary', 'edit_time', $this->request->variable('group_edit_time', 0));
	}

	public function acp_manage_group_initialise_data($event)
	{
		$event->update_subarray('test_variables', 'enable_edit_time', 'int');
		$event->update_subarray('test_variables', 'edit_time', 'int');
	}

	/**
	 * Load the variables used to display the extension fields on the group settings form
	 */
	public function acp_manage_group_display_form($event)
	{
		$this->template->assign_vars([
			'GROUP_ENABLE_EDIT_TIME'	=> (isset($event['group_row']['group_enable_edit_time'])) ? ($event['group_row']['group_enable_edit_time'] ? ' checked="checked"' : '') : '',
			'GROUP_EDIT_TIME'			=> (isset($event['group_row']['group_edit_time'])) ? $event['group_row']['group_edit_time'] : 0,
		]);
	}

	/**
	 * Apply the group limit editing time settings to the possibility to edit or not one post
	 */
	public function posting_modify_cannot_edit_conditions($event)
	{
		$group_id_ary = $this->get_group_id_ary();

		// If the user is member of at least 1 group for which the limit editing time feature has been enabled, then look at the group(s) setting(s)
		// Otherwise, leave the core code do the job and managing the global limit editing time
		if (!empty($group_id_ary))
		{
			$group_ids = [];

			// Test if any group allows editing at this time
			foreach ($group_id_ary as $group_id => $group_edit_time)
			{
				if ($group_edit_time == 0 || ($event['post_data']['post_time'] >= time() - ($group_edit_time * 60)))
				{
					$group_ids[] = (int) $group_id;
				}
			}

			// If at least 1 group allows to edit, then allow editing
			$event['s_cannot_edit_time'] = !empty($group_ids) ? false : true;
		}
	}

	/**
	 * Apply the Group limit editing time to the display or not "Edit" buttons for each post of a topic page
	 */
	public function viewtopic_modify_post_data($event)
	{
		$group_id_ary = $this->get_group_id_ary();

		// If the current user is member of at least 1 group for which the limit editing time feature has been enabled, then apply the group(s) setting(s)
		// Otherwise, leave the core code do the job and managing the global limit editing time
		if (!empty($group_id_ary))
		{
			// Check if one group gives unlimited time to edit, otherwhise find the maximum time given
			if (in_array(0, array_values($group_id_ary)))
			{
				$max_group_edit_time = 0;
			}
			else
			{
				$max_group_edit_time = max($group_id_ary);
			}

			// Set for each post if the "Edit" button should be displayed or not
			$rowset = $event['rowset'];

			foreach ($rowset as $post_id => $post_data)
			{
				// Set to display the "Edit" button if unlimited time, or post more recent than current time - max time to edit
				if ($max_group_edit_time == 0 || ($post_data['post_time'] >= time() - ($max_group_edit_time * 60)))
				{
					$post_data['s_group_cannot_edit_time'] = false;
				}
				else
				{
					$post_data['s_group_cannot_edit_time'] = true;
				}

				$rowset[$post_id] = $post_data;
			}

			$event['rowset'] = $rowset;
		}
	}

	public function viewtopic_modify_post_action_conditions($event)
	{
		// If an instruction to overcome the global Limit editing time has been set for the post (cf. above),
		// then overcome the post $s_cannot_edit_time variable which control the display of the "Edit" button for the post
		if (isset($event['row']['s_group_cannot_edit_time']))
		{
			$event['s_cannot_edit_time'] = $event['row']['s_group_cannot_edit_time'];
		}
	}

	/**
	 * Build the list of groups the user is member of and for which the limit editing time feature has been enabled
	 */
	private function get_group_id_ary()
	{
		$sql = 'SELECT g.group_id, g.group_edit_time
			FROM ' . $this->tables['groups'] . ' g
			LEFT JOIN ' . $this->tables['user_group'] . ' ug
				ON ug.group_id = g.group_id
			WHERE g.group_enable_edit_time = 1 AND ug.user_id = ' . (int) $this->user->data['user_id'];
		$result = $this->db->sql_query($sql);
		$group_id_ary = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$group_id_ary[$row['group_id']] = (int) $row['group_edit_time'];
		}
		$this->db->sql_freeresult($result);

		return $group_id_ary;
	}

	/**
	 * Load common language files during user setup
	 */
	public function load_language_on_setup($event)
	{
		$this->language->add_lang('common', 'kinerity/groupedittime');
	}
}
