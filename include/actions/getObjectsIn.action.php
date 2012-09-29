<?php

	$app = '_BACKEND';
	include_once('../config.php');

	// Defaults to "Edges"

	if (isset($_POST['type']) && $_POST['type'] == 'vertices'){
		$type = 'vertices';
	} elseif (isset($_POST['type']) && $_POST['type'] == 'edges') {
		$type = 'edges';
	} else if (isset($_POST['type']) && $_POST['type'] == 'tree'){
		$type = 'tree';
	}

	if (isset($_POST['bounds'])){

		$bounds = $_POST['bounds'];

		if ($type == 'vertices'){

			$objects = Utils::getVerticesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"]);
		
		} elseif ($type == 'edges'){

			$objects = Utils::getEdgesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"]);

		} elseif ($type == 'tree'){

			$objects = Utils::getVerticesAndChildrenIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"]);

		}

		if (isset($_POST['poi'])){

			$poi = array('lat' => $_POST['poi']['lat'], 'lng' => $_POST['poi']['lng']);
			$closestVertex = Utils::getClosestVertex($poi['lat'], $poi['lng'], _closestPointRadius_search);

		} else {

		//	$poi = null;
			$closestVertex = null;

		}

		$result = array(
		//	'bounds' => $bounds,
		//	'poi'	 => $poi,
			'closest'=> $closestVertex,
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
