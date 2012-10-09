<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 *@name model.php
 *@packages Model
 *@subpackage Model
 *@category Model
 *@author Andrew Scherbakov
 *@version 1.0
 *@copyright created  2012 - 06 Jun - 07 Thu
 *
 *
 */



require MODPATH . 'xpdo/classes/xpdo/xpdo.class.php';
require MODPATH . 'xpdo/classes/xpdo/cache/xpdocachemanager.class.php';
require MODPATH . 'xpdo/classes/xpdo/om/xpdoobject.class.php';

abstract class Model extends Kohana_Model {
  /**
   *
   * Enter description here ...
   * @var xPDO
   */
  private $_db = NULL;
  /**
   *
   * Enter description here ...
   * @var array
   */
  protected $_hasColumns = array();
  /**
   *
   * Enter description here ...
   * @var string
   */
  protected $_class = '';
  /**
   *
   * Enter description here ...
   * @var Meta|null
   */
  protected $_meta = NULL;
  /**
   *
   * Enter description here ...
   * @var boolean
   */
  protected $_xpdo_cache = TRUE;
  /**
   *
   * Enter description here ...
   * @var string
   */
  protected $_package = NULL;
  /**
   *
   * Enter description here ...
   * @param xPDOObject $obj
   * @param array $tplOptions
   */
  public function _metaResponse( xPDOObject $obj, $tplOptions){
    $rows = $this->_meta->getMetaValue($obj);
    $data = array();
    foreach ( $rows as $item){
      $data []= $item;
    }
    return $data;
  }
  /**
   *
   * Enter description here ...
   * @param boolean $exp
   * @param string $msg
   * @throws Exception
   */
  private function ensure( $exp, $msg){
    if ( $exp) throw new Database_Exception($msg);
  }
  /**
   *
   * Enter description here ...
   */
  function __construct( ){
    $array = array();
    $this->_db = DB::instance();
    $config = Kohana::$config->load('xpdo');
    $this->_db->addPackage( $this->_package, $config['class']['modelpath']);
    $array [$this->_class]= $this->_hasColumns;
    $this->_meta = new Meta($array, '', NULL, self::$_classname);
  }
  /**
   *
   * Enter description here ...
   * @return Meta
   */
  function meta(){
    return $this->_meta;
  }
  /**
   * Creates an new xPDOQuery for a specified xPDOObject class.
   *
   * @param string $class The class to create the xPDOQuery for.
   * @param mixed $criteria Any valid xPDO criteria expression.
   * @param boolean|integer $cacheFlag Indicates if the result should be cached
   * and optionally for how many seconds (if passed an integer greater than 0).
   * @return xPDOQuery The resulting xPDOQuery instance or false if unsuccessful.
   */
  function newQuery( $criteria= NULL, $alias =''){
    $query = $this->_db->newQuery($this->_class, $criteria, $this->_xpdo_cache);
    $query->bindGraph( $this->_meta->getGraph());
    if ( !empty($alias))  $query->setClassAlias( $alias);
    return $query;
  }
  /**
   * Creates an new xPDOQuery for a specified xPDOObject class.
   *
   * @param string $class The class to create the xPDOQuery for.
   * @param mixed $criteria Any valid xPDO criteria expression.
   * @param boolean|integer $cacheFlag Indicates if the result should be cached
   * and optionally for how many seconds (if passed an integer greater than 0).
   * @return xPDOQuery The resulting xPDOQuery instance or false if unsuccessful.
   */
  function newCriteria( $criteria= NULL){
    $query = new xPDOCriteria($this->_db, $criteria, $this->_meta->getGraph(), $this->_xpdo_cache);
    return $query;
  }
  /**
   *
   * Enter description here ...
   * @param mixed $criteria
   */
  function iterateCollection( $criteria, $callback, $options = array()){
    $callback = array( $this, $callback);
    $criteria = $this->newCriteria( $criteria);
    return DB::iterateCollection($this->_db, $this->_class, $criteria,
    $this->_xpdo_cache, $callback, $options);
  }
  /**
   *
   * Enter description here ...
   * @param mixed $criteria
   */
  function ajaxResponse( $criteria){
    return $this->iterateCollection($criteria, '_metaResponse');
  }
}