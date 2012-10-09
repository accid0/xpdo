<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Model base class. All models should extend this class.
 *
 * @package    Kohana
 * @category   Models
 * @author     Kohana Team
 * @copyright  (c) 2008-2011 Kohana Team
 * @license    http://kohanaframework.org/license
 */
abstract class Kohana_Model {
  /**
   * 
   * Enter description here ...
   * @var string
   */
    protected static $_classname = NULL;
	/**
	 * Create a new model instance.
	 *
	 *     $model = Model::factory($name);
	 *
	 * @param   string   model name
	 * @return  Model
	 */
	public static function factory($name)
	{
		self::$_classname = $name;
		
		// Add the model prefix
		$name = str_replace( array( '\\', '/'), '_', $name);
		$class = 'Model_'.$name;

		return new $class;
	}

} // End Model