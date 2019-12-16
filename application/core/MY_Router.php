<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Router extends CI_Router
{
  public $hwgroups = [];
  public $hookware;
  public $mroute;

  /**
   * [_parse_routes description]
   * @date   2019-12-14
   * @return [type]     [description]
   */
  protected function _parse_routes()
  {
    $uri = implode('/', $this->uri->segments);
    $http_verb = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : 'cli';

    foreach ($this->routes as $key => $value) {
      if (preg_match('/@group{[\w,\+.\-~:_]+}/', $key)) {
        $this->hwgroups[$key] = $value;
        unset($this->routes[$key]);
        continue;
      }

      if ($value == '@groupend') {
        array_pop($this->hwgroups);
        unset($this->routes[$key]);
        continue;
      }

      if (is_array($value)) {
        if (substr(array_keys($value)[0], 0, 1) == '+') {
          $this->hookware = array_keys($value)[0];
          $this->routes[$key] = array_values($value)[0];
        }

        $value = array_change_key_case($value, CASE_LOWER);
				if (isset($value[$http_verb])) {
          if (is_array($value[$http_verb])) {
            $this->hookware = array_keys($value[$http_verb])[0];
            $this->routes[$key][$http_verb] = array_values($value[$http_verb])[0];
          }
				}
      }

      if (preg_match('#^'.str_replace(array(':any', ':num'), array('[^/]+', '[0-9]+'), $key).'$#', $uri)) {
        $this->mroute = str_replace(array('[^/]+', '[0-9]+'), array(':any', ':num'), $key);
        break;
      }
    }

    parent::_parse_routes();
  }
  /**
   * [_set_default_controller description]
   * @date 2019-12-14
   */
  protected function _set_default_controller()
  {
    $this->hookware = 'root';
    parent::_set_default_controller();
  }
}
