<?php
/**
 * @package      Joomla.Plugin
 * @subpackage   Fields.JtFileUpload
 *
 * @author       Sven Schultschik
 * @copyright    (c) 2018 JoomTools.de - All rights reserved
 * @license      GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

$fieldValue = $field->value;

echo 'HELLO';

if ($fieldValue === '' || $fieldValue === null)
{
	return;
}
