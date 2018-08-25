<?php
/**
 * @package          Joomla.Plugin
 * @subpackage       Fields.JtFileUpload
 *
 * @author           Sven Schultschik
 * @copyright    (c) 2018 JoomTools.de - All rights reserved
 * @license          GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

$fieldValue = $field->value;
$savePath   = $field->fieldparams->get("savePath");

if ($fieldValue === '' || $fieldValue === null)
{
	return;
}

$path_parts = pathinfo($fieldValue);
$fileType   = $path_parts['extension'];
$type       = "";
if (strtolower($fileType) == 'pdf')
{
	$type = 'type="application/pdf"';
}

$displayName = str_replace("_", " ", $path_parts['filename']);

echo '<a href="' . JUri::base() . trim($savePath, "/") . "/" . $fieldValue . '" ' . $type . ' rel="nofollow">' . $displayName . '</a>';