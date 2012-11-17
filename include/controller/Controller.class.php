<?php

require_once(_PATH.'include/controller/constants.php');

class Controller {

  /** Defaults */
  static private $parameters   = array();
  static private $action       = null;

  public static function process(){

    date_default_timezone_set('Europe/Paris');

    /** Is an Ajax request ?
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      self::$useTemplate = false;
    }
    */

    if (!self::login()) {
      self::render('login', null);
      return;
    }

    /** Retrieve Parameters */
    self::getParameters();

    /** Load class files */
    self::loadClasses();
 
    if (self::$action != null) {

      self::render('ajax/json', doAction());

    } else {

      self::render('index', null);

    }

    return;

  }

  public static function loadClasses(){

    $database_connector_path = _PATH.'include/DB/'.self::$parameters['engine'].'/DBConnector.class.php';
    require_once($database_connector_path);

    global $DBConnection;

    $DBConnection = new DBConnector(_server, _user, _password, _database);
    if ($DBConnection->connect()) $DBConnection->selectdb();

    /** Load utils */
    $geo_utils_path = _PATH.'include/Geo/GeoUtils.class.php';
    $database_utils_path = _PATH.'include/DB/'.self::$parameters['engine'].'/DBUtils.class.php';
    require_once($geo_utils_path);
    require_once($database_utils_path);

    /** Load action if any */
    if (self::$action != null) {
      if (! @include_once(_PATH.'include/actions/'.self::$action.'.action.php'))
        throw new Exception ('No action with this name');
    }

  }

  public static function getParameters(){

    /* DEFAULTS */
    self::$parameters['addedScript'] = "";
    self::$parameters['framework']   = "gmaps";
    self::$parameters['provider']    = "";
    self::$parameters['editMode']    = false;
    self::$parameters['engine']      = "mysql";


    /** FRAMEWORK = API Provider */
    if (isset($_GET['framework']) && in_array($_GET['framework'], $_constants['frameworks'])){
      self::$parameters['framework'] = $_GET['framework'];
    }

    /** PROVIDER = Tiles Provider */ 
    if (isset($_GET['provider']) && in_array($_GET['provider'], $_constants['providers'])){
      self::$parameters['provider'] = $_GET['provider'];
      if ($_GET['framework'] == 'openlayers') self::$parameters['addedScript'] = "<script src='http://maps.google.com/maps/api/js?v=3.7&sensor=false'></script>";
    }

    /** ENGINE = Database engine */
    $cookieName = "panorame_engine";

    if (isset($_GET['engine']) && in_array($_GET['engine'], $_constants['engines'])){
      self::$parameters['engine'] = $_GET['engine'];
    }


    if (isset($_GET['action'])){

      self::$action = $_GET['action'];
      self::$parameters['engine'] = $_COOKIE[$cookieName];

    } else {

      setcookie( $cookieName, self::$parameters['engine'], strtotime( '+30 days' ) );

    }

    /** EDIT Mode */ 
    if (isset($_GET['edit']) && $_GET['edit'] == 1) {

      self::$parameters['framework']   = 'gmaps';
      self::$parameters['provider']    = 'gmaps-road';
      self::$parameters['addedScript'] = '';

      self::$parameters['editMode']    = true;

    }

  }

  public static function render($templateName, $actionResult){

    $parameters = self::$parameters;
    $data = $actionResult;

    if ($templateName != null) {
    
      header('X-Panorame-Engine: '.self::$parameters['engine']);
      header('X-Powered-By: tchap');
      include(_PATH.'include/templates/'.$templateName.'.php');
    
    } else {

      throw new Exception ('No action nor template to render');

    }

    return;

  }

  public static function login(){

    $password = "panorame";
    $cookieName = "panorame_auth";
    self::$parameters['error'] = false;

    if ( (!isset($_POST['name']) || !isset($_POST['password']) || $_POST['password'] != $password || $_POST['name'] == "")
      && (!isset($_COOKIE[$cookieName]) || $_COOKIE[$cookieName] != md5($password)) ){

      if (isset($_POST['password'])) self::$parameters['error'] = true;
      return false;

    } else {

      setcookie( $cookieName, md5($password), strtotime( '+30 days' ) );
      return true;

    }

  }

}