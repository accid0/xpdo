<?php
/**
 *
 * @package xpdo
 * @subpackage cache
 */
include_once 'xpdomemcache.class.php';
class xPDOTagMemCache extends xPDOMemCache{
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
     return $this->memcache->increment( $this->getCacheKey($key), $value);
   }
   /**
    * 
    * Enter description here ...
    * @param string $key
    * @param int $value
    */
   public function decrement( $key, $value = 1){
     return $this->memcache->decrement( $this->getCacheKey($key), $value);
   }
}