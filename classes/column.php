<?php
/**
*@name Column.php
*@packages models
*@subpackage db
*@author Andrew Scherbakov
*@version 1.0
*@copyright created 22.05.2012
*/

class Column {
  /**
   * 
   * Enter description here ...
   * @var string
   */
  private $column;
  /**
   * 
   * Enter description here ...
   * @var string
   */
  private $table;
  /**
   * 
   * Enter description here ...
   * @var string
   */
  private $fk;
  /**
   * 
   * Enter description here ...
   * @var array
   */
  private $info = array();
  /**
   * 
   * Enter description here ...
   * @param string $name
   */
  function __construct( $table, $column, $fk = ''){
    $this->table = (string)$table;
    $this->column = (string)$column;
    $this->fk = (string)$fk;
  }
  /**
   * @return string
   * Enter description here ...
   */
  function __toString(){
    if ( $this->fk != '')
      return "{$this->fk}.{$this->column}";
    else  return "{$this->table}.{$this->column}";
  }
  /**
   * @return string
   */
  function getColumn(){
    return $this->column;
  }
  /**
   * @return boolean
   */
  function isFk(){
    return $this->fk !== '';
  }
  /**
   * 
   * @param mixed $var
   */
  function __set( $key, $var){
    $this->info [$key]= $var;
  }
  /**
   * 
   * Enter description here ...
   * @param string $key
   */
  function __get( $key){
    return isset( $this->info[$key])? $this->info[$key]: NULL;
  }
  /**
   * @return string
   * Enter description here ...
   */
  function toJSON(){
    if ( $this->info === NULL)  return "\nnull";
    $result = '';
    foreach ( $this->info as $key => $value){
      if ( strpos( $value, 'function') === FALSE){
        $result .= "$key:\"" . addslashes($value) . "\",";
      }
      else  $result .= "$key:$value,";
    }
    $result = "{" . $result . "}";
    return $result;
  }
  /**
   * 
   * Enter description here ...
   */
  function disable(){
    $this->info = array(
      'event' => 'false'
    );
  }
}