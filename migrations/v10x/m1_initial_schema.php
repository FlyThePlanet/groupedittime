<?php
/**
 *
 * Group Edit Time extension for the phpBB Forum Software package
 *
 * @copyright (c) 2020, Kailey Truscott, https://www.layer-3.org/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace kinerity\groupedittime\migrations\v10x;

class m1_initial_schema extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	/**
	 * Update database schema
	 */
	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'groups'	=> [
					'group_edit_time'	=> ['UINT', 0],
				],
			],
		];
	}

	/**
	 * Revert database schema
	 */
	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'groups'	=> [
					'group_edit_time',
				],
			],
		];
	}
}
