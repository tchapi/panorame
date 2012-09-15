<?php

	include_once('../config.php');
	include_once('../DB/DB.php');
	include_once('../DB/Utils.php');

	if (isset($_POST['bounds'])){
		$bounds = $_POST['bounds'];

		$vertices = Utils::getVerticesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"]);

		$result = array(
			'bounds' => $bounds,
			'vertices' => $vertices
		);

	} else {

		$result = array(
			'error' => 'Bad Request',
			'code'	=> 1
		);
		
	}

	header('Content-type: application/json');
	print json_encode($result);
