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

JFormHelper::loadFieldClass('file');

class JFormFieldJtfileupload extends JFormFieldFile
{
	/**
	 * Are we in edit mode and a file was already uploaded?
	 *
	 * @var bool
	 * @since 1.0
	 */
	protected $fileExist = false;

	/**
	 * The file name of the previous uploaded file
	 *
	 * @var string
	 * @since 1.0
	 */
	protected $fileName = '';

	/**
	 * Method to get certain otherwise inaccessible properties from the form field object.
	 *
	 * @param string $name The property name for which to get the value.
	 *
	 * @return  mixed  The property value or null.
	 *
	 * @since   1.0
	 */
	public function __get($name)
	{
		switch ($name)
		{
			case 'fileExist':
				return $this->fileExist;
				break;
			case 'fileName':
				return $this->fileName;
				break;
		}

		return parent::__get($name);
	}

	/**
	 * Method to set certain otherwise inaccessible properties of the form field object.
	 *
	 * @param string $name  The property name for which to set the value.
	 * @param mixed  $value The value of the property.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public function __set($name, $value)
	{
		switch ($name)
		{
			case 'fileExist':
				$this->fileExist = (string) $value;
				break;
			case 'fileName':
				$this->fileName = (string) $value;
				break;

			default:
				parent::__set($name, $value);
		}
	}

	/**
	 * Method to attach a JForm object to the field.
	 *
	 * @param SimpleXMLElement $element     The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param mixed            $value       The form field value to validate.
	 * @param string           $group       The field name group control value. This acts as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @see     JFormField::setup()
	 * @since   1.0
	 */
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return)
		{
			$this->fileExist = (string) $this->element['fileExist'];
			$this->fileName = (string) $this->element['fileName'];
		}

		return $return;
	}

	/**
	 * Method to get the field input markup for the file field.
	 *
	 * @return string The field input markup.
	 *
	 * @since 1.0
	 */
	protected function getInput()
	{
		$renderedInput = parent::getInput();

		if ($this->fileExist)
		{
			$renderedInput .= $this->fileName;
		}

		return $renderedInput;
	}

	protected function getLayoutData()
	{
		$data = parent::getLayoutData();

		$extraData = array(
			'fileExist' => $this->fileExist,
			'fileName'  => $this->fileName
		);

		return array_merge($data, $extraData);
	}
}