<?php defined('SYSPATH') or die('No direct script access.');
/**
 * 
 * 
 *@name xpdo.php
 *@packages DB/config
 *@subpackage config
 *@category config
 *@author Andrew Scherbakov
 *@version 1.0
 *@copyright created  2012 - 06 Jun - 07 Thu
 *
 *
 */

return array
(
  'connection' => array(
    'dsn'                              => 'mysql:host=localhost;dbname=kohana',
    'user'                             => 'kohana',
    'pass'                             => 'kohana'
  ),
  'xpdo' => array(
    xPDO::OPT_CACHE_PATH               => APPPATH . 'cache/',
    xPDO::OPT_CACHE_HANDLER            => 'cache.xPDOTagFileCache',
    xPDO::OPT_CACHE_KEY                => 'xpdo',
    xPDO::OPT_CACHE_DB_HANDLER         => 'cache.xPDOFileCache',
    //xPDO::OPT_CACHE_DB_OBJECTS_BY_PK   => TRUE,
    xPDO::OPT_CACHE_DB_COLLECTIONS     => TRUE,
    xPDO::OPT_CACHE_FORMAT             => xPDOCacheManager::CACHE_PHP,
    xPDO::OPT_CACHE_DB                 => TRUE,
  	xPDO::OPT_TABLE_PREFIX             => 'khn_',
    xPDO::OPT_HYDRATE_FIELDS           => TRUE,
  	xPDO::OPT_HYDRATE_RELATED_OBJECTS  => TRUE,
  	xPDO::OPT_HYDRATE_ADHOC_FIELDS     => TRUE,
  	xPDO::OPT_VALIDATE_ON_SAVE         => TRUE,
  	xPDO::OPT_AUTO_CREATE_TABLES       => TRUE,
  	'charset' => 'utf8'
  ),
  'pdo' => array(
    PDO::ATTR_ERRMODE                  => PDO::ERRMODE_SILENT,
    PDO::ATTR_PERSISTENT               => FALSE,
	PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => TRUE
  ),
  'class'	=> array(
    'debug'                            => FALSE,
    'loglevel'						   => xPDO::LOG_LEVEL_DEBUG,
  	'logtarget'						   => 'FILE',
    'filename'						   => strftime('%Y/%m/%d') . '.xpdo.log',
    'filepath'						   => APPPATH . 'logs/',
    'modelpath'						   => APPPATH . 'packages/'
  )
);