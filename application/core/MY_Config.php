<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Config extends CI_Config
{
  /**
   * [public description]
   * @var [type]
   */
  public $events;

  /**
   * [__construct description]
   * @date 2019-12-22
   */
  function __construct() {
    parent::__construct();
    $this->events = $this->get_events();
  }

  /**
   * [get_events description]
   * @date   2019-12-22
   * @return array      [description]
   */
  private function get_events():array
  {
    $events = [];

		if (empty($events))	{
			$filePath = APPPATH.'config/events.php';
			if (file_exists($filePath))	require($filePath);

      // Is the events file in the environment folder?
			if (file_exists($filePath = APPPATH.'config/'.ENVIRONMENT.'/events.php')) {
        require($filePath);
			}

			// Does the $events array exist in the file?
			if (!isset($events) OR !is_array($events)) {
        show_error(503, 'Your config file does not appear to be formatted correctly.');
			}
    }

    return $events;
  }
}
