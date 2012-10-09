<?php
/**
 *
 * @package xpdo
 * @subpackage cache
 */
include_once 'xpdocachemanager.class.php';
class xPDOTagCacheManager extends xPDOCacheManager{
  /**
   *
   * Enter description here ...
   * @var string
   */
  const TAG_SUFFIX = 'tag_';
  /**
   * 
   * Enter description here ...
   * @var string
   */
  const LOCK_PREFIX = 'lock_';
  /**
   * 
   * Enter description here ...
   * @param mixed $var
   * @param array $tags
   */
  private function prepareValue( &$var, $tags){
    $value = array();
    $value['tags'] = array();
    $value['data'] = $var;
    foreach ( $tags as $tag){
      $t = parent::get(  self::TAG_SUFFIX . $tag);
      if ( $t == FALSE) {
        $t = 0;
        parent::add( self::TAG_SUFFIX . $tag, $t);
      }
      $value['tags'][$tag] = $t;
    }
    return $value;
  }
  /**
   * 
   * Enter description here ...
   * @param string $key
   * @param array $options
   */
  private function getOne( $result){
    if ( $result !== FALSE && isset($result['tags'])){
      $keys = array_keys( $result['tags']);
      array_walk( $keys, 
      create_function( '&$v,$k', 
      	'$v =  xPDOTagCacheManager::TAG_SUFFIX . $v;'));
      $tags = parent::get($keys);
      if ( count($tags) != count($result['tags']))  return FALSE;
      $i = 0;
      $tags = array_values( $tags);
      foreach ( $result['tags'] as $tag => $value){
        if ( $tags[$i] === FALSE || $tags[$i++] != $value)  return FALSE;
      }
    }
    if ( $result !== FALSE && isset( $result['data']))  return $result['data'];
    return $result;
  }
  /**
   * 
   * @param xPDO $xpdo
   * @param array $options
   */
  public function __construct(& $xpdo, $options = array()) {
    parent::__construct($xpdo, $options);
  }
  /**
   * (non-PHPdoc)
   * @see xPDOCacheManager::add()
   */
  public function add($key, & $var, $tags = array(),  $lifetime= 0, $options= array()) {
    if ( !$this->get($key, $options) && parent::get($key, $options))
      $this->delete($key, $options);
    return parent::add( $key, $this->prepareValue($var, $tags), $lifetime, $options);
  }
  /**
   * (non-PHPdoc)
   * @see xPDOCacheManager::replace()
   */
  public function replace($key, & $var, $tags = array(), $lifetime= 0, $options= array()) {
    return parent::replace($key, $this->prepareValue($var, $tags), $lifetime, $options);
  }
  /**
   * (non-PHPdoc)
   * @see xPDOCacheManager::set()
   */
  public function set($key, & $var, $tags = array(), $lifetime= 0, $options= array()) {
    return parent::set($key, $this->prepareValue($var, $tags), $lifetime, $options);
  }
  /**
   * (non-PHPdoc)
   * @see xPDOCacheManager::get()
   */
  public function get($key, $options = array()) {
    $temp = parent::get($key, $options);
    if (is_array( $key)){
      $result = array();
      foreach ($temp as $o){
        if ( $n =  $this->getOne( $o))  $result[] = $n;
      }
    }
    else   $result = $this->getOne($temp);
    return $result;
  }
  /**
   * 
   * Enter description here ...
   * @param string $tag
   */
  public function deleteByTag( $tag){
    $this->increment( self::TAG_SUFFIX . $tag);
  }
  /**
   * 
   * Enter description here ...
   * @param string $tag
   * @return bool
   */
  public function lock( $tag){
    $lock = 1;
    return parent::add( self::LOCK_PREFIX . $tag, $lock);
  }
  /**
   * 
   * Enter description here ...
   * @param string $tag
   * @return bool
   */
  public function releaseLock( $tag){
    return parent::delete( self::LOCK_PREFIX . $tag);
  }
   /**
    * 
    * Enter description here ...
    * @param mixed $key
    * @param int $value
    * @param array $options
    * @return mixed
    */
   public function increment( $key, $value = 1, $options = array()){
        $return= FALSE;
        if ($cache = $this->getCacheProvider($this->getOption(xPDO::OPT_CACHE_KEY, $options), $options)) {
            $return = $cache->increment($key, $value);
        }
        return $return;
   }
   /**
    * 
    * Enter description here ...
    * @param string $key
    * @param int $value
    * @param array $options
    * @return mixed
    */
   public function decrement( $key, $value = 1, $options = array()){
        $return= FALSE;
        if ($cache = $this->getCacheProvider($this->getOption(xPDO::OPT_CACHE_KEY, $options), $options)) {
            $return = $cache->decrement($key, $value);
        }
        return $return;
   }
   /**
    * 
    * Enter description here ...
    * @return int
    */
   function getN( $options = array()){
        $return= FALSE;
        if ($cache = $this->getCacheProvider($this->getOption(xPDO::OPT_CACHE_KEY, $options), $options)) {
            $return = $cache->getN();
        }
        return $return;
   }
}