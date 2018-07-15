<?php
/**
 * @package    [PACKAGE_NAME]
 *
 * @author     [AUTHOR] <[AUTHOR_EMAIL]>
 * @copyright  [COPYRIGHT]
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 * @link       [AUTHOR_URL]
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;

JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);
JFormHelper::addRulePath(JPATH_PLUGINS . '/fields/foo/rules');
//JForm::getInstance()->validate($submitedValues);

/**
 * Foo plugin.
 *
 * @package  [PACKAGE_NAME]
 * @since    1.0
 */
class plgFieldsFoo extends FieldsPlugin
{
	/**
	 * Application object
	 *
	 * @var    CMSApplication
	 * @since  1.0
	 */
	protected $app;

	/**
	 * Database object
	 *
	 * @var    JDatabaseDriver
	 * @since  1.0
	 */
	protected $db;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Transforms the field into a DOM XML element and appends it as a child on the given parent.
	 *
	 * @param   stdClass   $field  The field.
	 * @param   DOMElement $parent The field node parent.
	 * @param   JForm      $form   The form.
	 *
	 * @return  DOMElement
	 *
	 * @since   1.0
	 */
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, JForm $form)
	{
	    $script = "jQuery(document).ready(function(){jQuery('form#adminForm').attr('enctype','multipart/form-data');});";
        JFactory::getDocument()->addScriptDeclaration($script);

        $fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);
        if (!$fieldNode)
        {
            return $fieldNode;
        }

        $fieldNode->setAttribute('accept', '.pdf,.PDF');

        return $fieldNode;
	}

	/**
	 * The save event.
	 *
	 * @param   string  $context The context
	 * @param   JTable  $item    The table
	 * @param   boolean $isNew   Is new item
	 * @param   array   $data    The validated data
	 *
	 * @return  boolean
	 *
	 * @since   3.7.0
	 */
	public function onContentBeforeSave($context, $item, $isNew, $data = array())
	{
        $input = $this->app->input;
        $files = $input->files;

     print_r($this->params->get('type'));
     echo '<p>name</p>';
        //$files->get
        if (!empty($_FILES))
        {
            //print_r($_FILES);



            echo '<p>';
        //    $jinput          = new \Jtf\Input\Files;
        //    $submitedFiles     = $jinput->get($formTheme);
         //   $submitedValues = array_merge_recursive($submitedValues, $submitedFiles);
        }else{echo '<p>empty</p>';};
       // $this->getForm()->bind($submitedValues);
        //$this->setFieldValidates();
        //$valid = $this->getForm()->validate($submitedValues);


print_r($context);

echo "<p>";

print_r($item);
        echo "<p>";

        print_r($isNew);
        echo "<p>";
print_r($data);
//die();
	}
}
