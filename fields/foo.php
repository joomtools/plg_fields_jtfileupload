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

class JFormFieldFoo extends JFormFieldFile
{
    protected $type = 'Foo';

    protected $accept = '.pdf|.PDF';
}