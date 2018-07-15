<?php
/**
 * @package     Jorobo
 *
 * @copyright   Copyright (C) 2005 - 2015 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

use Joomla\Jorobo\Tasks\loadTasks;

if (!defined('JPATH_BASE'))
{
	define('JPATH_BASE', __DIR__);
}

// PSR-4 Autoload by composer
require JPATH_BASE . '/vendor/autoload.php';

class RoboFile extends \Robo\Tasks
{
	use loadTasks;

	/**
	 * Initialize Robo
	 */
	public function __construct()
	{
		$this->stopOnFail(false);
	}

	/**
	 * Build the joomla extension package
	 *
	 * @param   array  $params  Additional params
	 *
	 * @return  void
	 */
	public function build($params = ['dev' => false])
	{
		if (!file_exists('jorobo.ini'))
		{
			$this->_copy('jorobo.dist.ini', 'jorobo.ini');
		}

		(new \Joomla\Jorobo\Tasks\Build($params))->run();
	}

	/**
	 * Update copyright headers for this project. (Set the text up in the jorobo.ini)
	 *
	 * @return  void
	 */
	public function headers()
	{
		if (!file_exists('jorobo.ini'))
		{
			$this->_copy('jorobo.dist.ini', 'jorobo.ini');
		}

		(new \Joomla\Jorobo\Tasks\CopyrightHeader())->run();
	}

	/**
	 * Map into Joomla installation.
	 *
	 * @param   String  $target  The target joomla instance
	 *
	 * @return  void
	 */
	public function map($target)
	{
		if (!file_exists('jorobo.ini'))
		{
			$this->_copy('jorobo.dist.ini', 'jorobo.ini');
		}

		(new \Joomla\Jorobo\Tasks\Map($target))->run();
	}

	/**
	 * Bump Version placeholder __DEPLOY_VERSION__ in this project. (Set the version up in the jorobo.ini)
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function bump()
	{
		if (!file_exists('jorobo.ini'))
		{
			$this->_copy('jorobo.dist.ini', 'jorobo.ini');
		}

		(new \Joomla\Jorobo\Tasks\BumpVersion())->run();
	}
}
