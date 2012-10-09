<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 *@name meta.php
 *@packages Model
 *@subpackage Meta
 *@category Meta
 *@author Andrew Scherbakov
 *@version 1.0
 *@copyright created  2012 - 06 Jun - 07 Thu
 *
 *
 */

class Meta implements Iterator {
  /**
   *
   * Enter description here ...
   * @var xPDO
   */
  private $_db = NULL;
  /**
   *
   * Enter description here ...
   * @var int
   */
  private $_position = 0;
  /**
   *
   * @var string
   */
  private $_json = '';
  /**
   *
   * Enter description here ...
   * @var xPDOObject
   */
  private $_object = NULL;
  /**
   *
   * Enter description here ...
   * @var array
   */
  private $_keys = array();
  /**
   *
   * Enter description here ...
   * @var string
   */
  protected $_table = NULL;
  /**
   *
   * Enter description here ...
   * @var array
   */
  private $_fields = array();
  /**
   *
   * Enter description here ...
   * @var string
   */
  private $_fk = NULL;
  /**
   *
   * Enter description here ...
   * @var Model
   */
  private $_root = NULL;
  /**
   *
   * Enter description here ...
   * @param mixed $exp
   * @param string $msg
   * @throws Exception
   */
  private function ensure( $exp, $msg){
    if ( $exp) throw new Exception($msg);
  }
  /**
   * @param string $msg
   */
  private function log( $msg){
    $this->_db->log( xPDO::LOG_LEVEL_DEBUG,
    var_export($msg, TRUE));
  }
  /**
   *
   * Enter description here ...
   * @param array $array
   */
  private function init( $array){
    list($this->_table) = array_keys( $array);
    $fields = $array[ $this->_table];
    if ( is_null( $this->_json))  $this->_json = json_encode($array);
    $this->_object = $this->_db->newObject( $this->_table);
    $this->ensure( is_null( $this->_table) || !is_array( $fields) || empty($fields),
    	"[Model]: не правильно задан массив [" .
    var_export($fields, TRUE) . "]");
    foreach ( $fields as $key => $field){
      if ( !is_array( $field)){
        $this->_fields [$key]= $this->createColumn( $this->_table, $field, $this->_fk, $key);
      }
      else {
        $fk = $this->_object->getFKDefinition( $key);
        $newtable = array( $fk['class'] => $field);
        $this->_fields [$key]= new self( $newtable, $key, $this->_root);
      }
    }
  }
  /**
   *
   * Enter description here ...
   * @param string $table
   * @param string $field
   * @param string $fk
   * @param string $key
   */
  private function createColumn( $table, $field, $fk, $key){
    $column = new Column( $table, $field, $fk);
    $object = $this->_db->newObject( $table);
    $pk = $object->getPk();

    if ( $field == $pk){
      $column->disable();
      $column->name = $key;
      return $column;
    }
    elseif ( $fk != ''){
      $query = $this->_db->newQuery( $table);
      $query->select( "$pk, $field");
      $options = array( 'field' => $field);
      $array = DB::iterateCollection( $this->_db, 
        $table, $query, TRUE, array( $this, '_fetchField'), $options);
      $data = array();
      foreach ( $array as $item)
      $data [$item]= $item;
      $column->data = json_encode( $data);
      $column->type = 'multiselect';
      $column->onblur = 'ignore';
    }
    $serialized = $this->_root->serialize();
    $pk = $this->_root->getPk();
    $column->name = $key;
    $column->submitdata = <<<EOF
  function (value, settings) {
  	var id = $(this.parentNode).attr("id");
  	data = {
  	$pk : id,
  		serialized : "$serialized"
  	}
  	return data;
  }
EOF;
  	return $column;
  }
  /**
   *
   * Enter description here ...
   * @param array $array
   */
  function __construct( $array, $fk = '', $root = NULL, $serializeKey = NULL) {
    $this->ensure( !is_array($array),
    	"[Model]: конструктор принимает только массивы");
    $this->_fk = (string)$fk;
    if ( is_null( $root))  $this->_root = $this;
    else  $this->_root =$root;
    $this->_db = DB::instance();
    $this->_json = $serializeKey;
    $this->init( $array);
    $this->_keys = array_keys( $this->_fields);
  }
  /**
   *
   * Enter description here ...
   * @return string
   */
  function getTable(){
    return $this->_table;
  }
  /**
   * @return array
   * Enter description here ...
   */
  function getArrayGraph(){
    $array = array();
    foreach ( $this->_fields as $key => $field){
      if ($field instanceof  self) {
        $array [$key]= $field->getArrayGraph();
      }
    }
    return $array;
  }
  /**
   *
   * Enter description here ...
   * @param xPDOObject $obj
   */
  function getMetaValue( xPDOObject $obj){
    $rows = array();
    foreach( $this->_fields as $key => $column){
      if ( $column instanceof self){
        $fk = $obj->getFKDefinition( $key);
        if ($fk['cardinality'] == 'many')
        $relateds = $obj->getMany( $key);
        else  $relateds = array( $obj->getOne( $key));
        foreach ($relateds as $related){
          $rows = array_merge_recursive($rows, $column->getMetaValue( $related));
        }
      }
      else{
        $rows [$key]= $obj->get($column->getColumn());
      }
    }
    return $rows;
  }
  /**
   * @return string
   */
  function serialize(){
    return addslashes( $this->_json);
  }
  /**
   * 
   * Enter description here ...
   * @param string $key
   */
  function setSerializeKey( $key){
    $this->_json = $key;
  }
  /**
   * @return string
   * Enter description here ...
   */
  function getGraph(){
    $graph = $this->getArrayGraph();
    return json_encode($graph);
  }
  /**
   * @return string
   */
  function getPk(){
    $pk = $this->_object->getPK();
    foreach ( $this->_fields as $key => $value){
      if ( $pk == $value->getColumn())  return $key;
    }
    return "null";
  }
  /**
   * 
   * Enter description here ...
   * @param xPDOObject $obj
   * @param array $options
   * @return string
   */
  function _fetchField( xPDOObject $obj, $options){
    return $obj->get( $options['field']);
  }
  /**
   *
   * @param string $key
   * @return Column
   */
  function __get( $key){
    foreach ( $this->_fields as $id => $field){
      if ( $field instanceof  self){
        $r = $field->$key;
        if ( $r !== NULL)  return $r;
      }
      elseif ( $id === $key)  return $field;
    }
    return NULL;
  }
  /**
   *
   * @param string $key
   * @return boolean
   */
  function  __isset( $key){
    foreach ( $this->_fields as $id => $field){
      if ( $field instanceof  self){
        if ( isset( $field->$key))  return TRUE;
      }
      elseif ( $id === $key)  return TRUE;
    }
    return FALSE;
  }
  /**
   *
   * @param string $key
   * @param mixed $value
   */
  function __set( $key, $value){
    foreach ( $this->_fields as $id => $col){
      if ( ($col instanceof self) && isset($col->$key)){
        $fk = $this->_object->getFKDefinition( $id);
        if ( $fk['cardinality'] == 'many'){
          if ( !is_array( $value))  $value = array( $value);
          $objs = array();
          foreach ( $value as $item){
            if ( $ob = $col->get( $key, $item))
            $objs []= $ob;
          }
          if ( !empty( $objs))
          $this->_object->addMany( $objs);
          return;
        }
        elseif ( $fk['cardinality'] == 'one'){
          if ( $ob = $col->get( $key, $value))  $this->_object->addOne( $ob);
          return;
        }
      }
      elseif ( $id === $key){
        $f = $col->getColumn();
        $this->_object->set($f, $value);
        return ;
      }
    }
  }
  /**
   *
   * Enter description here ...
   * @param string $class
   * @param string $key
   * @param mixed $value
   * @return xPDOObject|NULL
   */
  function get( $key, $value){
    $result = NULL;
    foreach( $this->_fields as $id => $col){
      if ( ( $col instanceof self) && isset( $col->$key)){
        $result = $this->_db->newObject( $this->_table);
        $fk = $result->getFKDefinition( $id);
        if ( $fk['cardinality'] == 'many'){
          if ( !is_array( $value))  $value = array( $value);
          $objs = array();
          foreach ( $value as $item){
            if ( $ob = $col->get( $key, $item))
            $objs []= $ob;
          }
          if ( !empty( $objs))
          $result->addMany( $objs);
          else $result = NULL;
        }
        elseif ( $fk['cardinality'] == 'one'){
          if ( $ob = $col->get( $key, $value))
          $result->addOne( $ob);
          else $result = NULL;
        }
        $this->_object = $result;
        return $result;
      }
      elseif ( $key === $id){
        $query = $this->_db->newQuery( $this->_table);
        $query->where( array(
        $col->getColumn() => $value
        ));
        $result = $this->_db->getObject( $this->_table, $query);
        $this->_object = $result;
        return $result;
      }
    }
    return $result;
  }
  /**
   * @return xPDOObject
   */
  function save(){
    if ( !is_null( $this->_object)){
      if ( !$this->_object->isNew()){
        foreach ( $this->_object->_relatedObjects as $key => $obj){
          if ( !empty( $obj)){
            $fk = $this->_object->getFKDefinition( $key);
            $this->_object->_relatedObjects [$key]= array();
            if ( $fk['cardinality'] == 'many'){
              $del = $this->_object->getMany( $key);
              foreach ( $del as $d)
              $d->remove( array($this->_table));
            }
            elseif ( $fk['cardinality'] == 'one'){
              $del = $this->_object->getOne( $key);
              $del->remove( array($this->_table));
            }
            $this->_object->_relatedObjects [$key]= $obj;
          }
        }
      }
      $this->_object->save();
    }
    return  $this->_object;
  }
  /**
   *
   * Enter description here ...
   */
  function delete(){
    if ( !is_null( $this->_object))
    $this->_object->remove();
  }
  /**
   *
   */
  function rewind(){
    $this->_position = 0;
    foreach ($this->_fields as $field){
      if ( $field instanceof self) $field->rewind();
    }
  }
  /**
   * @return mixed
   */
  function current(){
    if ( $this->_fields[ $this->_keys[ $this->_position]] instanceof  self){
      return $this->_fields[ $this->_keys[ $this->_position]]->current();
    }
    return $this->_fields[ $this->_keys[ $this->_position]];
  }
  /**
   * @return int
   * Enter description here ...
   */
  function key(){
    if ( $this->_fields[ $this->_keys[ $this->_position]] instanceof  self){
      return $this->_fields[ $this->_keys[ $this->_position]]->key();
    }
    return $this->_keys[ $this->_position];
  }
  /**
   *
   * Enter description here ...
   */
  function next(){
    if ( $this->_fields[ $this->_keys[ $this->_position]] instanceof  self){
      $this->_fields[ $this->_keys[ $this->_position]]->next();
      if ( !$this->_fields[ $this->_keys[ $this->_position]]->valid())
      ++$this->_position;
    }
    ++$this->_position;
  }
  /**
   * @return boolean
   * Enter description here ...
   */
  function valid(){
    if ( !isset( $this->_keys[ $this->_position]))  return FALSE;
    if ( $this->_fields[ $this->_keys[ $this->_position]] instanceof  self){
      return $this->_fields[ $this->_keys[ $this->_position]]->valid();
    }
    return TRUE;
  }
}