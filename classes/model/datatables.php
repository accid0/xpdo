<?php defined('SYSPATH') or die('No direct script access.');
/**
 *
 *
 *@name model\datatables.php
 *@packages Model/Datatables
 *@subpackage Datatables
 *@category Model
 *@author Andrew Scherbakov
 *@version 1.0
 *@copyright created  2012 - 06 Jun - 07 Thu
 *
 *
 */

class Model_Datatables extends Kohana_Model{
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
  private $_output = array();
  /**
   *
   * Enter description here ...
   */
  private function init(){
    $post = Request::current()->post();
    $modelname = $post['serialized'];
    $this->_model = Model::factory($modelname);
    $meta = $this->_model->meta();
    $query = $this->_model->newQuery();
    /*
     * Script:    DataTables server-side script for PHP and MySQL
     * Copyright: 2010 - Allan Jardine
     * License:   GPL v2 or BSD (3-point)
     */

    /* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
     * Easy set variables
     */
     
    /* Array of database columns which should be read and sent back to DataTables. Use a space where
     * you want to insert a non-database field (for example a counter or static image)
     */
    $aColumns = explode( ',', $post['sColumns'] );
    $pk = $meta->getPk();
    $pk = (string)$meta->$pk;
    $sTable = $meta->getTable();
    /*
     * Ordering
     */
    if ( isset( $post['iSortCol']) )
    {
      for ( $i=0 ; $i<intval( $post['iSortingCols']) ; $i++ )
      {
        $cn = 'iSortCol_' . $i;
        $cn = intval($post[$cn]);
        $rn = 'bSortable_' . $cn;
        $dn = 'sSortDir_' . $i;
        if ( $post[$rn] == "true" )
        {
          $col = $aColumns[ $cn ];
          $query->sortby( (string)$meta->$col,  $post[$dn] );
        }
      }
    }
    /*
     * Filtering
     * NOTE this does not match the built-in DataTables filtering which does it
     * word by word on any field. It's possible to do here, but concerned about efficiency
     * on very large tables, and MySQL's regex functionality is very limited
     */
    if ( $post['sSearch'] != "" )
    {
      $sWhere = array();
      $sWhere [0]= array();
      for ( $i=0 ; $i<count($aColumns) ; $i++ )
      {
        $col = $aColumns[ $i ];
        $sWhere [0]['OR:' . $meta->$col . ':LIKE']=  '%' . $post['sSearch'] . '%';
      }
       
      /* Individual column filtering */
      for ( $i=0 ; $i<count($aColumns) ; $i++ )
      {
        $sn = 'bSearchable_'.$i;
        $cn = 'sSearch_'.$i;
        if ( $post[$sn] == "true" && $post[$cn] != '' )
        {
          $col = $aColumns[ $i ];
          $sWhere ['AND:' . $meta->$col . ':LIKE']= '%' . $post[$cn] . '%';
        }
      }
      $query->where( $sWhere);
    }
     
    $query->groupby("$pk");

    /*
     * Paging
     */
    $sLimit = "";
    if ( isset( $post['iDisplayStart']) && $post['iDisplayStart'] != '-1' )
    {
      $query->limit(  $post['iDisplayLength'], $post['iDisplayStart']);
    }
    $query->prepare();
    $sql = $query->toSql();
    $sql = str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $sql);
    $data = $this->_model->ajaxResponse( $sql);

    $query = $this->_model->newCriteria('SELECT FOUND_ROWS() as cnt');
    $query->prepare();
    $query->stmt->execute();
    $iFilteredTotal = $query->stmt->fetch( PDO::FETCH_ASSOC);
    $iFilteredTotal = $iFilteredTotal['cnt'];

    $query = $this->_model->newQuery();
    $iTotal = DB::instance()->getCount( $sTable, $query);

    /*
     * Output
     */
    $this->_output = array(
      		"sEcho" => intval($post['sEcho']),
      		"iTotalRecords" => $iTotal,
      		"iTotalDisplayRecords" => $iFilteredTotal,
      	    "sColumns" => $post['sColumns'],
      		"aaData" => $data
    );
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
   * @return string
   */
  function render(){
    return json_encode( $this->_output);
  }
}