<?php
declare(strict_types=1);

defined('BASEPATH') OR exit('No direct script access allowed');

function hookware(string $class, string $path='hooks', $params=''):array
{
  return [
    'class'    => $class,
    'function' => 'startup',
    'filename' => $class . '.php',
    'filepath' => $path,
    'params'   => $params
  ];
}

class MY_Hooks extends CI_Hooks
{
  protected $loadedHookwares = [];
  public function call_hook($which = '')
  {
    if ($which == 'post_controller_constructor' && isset($this->hooks['hookwares'])) {
      if (count(get_instance()->router->hwgroups) > 0) {
        foreach (get_instance()->router->hwgroups as $groups => $value) {
          if (!$value) break;
          $groups = preg_replace('/(@group{|})/', '', $groups);
          $groups = explode(',', $groups);
          foreach ($groups as $hookware) $this->process_hookware($hookware);
          if (get_instance()->router->hookware) {
            foreach (explode(',', get_instance()->router->hookware) as $hookware) {
              $this->process_hookware($hookware);
            }
          }
        }
      }
    }
    return parent::call_hook($which);
  }
  /**
   * [process_hookware description]
   * @date   2019-12-15
   * @param  string     $hookware [description]
   * @return [type]               [description]
   */
  private function process_hookware(string $hookware)
  {
    if (strpos($hookware, ':') !== false) {
      $args = explode('~', substr($hookware, strpos($hookware, ':') + 1));
      $hookware = explode(':', $hookware)[0];
    }

    if (substr($hookware, 0, 1) == '+' && isset($this->hooks['hookwares'][str_replace('+', '', $hookware)])) {
      if (file_exists(APPPATH.'hookwares/'.$this->hooks['hookwares'][str_replace('+', '', $hookware)].'.php')) {
        class_exists($this->hooks['hookwares'][str_replace('+', '', $hookware)], FALSE) OR require_once(APPPATH.'hookwares/'.$this->hooks['hookwares'][str_replace('+', '', $hookware)].'.php');
        (new $this->hooks['hookwares'][str_replace('+', '', $hookware)](...array_slice(get_instance()->uri->rsegments, 2)))->handle($args ?? null);
      } else {
        throw new Exception('HookWare Class: '.str_replace('+', '', $hookware).' not found.');
      }
    } else {
      throw new Exception('HookWare Key: '.str_replace('+', '', $hookware).' not found.');
    }
  }
}
