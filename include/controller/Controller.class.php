<?php

require_once(_PATH.'include/controller/constants.php');

class Controller {

  /** Defaults */
  static private $parameters   = array();
  static private $action       = null;
  static private $ajaxRequest  = false;

  public static function process(){

    date_default_timezone_set('Europe/Paris');

    /** Is an Ajax request ? */
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && 
       isset($_POST['ajax']) && $_POST['ajax'] == 1) {
      self::$ajaxRequest = true;
    }
    
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

    /** Load interfaces */
    $maps_utils_interface = _PATH.'include/DB/MapUtilsInterface.php';
    require_once($maps_utils_interface);
    $poi_utils_interface = _PATH.'include/DB/PoiUtilsInterface.php';
    require_once($poi_utils_interface);

    // Load database connector
    $database_connector_path = _PATH.'include/DB/'.self::$parameters['engine'].'/DBConnection.class.php';
    require_once($database_connector_path);

    /** Load utils */
    $geo_utils_path   = _PATH.'include/Geo/GeoUtils.class.php';
    $poi_service_path = _PATH.'include/POI/PoiService.class.php';
    $map_utils_path   = _PATH.'include/DB/'.self::$parameters['engine'].'/MapUtils.class.php';
    $poi_utils_path   = _PATH.'include/DB/'.self::$parameters['engine'].'/PoiUtils.class.php';
    require_once($geo_utils_path);
    require_once($poi_service_path);
    require_once($map_utils_path);
    require_once($poi_utils_path);

    /** Load action if any */
    if (self::$action != null) {

      if ( substr(self::$action,0,4) === 'edit' ) {
        $admin_utils_path = _PATH.'include/DB/'.self::$parameters['engine'].'/AdminUtils.class.php';
        require_once($admin_utils_path);
      }
      
      if (! @include_once(_PATH.'include/actions/'.self::$action.'.action.php'))
        throw new Exception ('No action with this name');

    }

  }

  public static function getParameters(){

    global $constants;

    /* PAGES */
    if (isset($_GET['page'])) {
    
      $exists = false;
      foreach($constants['pages'] as $page){
        if ($page['slug'] == $_GET['page']) {
          $exists = $page;
          break;
        }
      }

      if ($exists !== false) {  
        self::$parameters['page'] = $page;
      } else {
        self::$parameters['page'] = array('slug' => '404', 'name' => '404');
      }

    } else {
      self::$parameters['page'] =  array('slug' => 'map', 'name' => 'Home');
    }

    /* Short links */
    if (self::$parameters['page']['slug'] == 'map') {

      // mean
      $mean = $constants['defaults']['mean'];
      if ( isset($_GET['v']) && in_array($_GET['v'], $constants['limits']['mean'])) {
        $mean = intval($_GET['v']);
      }

      // speed
      $speed = $constants['defaults']['speed'];
      if ( isset($_GET['s']) && in_array($_GET['s'], $constants['limits']['speed'])) {
        $speed = intval($_GET['s']);
      }

      // poi
      $poi = $constants['defaults']['poi'];
      if ( isset($_GET['i']) ) {

        $rawPOI = explode(',', $_GET['i']);
        if ( count(array_intersect($rawPOI, $constants['limits']['poi'])) == count($rawPOI) ) {
          $poi = trim($_GET['i']);
        }

      } 

      // time
      $time = $constants['defaults']['time'];
      if ( isset($_GET['t']) && $_GET['t'] < $constants['limits']['time']['max'] 
                             && $_GET['t'] > $constants['limits']['time']['min'] ) {
        $time = intval($_GET['t']);
      }

      self::$parameters['inits'] = array(
        'lat' => isset($_GET['lat'])?floatval($_GET['lat']):null,
        'lng' => isset($_GET['lng'])?floatval($_GET['lng']):null,

        'mean' => $mean,
        'speed'  => $speed,
        'time'   => $time,

        'poi' => $poi,
      );

      $slug = isset($_GET['slug'])?floatval($_GET['slug']):null;

    }

    /* DEFAULTS */
    self::$parameters['addedScript'] = "";
    self::$parameters['framework']   = "gmaps";
    self::$parameters['provider']    = "";
    self::$parameters['editMode']    = false;
    self::$parameters['engine']      = "mysql";


    /** FRAMEWORK = API Provider */
    if (isset($_GET['framework']) && in_array($_GET['framework'], $constants['frameworks'])){
      self::$parameters['framework'] = $_GET['framework'];
    }

    /** PROVIDER = Tiles Provider */ 
    if (isset($_GET['provider']) && in_array($_GET['provider'], $constants['providers'])){
      self::$parameters['provider'] = $_GET['provider'];
      if ($_GET['framework'] == 'openlayers') self::$parameters['addedScript'] = "<script src='http://maps.google.com/maps/api/js?v=3.7&sensor=false'></script>";
    }

    /** ENGINE = Database engine */
    $cookieName = "panorame_engine";

    if (isset($_GET['engine']) && in_array($_GET['engine'], $constants['engines'])){
      self::$parameters['engine'] = $_GET['engine'];
    } else {
      self::$parameters['engine'] = $constants['defaults']['engine'];
    }


    /* Action */
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

    global $constants;
    
    $parameters = self::$parameters;
    $data = $actionResult;

    if ($templateName != null) {
    
      header('X-Panorame-Engine: '.self::$parameters['engine']);
      
      if (self::$ajaxRequest === true && isset($parameters['page'])) {

        ob_start();
          header('Content-Type: application/json');
          include(_PATH.'include/templates/pages/_'.$parameters['page']['slug'].'.php');
        $data = ob_get_clean();

        echo json_encode(array(
          'title' => _name . " | " . $parameters['page']['name'],
          'slug'  => $parameters['page']['slug'],
          'html'  => $data
        ));

      } else {

        include(_PATH.'include/templates/'.$templateName.'.php');

      }

    } else {

      throw new Exception ('No action nor template to render');

    }

    return;

  }

  public static function login($setCookie = false){

    $password = "panorame";
    $cookieName = "panorame_auth";
    self::$parameters['error'] = false;

    if ( (!isset($_POST['name']) || !isset($_POST['password']) || $_POST['password'] != $password || $_POST['name'] == "")
      && (!isset($_COOKIE[$cookieName]) || $_COOKIE[$cookieName] != md5(md5($password)) ) ){

      if (isset($_POST['password'])) self::$parameters['error'] = true;
      return false;

    } else {

      setcookie( $cookieName, md5(md5($password)), strtotime( '+30 days' ) );
      if (isset($_POST['name'])) 
        setcookie( $cookieName."_name", $_POST['name'], strtotime( '+30 days' ) );

      return true;

    }

  }

}