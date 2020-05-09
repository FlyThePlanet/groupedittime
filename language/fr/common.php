<?php
/**
 *
 * Group Edit Time extension for the phpBB Forum Software package
 *
 * @copyright (c) 2020, Kailey Truscott, https://www.layer-3.org/
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

/**
 * DO NOT CHANGE
 */
if (!defined('IN_PHPBB'))
{
	exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = [];
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, [
	'GROUP_ENABLE_EDIT_TIME'			=> 'Activer une gestion du temps spécifique pour modifier les messages',
	'GROUP_ENABLE_EDIT_TIME_EXPLAIN'	=> 'Permet de gérer une durée spécifique pour les membres du groupe. Ce paramètre s\'imposera sur celui paramétré pour les utilisateurs ordinaires du forum.',
	'GROUP_EDIT_TIME'					=> 'Temps maximum pour modifier les messages',
	'GROUP_EDIT_TIME_EXPLAIN'			=> 'Fixe une durée maximum pour modifier un message, spécifique pour les membres du groupe. Réglez cette valeur sur "0" si vous souhaitez donner un temps illimité aux membres du groupe.',
]);
