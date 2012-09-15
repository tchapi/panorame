<?php

	include_once('../config.php');
	include_once('../DB/DB.php');
	include_once('../DB/Utils.php');

	// Defaults to "Edges"

	if (isset($_POST['type']) && $_POST['type'] == 'vertices'){
		$type = 'vertices';
	} else {
		$type = 'edges';
	}

	if (isset($_POST['bounds'])){

		$bounds = $_POST['bounds'];

		if ($type == 'vertices'){

			$objects = Utils::getVerticesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"]);
		
		} elseif ($type == 'edges'){
		
			$objects = Utils::getEdgesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"]);
		
		}

		$result = array(
			'bounds' => $bounds,
			'count'  => count($objects),
			  $type  => $objects
		);

	} else {

		$result = array(
			'error' => 'Bad Request',
			'code'	=> 1
		);
		
	}

	header('Content-type: application/json');
	print json_encode($result);
