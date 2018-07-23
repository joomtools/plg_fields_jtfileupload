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

if ($fieldValue === '' || $fieldValue === null)
{
	return;
}

$fileType = JFile::getExt($fieldValue);
$type     = "";
if (strtolower($fileType) == 'pdf')
{
	$type = 'type="application/pdf"';
}

$displayName = str_replace("_", " ", JFile::stripExt($fieldValue));

echo '<a href="' . JUri::base() . "/images/jtfileupload/" . $fieldValue . '" ' . $type . ' rel="nofollow">' . $displayName . '</a>';