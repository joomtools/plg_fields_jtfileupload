<?php
/**
 * @package     ${NAMESPACE}
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

defined('_JEXEC') or die;

JFormHelper::loadFieldClass('file');

JFormHelper::addRulePath(JPATH_PLUGINS . '/fields/foo/rules');
//JForm::getInstance()->validate($submitedValues);

class JFormFieldFoo extends JFormFieldFile
{

}