<?php

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

		$database = "mongo"; // OR mysql

		/* ----------------------------- */
		/* ------- DO NOT MODIFY ------- */

			$database_connector_path = 'DB/'.$database.'/DBConnector.php';
			$database_utils_path = 'DB/'.$database.'/DBUtils.php';

			include_once($database_connector_path);
			include_once($database_utils_path);

		/* ----------------------------- */

	}