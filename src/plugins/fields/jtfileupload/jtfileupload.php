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

use Joomla\Filesystem\File;
use Joomla\CMS\HTML\HTMLHelper;

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
	 * @var     JApplication
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
	 * @var     JLanguage
	 * @since   __DEPLOY_VERSION__
	 */
	protected $autoloadLanguage = true;

	/**
	 * Transforms the field into a DOM XML element and appends it as a child on the given parent.
	 *
	 * @param   stdClass    $field  The field.
	 * @param   DOMElement  $parent The field node parent.
	 * @param   JForm       $form   The form.
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
		HTMLHelper::_('script', 'plg_fields_jtfileupload/jtfileuploadBasic.min.js', array('version' => 'auto', 'relative' => true));

		if (!$fieldNode)
		{
			return $fieldNode;
		}

		$fieldNode->setAttribute('accept', '.pdf,.PDF');

		$this->fieldDatas[$field->name]["existingFileName"] = "";

		// Edit? File already exist?
		if (!empty($field->value))
		{
			HTMLHelper::_('script', 'plg_fields_jtfileupload/jtfileuploadEdit.min.js', array('version' => 'auto', 'relative' => true));

			// Stuff for the layout
			$fieldNode->setAttribute('fileExist', true);
			$fieldNode->setAttribute('fileName', $field->value);

			// Info for saving process later
			$this->fieldDatas[$field->name]["existingFileName"] = $field->value;
		}

		return $fieldNode;
	}

	/**
	 * The save event.
	 *
	 * @param   string   $context  The context
	 * @param   JTable   $item     The table
	 * @param   boolean  $isNew    Is new item
	 * @param   array    $data     The validated data
	 *
	 * @return   boolean
	 * @throws   Exception
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentBeforeSave($context, $item, $isNew, $data = array())
	{
		if ($context == "com_fields.field")
		{
			$savePath           = $data["fieldparams"]["savePath"];
			$downloadProtection = $data["fieldparams"]["downloadProtection"];

			$filePath = JPATH_SITE . "/" . $savePath . "/.htaccess";

			if ($downloadProtection == 1 && !file_exists($filePath))
			{
				$buffer = [];

				// Start RewriteEngine
				$buffer[] = 'RewriteEngine On';
				$buffer[] = '';

				// Define scheme
				$buffer[] = 'RewriteCond %{HTTPS} =on';
				$buffer[] = 'RewriteRule ^ - [env=proto:https]';
				$buffer[] = 'RewriteCond %{HTTPS} !=on';
				$buffer[] = 'RewriteRule ^ - [env=proto:http]';
				$buffer[] = '';

				// Check referer
				$buffer[] = 'RewriteCond %{HTTP_REFERER} !^%{ENV:PROTO}://%{HTTP_HOST}.*$ [NC]';
				$buffer[] = 'RewriteRule ^.*$ - [NC,R=403,L]';

				$htaccess = implode("\r\n", $buffer);

				if (!File::write($filePath, $htaccess))
					$this->app->enqueueMessage(sprintf("JTFILEUPLOAD_FAILED_CREATE_HTACCESS", $filePath, $htaccess), JLog::ERROR);
			}

			if ($downloadProtection == 0 && file_exists($filePath))
			{
				if (!File::delete($filePath))
				{
					$this->app->enqueueMessage(sprintf("JTFILEUPLOAD_FAILED_DELETE_HTACCESS", $filePath), JLog::ERROR);
				}
			}
		}

		if (!($context == "com_content.form" || $context == "com_content.article"))
		{
			return true;
		}

		// Fieldname uses jtfileupload
		if (empty($this->fieldDatas))
		{
			return false;
		}

		// Get the uploaded files object
		$allFiles = new JtFileUpload\Input\Files;
		$files    = $allFiles->get("jform");

		foreach ($this->fieldDatas as $fieldData)
		{
			if ($fieldData["uploaded"])
			{
				continue;
			}

			$postData = $this->app->input->post;

			$choveride_res = $postData->getArray(array(
				'jform' => array(
					'com_fields' => array(
						$fieldData["fieldName"] . '_choverride' => 'string',
					),
				),
			));

			$choverride = $choveride_res['jform']['com_fields'][$fieldData["fieldName"] . '_choverride'];

			// The name of the file, which where uploaded last time article was saved
			$existingFileName = $fieldData['existingFileName'];

			if (is_null($choverride) && !empty($existingFileName))
			{
				// If a file is already uploaded and we don't want to override it, we just keep the existing values
				$this->fieldDatas[$fieldData["fieldName"]]["uploaded"]     = true;
				$this->fieldDatas[$fieldData["fieldName"]]["fileNameSafe"] = $existingFileName;

				return true;
			}

			$overrideExistingFile = false;

			if (!is_null($choverride))
			{
				$overrideExistingFile = true;
			}

			// Get the file object for the form
			$file = $files['com_fields'][$fieldData["fieldName"]];

			// No file was uploaded
			if ((int) $file['error'] === 4 && !$fieldData["required"])
			{
				return true;
			}

			if (((int) $file['error'] === 4 && $fieldData["required"])
				|| ((int) $file['error'] === 4 && $overrideExistingFile))
			{
				return false;
			}

			// Make the filename safe for the Web
			$filename = File::makeSafe($file['name']);
			$filename = str_replace(" ", "_", $filename);

			// TODO check error in fileSub

			// Do some checks of the file
			$path_parts = pathinfo($filename);

			if (!in_array(strtolower($path_parts['extension']), array('pdf')))
			{
				JLog::add('JTFILEUPLOAD_NOT_A_PDF', JLog::ERROR);

				return false;
			}

			// TODO check filesize
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

			// Upload the file
			$src             = $file['tmp_name'];
			$destinationPath = JPATH_SITE . "/" . $fieldData["savePath"];
			$destination     = $destinationPath . "/" . $filename;

			// Add a postfix if file already exist
			while (file_exists($destination))
			{
				$path_parts  = pathinfo($filename);
				$filename    = $path_parts['filename'] . "_" . rand() . "." . $path_parts['extension'];
				$destination = $destinationPath . "/" . $filename;

				$this->app->enqueueMessage(JText::sprintf("JTFILEUPLOAD_FILE_ALREADY_EXISTS", $filename), 'warning');
			}

			#$mediaHelper = new JHelperMedia;
			
			$mediaHelper = new JtMediaHelper;

			if (!$mediaHelper->canUpload($file, 'com_fields'))
			{
				return false;
			}

			if (!File::upload($src, $destination))
			{
				// Error
				JLog::add('JTFILEUPLOAD_UPLOAD_FAILED', JLog::ERROR);

				return false;
			}

			$this->fieldDatas[$fieldData["fieldName"]]["uploaded"]     = true;
			$this->fieldDatas[$fieldData["fieldName"]]["fileNameSafe"] = $filename;

			if ($overrideExistingFile)
			{
				// Delete old file and upload new file
				$this->deleteFile($destinationPath, $existingFileName);
			}
		}

		return true;
	}

	private function deleteFile($folder, $fileName)
	{
		if (!File::delete($folder . "/" . $fileName))
		{
			$this->app->enqueueMessage(JText::sprintf("JTFILEUPLOAD_DELETE_FILE_FAILED", $fileName), 'error');
		}
	}

	/**
	 * The save event.
	 *
	 * @param   string   $context  The context
	 * @param   JTable   $item     The table
	 * @param   boolean  $isNew    Is new item
	 * @param   array    $data     The validated data
	 *
	 * @return   boolean
	 *
	 * @since   __DEPLOY_VERSION__
	 */
	public function onContentAfterSave($context, $item, $isNew, $data = array())
	{
		if (!($context == "com_content.form" || $context == "com_content.article"))
		{
			return true;
		}

		if (empty($this->fieldDatas))
		{
			return true;
		}

		$dbValues = array();

		foreach ($this->fieldDatas as $fieldData)
		{
			if ($fieldData["uploaded"])
			{
				$dbValues[] = (int) $fieldData["fieldId"] . ', ' . (int) $item->id . ', ' . $this->db->quote($fieldData["fileNameSafe"]);
			}
		}

		if (empty($dbValues)) return true;

		$query = $this->db->getQuery(true);
		$query->insert('#__fields_values')
			->columns(
				array(
					$this->db->quoteName('field_id'),
					$this->db->quoteName('item_id'),
					$this->db->quoteName('value'),
				)
			)
			->values($dbValues);
		$this->db->setQuery($query);
		$this->db->execute();

		return true;
	}
}
