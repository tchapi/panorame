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

	if (isset($_POST['bounds']) && isset($_POST['poi'])){

		$bounds = $_POST['bounds'];

		// Get POI Closest point

		$poi = array('lat' => $_POST['poi']['lat'], 'lng' => $_POST['poi']['lng']);
		$closestVertex = Utils::getClosestVertex($poi['lat'], $poi['lng'], _closestPointRadius_search);

		// GEt objects
		if ($type == 'vertices'){

			$objects = Utils::getVerticesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"], $closestVertex['point']['lat'], $closestVertex['point']['lng']);
		
		} elseif ($type == 'edges'){

			$objects = Utils::getEdgesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"], $closestVertex['point']['lat'], $closestVertex['point']['lng']);

		} elseif ($type == 'tree'){

			$objects = Utils::getVerticesAndChildrenIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"], $closestVertex['point']['lat'], $closestVertex['point']['lng']);

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
