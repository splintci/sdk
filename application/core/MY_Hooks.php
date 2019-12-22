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
  protected $loadedhookware = [];
  public function call_hook($which = '')
  {
    if ($which == 'post_controller_constructor' && isset($this->hooks['hookware'])) {

      if (isset($this->hooks['hookware']['all'])) $this->run_hookware('all');

      if (isset($this->hooks['hookware']['root'])) $this->run_hookware('root');

      if (isset($this->hooks['hookware']['cli'])) $this->run_hookware('cli');

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

    if (substr($hookware, 0, 1) == '+' && isset($this->hooks['hookware'][str_replace('+', '', $hookware)])) {
      if (!$this->run_hookware(str_replace('+', '', $hookware), $args ?? null)) show_error('HookWare Class: '.str_replace('+', '', $hookware).' not found.', 500);
    } else {
      show_error('HookWare Key: '.str_replace('+', '', $hookware).' not found.', 500);
    }
  }
  /**
   * [run_hookware description]
   * @date   2019-12-15
   * @param  string     $hookware [description]
   * @param  string     $args     [description]
   * @return bool                 [description]
   */
  private function run_hookware(string $hookware, ?string $args=null):bool
  {
    if (file_exists(APPPATH.'hookware/'.$this->hooks['hookware'][$hookware].'.php')) {
      class_exists($this->hooks['hookware'][$hookware], FALSE) OR require_once(APPPATH.'hookware/'.$this->hooks['hookware'][$hookware].'.php');
      $hookwareInstance = new $this->hooks['hookware'][$hookware](...array_slice(get_instance()->uri->rsegments, 2));
      return $this->eval_hookware_handle($hookwareInstance, $args);
    }
    return false;
  }
  /**
   * [eval_hookware_handle description]
   * @date   2019-12-15
   * @param  HookWare   $hookwareInstance [description]
   * @param  ?string    $args             [description]
   * @return bool                         [description]
   */
  private function eval_hookware_handle(HookWare $hookwareInstance, ?string $args):bool
  {
    if (get_instance()->input->is_cli_request() && !$hookwareInstance->shouldHandleCLI()) return true;
    if (in_array(get_instance()->router->mroute, $hookwareInstance->except())) return true;
    if (!$hookwareInstance->shouldHandle(get_instance()->router->mroute)) return true;
    $hookwareInstance->handle($args);
    return true;
  }
}
