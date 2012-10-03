<?php

	$app = '_BACKEND';
	include_once('../config.php');

	if (isset($_POST['start_lat']) && isset($_POST['dest_lat']) && isset($_POST['start_lng']) && isset($_POST['dest_lng'])){

		$start_lat = floatval($_POST['start_lat']);
		$start_lng = floatval($_POST['start_lng']);
		$start_alt = isset($_POST['start_alt'])?intval($_POST['start_alt']):0;

		$dest_lat = floatval($_POST['dest_lat']);
		$dest_lng = floatval($_POST['dest_lng']);
		$dest_alt = isset($_POST['dest_alt'])?intval($_POST['dest_alt']):0;

		$type = isset($_POST['type'])?intval($_POST['type']):0;

		$result = Utils::addEdge($start_lat, $start_lng, $start_alt, $dest_lat, $dest_lng, $dest_alt, $type);
	
		if ($result == null || $result == false){

			$result = array(
				'error' => 'Error creating edge',
				'code'	=> 2
			);

		} else {

			$result = array(
				'request' => array(
					'start' => array(
						'lat' => $start_lat,
						'lng' => $start_lng,
						'alt' => $start_alt
						),
					'dest' => array(
						'lat' => $dest_lat,
						'lng' => $dest_lng,
						'alt' => $dest_alt
						),
					'type' => $type,
					),
				'new'  => $result
			);

		}

	} else {
	
		$result = array(
			'error' => 'Bad Request',
			'code'	=> 1
		);
		
	}

	header('Content-type: application/json');
	print json_encode($result);
