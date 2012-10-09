<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 *@name model\form.php
 *@packages Model/Form
 *@subpackage Form
 *@category Form
 *@author Andrew Scherbakov
 *@version 1.0
 *@copyright created  2012 - 06 Jun - 07 Thu
 *
 *
 */

class Model_Form extends Kohana_Model{
  /**
   *
   * Enter description here ...
   * @var Model
   */
  private $_model = NULL;
  /**
   *
   * Enter description here ...
   * @var array
   */
  private $_post = array();
  /**
   *
   * Enter description here ...
   * @throws HTTP_Exception_500
   */
  private function init(){
    $this->_post = $_POST;
    if ( $modelname = $this->_post['serialized']){
      $this->_model = Model::factory($modelname);
    }
    else throw new HTTP_Exception_500();

  }
  /**
   *
   * Enter description here ...
   */
  function __construct(){
    $this->init();
  }
  /**
   *
   * Enter description here ...
   */
  function create(){
    $meta = $this->_model->meta();
    foreach ($meta as $key => $field){
      if ( isset( $this->_post[$key]))  $meta->$key = $this->_post[$key];
    }
    $meta->save();
  }
  /**
   *@throws HTTP_Exception_500
   */
  function update(){
    $meta = $this->_model->meta();
    $pkName = $meta->getPk();
    if ( !isset( $this->_post[$pkName])) throw new HTTP_Exception_500();
    $meta->get( $pkName, $this->_post[$pkName]);
    foreach ( $meta as $key => $field){
      if ( isset( $this->_post[$key]))  $meta->$key = $this->_post[$key];
    }
    $meta->save();
  }
  /**
   *
   * Enter description here ...
   */
  function delete(){
    $meta = $this->_model->meta();
    $pkName = $meta->getPk();
    if ( !isset( $this->_post[$pkName])) throw new HTTP_Exception_500();
    $meta->get( $pkName, $this->_post[$pkName]);
    $meta->delete();
  }
}