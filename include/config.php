<?php

  /** Absolute path to the Tuneefy directory. */
  if ( !defined('_PATH') )
        define('_PATH', dirname(__FILE__) . '/');
  
  /* Closest function radius (in m) */
  define('_closestPointRadius_search', 200);
  define('_closestPointRadius_edit', 10);

	if ($app == '_FRONTEND') {

		/*
		 * FRONTEND Defaults
		 */

		$addedScript = "";
		$framework   = "gmaps";
		$provider    = "";

	} elseif ($app == '_BACKEND') {
		
		/*
		 * BACKEND Defaults
		 */

		$server = "localhost";
		$user = "isocron";
		$password = "isocron";
		$database = "isocron";

		$engine = "mysql"; // OR mysql

		/* ----------------------------- */
		/* ------- DO NOT MODIFY ------- */

			$database_connector_path = _PATH.'DB/'.$engine.'/DBConnector.php';
			$database_utils_path = _PATH.'DB/'.$engine.'/DBUtils.php';

			include_once($database_connector_path);
			include_once($database_utils_path);

		/* ----------------------------- */

	}