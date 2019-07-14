<?php

namespace JtFileUpload\MediaHelper;

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Component\ComponentHelper;

/**
 * Media helper class
 *
 * @since  3.2
 */
class JtMediaHelper
{    /**
	 * Checks if the file can be uploaded
	 *
	 * @param   array   $file       File information
	 * @param   string  $component  The option name for the component storing the parameters
	 *
	 * @return  boolean
	 *
	 * @since   3.2
	 */
	public function canUpload($file, $component = 'com_media')
	{
		$app    = \JFactory::getApplication();
		$params = ComponentHelper::getParams($component);

		if (empty($file['name']))
		{
			$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_UPLOAD_INPUT'), 'error');

			return false;
		}

		jimport('joomla.filesystem.file');

		if (str_replace(' ', '', $file['name']) !== $file['name'] || $file['name'] !== \JFile::makeSafe($file['name']))
		{
			$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILENAME'), 'error');

			return false;
		}

		$filetypes = explode('.', $file['name']);

		if (count($filetypes) < 2)
		{
			// There seems to be no extension
			$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETYPE'), 'error');

			return false;
		}

		array_shift($filetypes);

		// Media file names should never have executable extensions buried in them.
		$executable = array(
			'php', 'js', 'exe', 'phtml', 'java', 'perl', 'py', 'asp', 'dll', 'go', 'ade', 'adp', 'bat', 'chm', 'cmd', 'com', 'cpl', 'hta', 'ins', 'isp',
			'jse', 'lib', 'mde', 'msc', 'msp', 'mst', 'pif', 'scr', 'sct', 'shb', 'sys', 'vb', 'vbe', 'vbs', 'vxd', 'wsc', 'wsf', 'wsh',
		);

		$check = array_intersect($filetypes, $executable);

		if (!empty($check))
		{
			$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETYPE'), 'error');

			return false;
		}

		$filetype  = array_pop($filetypes);
		$allowable = array_map('trim', explode(',', $params->get('upload_extensions')));
		$ignored   = array_map('trim', explode(',', $params->get('ignore_extensions')));

		if ($filetype == '' || $filetype == false || (!in_array($filetype, $allowable) && !in_array($filetype, $ignored)))
		{
			$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETYPE'), 'error');

			return false;
		}

		$maxSize = (int) ($params->get('upload_maxsize', 0) * 1024 * 1024);

		if ($maxSize > 0 && (int) $file['size'] > $maxSize)
		{
			$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETOOLARGE'), 'error');

			return false;
		}

		if ($params->get('restrict_uploads', 1))
		{
			$images = array_map('trim', explode(',', $params->get('image_extensions')));

			if (in_array($filetype, $images))
			{
				// If tmp_name is empty, then the file was bigger than the PHP limit
				if (!empty($file['tmp_name']))
				{
					// Get the mime type this is an image file
					$mime = $this->getMimeType($file['tmp_name'], true);

					// Did we get anything useful?
					if ($mime != false)
					{
						$result = $this->checkMimeType($mime, $component);

						// If the mime type is not allowed we don't upload it and show the mime code error to the user
						if ($result === false)
						{
							$app->enqueueMessage(\JText::sprintf('JLIB_MEDIA_ERROR_WARNINVALID_MIMETYPE', $mime), 'error');

							return false;
						}
					}
					// We can't detect the mime type so it looks like an invalid image
					else
					{
						$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNINVALID_IMG'), 'error');

						return false;
					}
				}
				else
				{
					$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNFILETOOLARGE'), 'error');

					return false;
				}
			}
			elseif (!in_array($filetype, $ignored))
			{
				// Get the mime type this is not an image file
				$mime = $this->getMimeType($file['tmp_name'], false);

				// Did we get anything useful?
				if ($mime != false)
				{
					$result = $this->checkMimeType($mime, $component);

					// If the mime type is not allowed we don't upload it and show the mime code error to the user
					if ($result === false)
					{
						$app->enqueueMessage(\JText::sprintf('JLIB_MEDIA_ERROR_WARNINVALID_MIMETYPE', $mime), 'error');

						return false;
					}
				}
				// We can't detect the mime type so it looks like an invalid file
				else
				{
					$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNINVALID_MIME'), 'error');

					return false;
				}

				if (!\JFactory::getUser()->authorise('core.edit.value', $component))
				{
					$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNNOTADMIN'), 'error');

					return false;
				}
			}
		}

		$xss_check = file_get_contents($file['tmp_name'], false, null, -1, 256);

		$html_tags = array(
			'abbr', 'acronym', 'address', 'applet', 'area', 'audioscope', 'base', 'basefont', 'bdo', 'bgsound', 'big', 'blackface', 'blink',
			'blockquote', 'body', 'bq', 'br', 'button', 'caption', 'center', 'cite', 'code', 'col', 'colgroup', 'comment', 'custom', 'dd', 'del',
			'dfn', 'dir', 'div', 'dl', 'dt', 'em', 'embed', 'fieldset', 'fn', 'font', 'form', 'frame', 'frameset', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
			'head', 'hr', 'html', 'iframe', 'ilayer', 'img', 'input', 'ins', 'isindex', 'keygen', 'kbd', 'label', 'layer', 'legend', 'li', 'limittext',
			'link', 'listing', 'map', 'marquee', 'menu', 'meta', 'multicol', 'nobr', 'noembed', 'noframes', 'noscript', 'nosmartquotes', 'object',
			'ol', 'optgroup', 'option', 'param', 'plaintext', 'pre', 'rt', 'ruby', 's', 'samp', 'script', 'select', 'server', 'shadow', 'sidebar',
			'small', 'spacer', 'span', 'strike', 'strong', 'style', 'sub', 'sup', 'table', 'tbody', 'td', 'textarea', 'tfoot', 'th', 'thead', 'title',
			'tr', 'tt', 'ul', 'var', 'wbr', 'xml', 'xmp', '!DOCTYPE', '!--',
		);

		foreach ($html_tags as $tag)
		{
			// A tag is '<tagname ', so we need to add < and a space or '<tagname>'
			if (stripos($xss_check, '<' . $tag . ' ') !== false || stripos($xss_check, '<' . $tag . '>') !== false)
			{
				$app->enqueueMessage(\JText::_('JLIB_MEDIA_ERROR_WARNIEXSS'), 'error');

				return false;
			}
		}

		return true;
    }
}