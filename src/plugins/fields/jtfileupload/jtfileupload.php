<?php
/**
 * @package      Joomla.Plugin
 * @subpackage   Fields.JtFileUpload
 *
 * @author       Sven Schultschik
 * @copyright    (c) 2019 JoomTools.de - All rights reserved
 * @license      GNU General Public License version 3 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\Filesystem\File;
use Joomla\CMS\Uri\Uri;

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
	 * @param stdClass   $field  The field.
	 * @param DOMElement $parent The field node parent.
	 * @param JForm      $form   The form.
	 *
	 * @return   DOMElement
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onCustomFieldsPrepareDom($field, DOMElement $parent, JForm $form)
	{
		$fieldNode = parent::onCustomFieldsPrepareDom($field, $parent, $form);

		$params = JComponentHelper::getParams('com_fields');

		$params->set('upload_maxsize', 10);
		$params->set('upload_extensions', 'pdf,PDF');
		//$params->Set('ignore_extensions', ???);
		$params->set('restrict_uploads', 1);
		$params->set('upload_mime', 'application/pdf');
		//$params->set('image_extensions', ???);

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


		// Add enctype to formtag and jtfileuploadReady method
		Factory::getDocument()->addScript(Uri::root(true) ."/media/plg_fields_jtfileupload/js/jtfileuploadBasic.js", array(), array('type' => 'text/javascript'));

		if (!$fieldNode)
		{
			return $fieldNode;
		}

		$fieldNode->setAttribute('accept', '.pdf,.PDF');


		$this->fieldDatas[$field->name]["existingFileName"] = "";

		//Edit? File already exist?
		if (!empty($field->value))
		{
			$hideField = "
			function hideField() {
				var uploadField = document.getElementById('jform_com_fields_" . $field->name . "');
				uploadField.disabled = true;
				
				var checkBox = document.getElementById('jform_com_fields_" . $field->name . "_choverride');
				
				checkBox.addEventListener('click', function() {hideShowUpload();});
			};
			jtfileuploadReady(hideField);
			
			function hideShowUpload(){
				var uploadField = document.getElementById('jform_com_fields_" . $field->name . "');
				var checkBox = document.getElementById('jform_com_fields_" . $field->name . "_choverride');
				
				if (checkBox.checked == true){
				console.log('Click');
					uploadField.disabled = false;
				} else {
					uploadField.disabled = true;
				}
				
			};
			";

			Factory::getDocument()->addScriptDeclaration($hideField);

			//Stuff for the layout
			$fieldNode->setAttribute('fileExist', true);
			$fieldNode->setAttribute('fileName', $field->value);
			//echo '<h1>Field Node</h1>';var_dump($fieldNode->getAttribute('fileName'));

			//Info for saving process later
			$this->fieldDatas[$field->name]["existingFileName"] = $field->value;
		}

		return $fieldNode;
	}

	/**
	 * The save event.
	 *
	 * @param string  $context The context
	 * @param JTable  $item    The table
	 * @param boolean $isNew   Is new item
	 * @param array   $data    The validated data
	 *
	 * @return   boolean
	 *
	 * @throws Exception
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentBeforeSave($context, $item, $isNew, $data = array())
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

				$uriInstance = JUri::getInstance();
				$buffer      = "RewriteCond %{HTTP_REFERER} !^" . $uriInstance->getScheme() . "://" . $uriInstance->getHost() . ".*$ [NC]\r\n
RewriteRule ^.*$ - [NC,R=403,L]";

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

		//fieldname uses jtfileupload
		if (empty($this->fieldDatas))
		{
			return false;
		}

		//Get the uploaded files object
		$allFiles = new JtFileUpload\Input\Files;
		$files    = $allFiles->get("jform");

		foreach ($this->fieldDatas as $fieldData)
		{
			if ($fieldData["uploaded"]) continue;

			$postData = JFactory::getApplication()->input->post;

			$choveride_res = $postData->getArray(array(
				'jform' => array(
					'com_fields' => array(
						$fieldData["fieldName"] . '_choverride' => 'string'
					)
				)
			));

			$choverride = $choveride_res['jform']['com_fields'][$fieldData["fieldName"] . '_choverride'];

			// The name of the file, which where uploaded last time article was saved
			$existingFileName = $fieldData['existingFileName'];

			$overrideExistingFile = false;
			if (!is_null($choverride))
			{
				$overrideExistingFile = true;
			}
			// If a file is already uploaded and we don't want to override it, we just keep the existing values
			else if (!empty($existingFileName))
			{
				$this->fieldDatas[$fieldData["fieldName"]]["uploaded"]     = true;
				$this->fieldDatas[$fieldData["fieldName"]]["fileNameSafe"] = $existingFileName;

				return true;
			}

			//Get the file object for the form
			$file = $files['com_fields'][$fieldData["fieldName"]];

			//No file was uploaded
			if ((int) $file['error'] === 4 && !$fieldData["required"])
			{
				return true;
			}
			else if (((int) $file['error'] === 4 && $fieldData["required"])
				|| ((int) $file['error'] === 4 && $overrideExistingFile))
			{
				return false;
			}

			//Make the filename safe for the Web
			$filename = File::makeSafe($file['name']);
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
			/*
			 * if (($file['error'] == 1)
				|| ($uploadMaxSize > 0 && $file['size'] > $uploadMaxSize)
				|| ($uploadMaxFileSize > 0 && $file['size'] > $uploadMaxFileSize))
			{
				// File size exceed either 'upload_max_filesize' or 'upload_maxsize'.
				JError::raiseWarning(100, JText::_('COM_MEDIA_ERROR_WARNFILETOOLARGE'));

				return false;
			}
			 */

			//Upload the file
			$src             = $file['tmp_name'];
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

			$mediaHelper = new JHelperMedia;
			if (!$mediaHelper->canUpload($file, 'com_fields'))
			{
				return false;
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
			if ($overrideExistingFile)
			{
				//delete old file and upload new file
				$this->deleteFile($destinationPath, $existingFileName);
			}

		}

		return true;
	}

	private function deleteFile($folder, $fileName)
	{
		if (!File::delete($folder . "/" . $fileName))
			$this->app->enqueueMessage(JText::sprintf("JTFILEUPLOAD_DELETE_FILE_FAILED", $fileName), 'error');
	}

	/**
	 * The save event.
	 *
	 * @param string  $context The context
	 * @param JTable  $item    The table
	 * @param boolean $isNew   Is new item
	 * @param array   $data    The validated data
	 *
	 * @return  boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentAfterSave($context, $item, $isNew, $data = array())
	{
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
