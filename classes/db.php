<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 *@name DB.php
 *@packages DB
 *@subpackage DB
 *@category DB
 *@author Andrew Scherbakov
 *@version 1.0
 *@copyright created  2012 - 06 Jun - 07 Thu
 *
 *
 */

/*
 * Copyright 2010 by Mike Schell <mike@webprogramming.ca>
 *
 * This file is part of xPDO-Collection-Tools which is based on, contains code
 * from and extends xPDO copyright 2006-2010 by Jason Coward <xpdo@opengeek.com>
 *
 * xPDO-Collection-Tools is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option) any
 * later version.
 *
 * xPDO-Collection-Tools is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * xPDO-Collection-Tools; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

require_once MODPATH . 'xpdo/classes/xpdo/xpdo.class.php';
require_once MODPATH . 'xpdo/classes/xpdo/cache/xpdocachemanager.class.php';
require_once MODPATH . 'xpdo/classes/xpdo/om/xpdoobject.class.php';

class DB {
  /**
   *
   * Enter description here ...
   * @var DB
   */
  private static $instance = NULL;
  /**
   *
   * Enter description here ...
   * @var array
   */
  private static $output = array();
  /**
   *
   * @return DB
   */
  public static function instance(){
    if ( is_null( self::$instance))  new self;
    return self::$instance;
  }
  /**
   *
   * Enter description here ...
   */
  private function __construct(){
    $config = Kohana::$config->load('xpdo');
    self::$instance = new xPDO(  $config['connection']['dsn'],
    $config['connection']['user'], $config['connection']['pass'],
    $config['xpdo'], $config['pdo']
    );
    if ( $config['class']['debug']){
      self::$instance->setDebug();
    }
    self::$instance->setLogLevel($config['class']['loglevel']);
    self::$instance->setLogTarget(
      array(
  		'target' => $config['class']['logtarget'],
  	 	'options' => array(
  	 		'filename' => $config['class']['filename'],
  	 		'filepath' => $config['class']['filepath']
        )
      ));
  }
  /**
   *
   * Enter description here ...
   * @param string $msg
   */
  public static function log( $msg){
    $xpdo = self::instance();
    $xpdo->log( xPDO::LOG_LEVEL_INFO, $msg);
  }
  /**
   * Iterate over a collection of xPDOObjects, pass each instance to a callback function.
   *
   * This is an optimization so that collections can be operated on without
   * having the whole collection of objects in memory at once.
   * Based on xPDOObject :: loadCollection() copyright 2006-2010 by Jason Coward <xpdo@opengeek.com>
   *
   * @static
   * @param xPDO &$xpdo A valid xPDO instance.
   * @param string $className Name of the class.
   * @param mixed $criteria A valid primary key, criteria array, or xPDOCriteria instance.
   * @param boolean|integer $cacheFlag Indicates if the objects should be
   * cached and optionally, by specifying an integer value, for how many seconds.
   * @param callable $callback Specifier for the function/method that will render objects using $tplOptions
   * @param array $tplOptions Template options vary according to renderer function
   * @return string The accumulated result of rendering each object in the collection
   */
  public static function iterateCollection(xPDO & $xpdo, $className, $criteria, $cacheFlag, $callback, $tplOptions = array() ) {
    self::$output = array();
    $fromCache = false;
    if (!$className= $xpdo->loadClass($className)) return self::$output;
    $rows= false;
    $fromCache= false;
    $collectionCaching = (integer) $xpdo->getOption(xPDO::OPT_CACHE_DB_COLLECTIONS, array(), 1);
    if (!is_object($criteria)) {
      $criteria= $xpdo->getCriteria($className, $criteria, $cacheFlag);
    }
    if ($collectionCaching > 0 && $cacheFlag) {
      $rows= $xpdo->fromCache($criteria);
      $fromCache = (is_array($rows) && !empty($rows));
    }
    if (!$fromCache && is_object($criteria)) {
      $rows= xPDOObject :: _loadRows($xpdo, $className, $criteria);
    }
    if (is_array ($rows)) {
      foreach ($rows as $row) {
        self :: _processInstance($xpdo, $className, $criteria, $row, $fromCache, $cacheFlag, $callback, $tplOptions);
      }
    } elseif (is_object($rows)) {
      $cacheRows = array();
      while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
        self :: _processInstance($xpdo, $className, $criteria, $row, $fromCache, $cacheFlag, $callback, $tplOptions);
        if ($collectionCaching > 0 && $cacheFlag && !$fromCache) $cacheRows[] = $row;
      }
      if ($collectionCaching > 0 && $cacheFlag && !$fromCache) $rows =& $cacheRows;
    }
    if (!$fromCache && $collectionCaching > 0 && $cacheFlag && !empty($rows)) {
      $xpdo->toCache($criteria, $rows, $cacheFlag);
    }
    return self::$output;
  }

  /**
   * process object instance
   *
   * Get xPDOObject instance and process via callback function.
   * Get from / saves to cache as appropriate.
   * Based on xPDOObject :: _loadCollectionInstance() copyright 2006-2010 by Jason Coward <xpdo@opengeek.com>
   *
   * @param object $xpdo
   * @param string $output Rendered output is appended in this
   * @param callable $callback
   * @param array $tplOptions [string 'tpl' Template code, string 'phPrefix' prefix for placeholder names in template]
   */
  protected static function _processInstance(xPDO & $xpdo, $className, $criteria, $row, $fromCache, $cacheFlag=true, $callback, $tplOptions){
    $loaded = false;
    $search = array();
    $replace = array();
    if($obj= xPDOObject :: _loadInstance($xpdo, $className, $criteria, $row)) {
      $cacheKey= $obj->getPrimaryKey();
      if (($cacheKey !== NULL) && !$obj->isLazy()) {
        if (is_array($cacheKey)) {
          $pkval= implode('-', $cacheKey);
        } else {
          $pkval= $cacheKey;
        }
        if ($xpdo->getOption(xPDO::OPT_CACHE_DB_COLLECTIONS, array(), 1) && $cacheFlag) {
          if (!$fromCache) {
            $pkCriteria = $xpdo->newQuery($className, $cacheKey, $cacheFlag);
            $xpdo->toCache($pkCriteria, $obj, $cacheFlag);
          } else {
            $obj->_cacheFlag= true;
          }
        }
        $loaded = true;
      } else {
        
        $loaded = true;
      }
    }
    if($loaded) {
      if(is_callable($callback)) {
        self::$output []= call_user_func_array($callback, array(&$obj, &$tplOptions));
      }
    }
    unset($vals, $obj);
    return $loaded;
  }
}
