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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;

JLoader::import('components.com_fields.libraries.fieldsplugin', JPATH_ADMINISTRATOR);
JLoader::registerNamespace('JtFileUpload', JPATH_PLUGINS . '/fields/jtfileupload/libraries/jtfileupload', false, false, 'psr4');
// Add form rules
JFormHelper::addRulePath(JPATH_PLUGINS . '/content/jtf/libraries/joomla/form/rules');
JLoader::registerNamespace('Joomla\\CMS\\Form\\Rule', JPATH_PLUGINS . '/fields/jtfileupload/libraries/joomla/form/rules', false, false, 'psr4');

/**
 * JtFileUpload plugin.
 *
 * @package      Joomla.Plugin
 * @subpackage   Fields.Jtfieldupload
 *
 * @since        __DEPLOY_VERSION__
 */
class plgFieldsJtfileupload extends FieldsPlugin
{
	protected $fieldDatas = array();

	/**
	 * Application object
	 *
	 * @var     CMSApplication
	 * @since   __DEPLOY_VERSION__
	 */
	protected $app;

	/**
	 * Database object
	 *
	 * @var     JDatabaseDriver
	 * @since   __DEPLOY_VERSION__
	 */
	protected $db;

	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var     boolean
	 * @since   __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Transforms the field into a DOM XML element and appends it as a child on the given parent.
	 *
	 * @param   stdClass   $field  The field.
	 * @param   DOMElement $parent The field node parent.
	 * @param   JForm      $form   The form.
	 *
	 * @return   DOMElement
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, JForm $form)
	{
		$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

		// Execute only if we had a jtfileupload
		if ($field->type != 'jtfileupload')
		{
			return $fieldNode;
		}

		// Set Fieldname
		if ($field->name != "")
		{
			$this->fieldDatas[$field->name]["fieldName"] = $field->name;
		}

		$this->fieldDatas[$field->name]["fieldId"]  = $field->id;
		$this->fieldDatas[$field->name]["required"] = $field->required;
		$this->fieldDatas[$field->name]["savePath"] = $field->fieldparams->get("savePath");
		$this->fieldDatas[$field->name]["uploaded"] = false;


		// Add enctype to formtag
		$script = "jQuery(document).ready(function($){ 
	                    $('form[name=\"adminForm\"]').attr('enctype','multipart/form-data');
	               });";

		Factory::getDocument()->addScriptDeclaration($script);

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
	 * @return   boolean
	 *
	 * @since   3.7.0
	 */
	public function onContentBeforeSave($context, $item, $isNew, $data = array())
	{
		if (!($context == "com_content.form" || $context == "com_content.article"))
			return;

		//fieldname uses jtfileupload
		if (empty($this->fieldDatas))
		{
			return false;
		}

		//Get the uploaded files object
		$files = new JtFileUpload\Input\Files;
		$file  = $files->get("jform");

		foreach ($this->fieldDatas as $fieldData)
		{
			if ($fieldData["uploaded"]) continue;

			//Get the file object for the form
			$fileSub = $file['com_fields'][$fieldData["fieldName"]];

			//No file was uploaded
			if ((int) $fileSub['error'] === 4 && !$fieldData["required"])
			{
				return true;
			}
			else if ((int) $fileSub['error'] === 4 && $fieldData["required"])
			{
				return false;
			}

			//Make the filename safe for the Web
			$filename = File::makeSafe($fileSub['name']);
			$filename = str_replace(" ", "_", $filename);

			//TODO check error in fileSub

			//Do some checks of the file
			$path_parts = pathinfo($filename);
			if (!in_array(strtolower($path_parts['extension']), array('pdf')))
			{
				JLog::add('JTFILEUPLOAD_NOT_A_PDF', JLog::ERROR);

				return false;
			}

			//TODO check filesize

			//Upload the file
			$src             = $fileSub['tmp_name'];
			$destinationPath = JPATH_SITE . "/" . $fieldData["savePath"];
			$destination     = $destinationPath . "/" . $filename;

			//Add a postfix if file already exist
			while (file_exists($destination))
			{
				$path_parts  = pathinfo($filename);
				$filename    = $path_parts['filename'] . "_" . rand() . "." . $path_parts['extension'];
				$destination = $destinationPath . "/" . $filename;
				$this->app->enqueueMessage(JText::sprintf("JTFILEUPLOAD_FILE_ALREADY_EXISTS", $filename), 'warning');
			}

			if (File::upload($src, $destination))
			{
				$this->fieldDatas[$fieldData["fieldName"]]["uploaded"]     = true;
				$this->fieldDatas[$fieldData["fieldName"]]["fileNameSafe"] = $filename;
				//success
			}
			else
			{
				JLog::add('JTFILEUPLOAD_UPLOAD_FAILED', JLog::ERROR);

				return false;
			}
		}

		return true;
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
	public function onContentAfterSave($context, $item, $isNew, $data = array())
	{
		if ($context == "com_fields.field")
		{
			$savePath           = $data["fieldparams"]["savePath"];
			$downloadProtection = $data["fieldparams"]["downloadProtection"];

			$filePath = JPATH_SITE . "/" . $savePath . "/.htaccess";

			if ($downloadProtection == 1)
			{
				if (file_exists($filePath))
					return;

				if (substr($savePath, -1) != "/")
					$savePath .= "/";
				if (substr($savePath, 0, 1) == "/")
					$savePath = substr($savePath, 1);

				$bufferPath = str_replace("/", "\/", $savePath);
				$buffer     = "RewriteRule ^" . $bufferPath . ".*$ readmedia.php [L]";
				if (!File::write($filePath, $buffer))
					$this->app->enqueueMessage(sprintf("JTFILEUPLOAD_FAILED_CREATE_HTACCESS", $filePath, $buffer), JLog::ERROR);
			}
			else
			{
				if (file_exists($filePath))
					if (!File::delete($filePath))
						$this->app->enqueueMessage(sprintf("JTFILEUPLOAD_FAILED_DELETE_HTACCESS", $filePath), JLog::ERROR);
			}
		}

		if (!($context == "com_content.form" || $context == "com_content.article"))
			return;

		if (empty($this->fieldDatas)) return true;

		$dbValues = array();
		$db       = $this->db;

		foreach ($this->fieldDatas as $fieldData)
		{
			if ($fieldData["uploaded"])
			{
				$dbValues[] = (int) $fieldData["fieldId"] . ', ' . (int) $item->id . ', ' . $db->quote($fieldData["fileNameSafe"]);
			}
		}

		if (empty($dbValues)) return true;

		$query = $db->getQuery(true);
		$query->insert('#__fields_values')
			->columns(
				array(
					$db->quoteName('field_id'),
					$db->quoteName('item_id'),
					$db->quoteName('value')
				)
			)
			->values($dbValues);
		$db->setQuery($query);
		$db->execute();

		return true;
	}
}
