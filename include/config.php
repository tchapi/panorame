<?php

  /** Absolute path to the Tuneefy directory. */
  if ( !defined('_PATH') )
        define('_PATH', dirname(__FILE__) . '/');
  
  /* Closest function radius (in m) */
  define('_closestPointRadius_search', 200);
  define('_closestPointRadius_edit', 5);
  define('_extendBoundsPointRadius', 500);
  
  define('_earth_radius', 6371030.00); // in m
  
  if ($app == '_FRONTEND') {

    /*
     * FRONTEND Defaults
     */

    $addedScript = "";
    $framework   = "gmaps";
    $provider    = "";
    $editMode    = false;

  } elseif ($app == '_BACKEND') {
    
    /*
     * BACKEND Defaults
     */

    $server   = "localhost";
    $user     = "isocron"; // tchap_panorame
    $password = "isocron"; // 8y3nP9922z6tu2en
    $database = "isocron"; // tchap_panorame

    $engine   = "mysql"; // OR mysql

    global $DBConnection;

    /* ----------------------------- */
    /* ------- DO NOT MODIFY ------- */

      $database_connector_path = _PATH.'DB/'.$engine.'/DBConnector.php';
      $database_utils_path = _PATH.'DB/'.$engine.'/DBUtils.php';

      include_once($database_connector_path);
      include_once($database_utils_path);

    /* ----------------------------- */

  }