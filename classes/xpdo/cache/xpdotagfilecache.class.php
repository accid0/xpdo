<?php
/**
 *
 * @package xpdo
 * @subpackage cache
 */
include_once 'xpdocachemanager.class.php';
class xPDOTagFileCache extends xPDOFileCache{
  /**
   * 
   * Enter description here ...
   * @var int
   */
  private $n = 0;
  /**
   * 
   * Enter description here ...
   * @param xPDO $xpdo
   * @param array $options
   */
  public function __construct(& $xpdo, $options = array()) {
    parent::__construct($xpdo, $options);
  }
  /**
   * (non-PHPdoc)
   * @see xPDOCache::getCacheKey()
   */
  public function getCacheKey($key, $options = array()) {
     if ( is_array( $key)){
       $keys = array();
       foreach ( $key as $k){
         $keys[] = parent::getCacheKey($k, $options);
       }
     }
     else  $keys = parent::getCacheKey($key, $options);
     ++$this->n;
     return $keys;
   }
   /**
    * 
    * Enter description here ...
    * @return int
    */
   function getN(){
     return $this->n;
   }
   /**
    * 
    * Enter description here ...
    * @param mixed $key
    * @param int $value
    */
   public function increment( $key, $value = 1){
     $cache = (int)parent::get($key);
     return $this->set( $key, $cache + $value, 0);
   }
   /**
    * 
    * Enter description here ...
    * @param string $key
    * @param int $value
    */
   public function decrement( $key, $value = 1){
     $cache = (int)parent::get($key);
     return $this->set( $key, $cache - $value, 0);
   }
   /**
    * (non-PHPdoc)
    * @see xPDOFileCache::get()
    */
  public function get($key, $options = array()){
    $result = FALSE;
    if ( is_array( $key)){
      $result = array();
      foreach ( $key as $k)
        $result [$k] = parent::get($k, $options);
    }
    else  $result = parent::get($key, $options);
    return $result;
  }
  
}
