<?php  if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * [MY_Loader description]
 */
class MY_Loader extends CI_Loader {

  /**
   * [splint description]
   * @param  [type] $splint   [description]
   * @param  array  $autoload [description]
   * @return [type]           [description]
   */
  function splint($splint, $autoload = array(), $params = null, $alias = null, $returnView = false) {
    $splint = trim($splint, '/');
    if (!is_dir(APPPATH . "splints/$splint/")) {
      show_error("Cannot find splint '$splint'");
      return false;
    }
    if (is_string($autoload)) {
      if (substr($autoload, 0, 1) == "+") {
        $this->library("../splints/$splint/libraries/" . substr($autoload, 1), $params, $alias);
      } elseif (substr($autoload, 0, 1) == "*") {
        $this->model("../splints/$splint/models/" . substr($autoload, 1));
      } elseif (substr($autoload, 0, 1) == "-") {
        $this->view("../splints/$splint/views/" . substr($autoload, 1), $params, $returnView);
      } elseif (substr($autoload, 0, 1) == "@") {
        $this->config("../splints/$splint/config/" . substr($autoload, 1));
      } elseif (substr($autoload, 0, 1) == "%") {
        $this->helper("../splints/$splint/helpers/" . substr($autoload, 1));
      }
      return true;
    }
    foreach ($autoload as $type => $arg) {
      if ($type == 'library') {
        if (is_array($arg)) {
          $this->library("../splints/$splint/libraries/" . $arg[0], (isset($arg[1]) ? $arg[1] : null), (isset($arg[2]) ? $arg[2] : $arg[0]));
        } else {
          $this->library("../splints/$splint/libraries/$arg");
        }
      } elseif ($type == 'model') {
        if (is_array($arg)) {
          $this->model("../splints/$splint/models/" . $arg[0], (isset($arg[1]) ? $arg[1] : null));
        } else {
          $this->model("../splints/$splint/models/$arg");
        }
      } elseif ($type == 'config') {
        $this->config("../splints/$splint/config/$arg");
      } elseif ($type == 'helper') {
        $this->helper("../splints/$splint/helpers/$arg");
      } elseif($type == 'view') {
        $this->view("../splints/$splint/views/$arg");
      } else {
        show_error ("Could not autoload object of type '$type' ($arg) for splint $splint");
      }
      return true;
    }
  }
  /**
   * [bind description]
   * @param  [type] $splint [description]
   * @param  [type] $bind   [description]
   * @return [type]         [description]
   */
  function bind($splint, &$bind) {
    $bind = new Splint($splint);
  }
}

/**
 * [Splint description]
 */
class Splint {

  /**
   * [private description]
   * @var [type]
   */
  private $ci;
  /**
   * [private description]
   * @var [type]
   */
  private $splint;

  /**
   * [$load description]
   * @var [type]
   */
  var $load;

  /**
   * [protected description]
   * @var [type]
   */
  protected $dynamic_fields;

  function __construct($splint) {
    $this->ci =& get_instance();
    $this->splint = $splint;
    $this->load =& $this;
  }
  /**
   * [library description]
   * @param  [type] $lib    [description]
   * @param  [type] $params [description]
   * @param  [type] $alias  [description]
   * @return [type]         [description]
   */
  function library($lib, $params=null, $alias=null, $bind=false) {
    $this->ci->load->library("../splints/$this->splint/libraries/" . $lib, $params, $alias);
    if ($bind) {
      if ($alias != null && is_string($alias)) {
        $this->{$alias} =& $this->ci->{$alias};
      } else {
        $this->{strtolower($lib)} =& $this->ci->{strtolower($lib)};
      }
    }
  }
  /**
   * [view description]
   * @param  [type]  $view   [description]
   * @param  [type]  $params [description]
   * @param  boolean $return [description]
   * @return [type]          [description]
   */
  function view($view, $params=null, $return=false) {
    $this->ci->load->view("../splints/$this->splint/views/" . $view, $params, $return);
  }
  /**
   * [model description]
   * @param  [type] $model [description]
   * @param  [type] $alias [description]
   * @return [type]        [description]
   */
  function model($model, $alias=null) {
      $this->ci->load->model("../splints/$this->splint/models/" . $model, $alias);
  }
  /**
   * [helper description]
   * @param  [type] $helper [description]
   * @return [type]         [description]
   */
  function helper($helper) {
    $this->ci->load->helper("../splints/$this->splint/helpers/$helper");
  }
  /**
   * [config description]
   * @param  [type] $config [description]
   * @return [type]         [description]
   */
  function config($config) {
    $this->c0->load->config("../splints/$this->splint/config/$config");
  }
}
