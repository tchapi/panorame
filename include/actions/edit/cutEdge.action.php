<?php

	$app = '_BACKEND';
	include_once('../config.php');

	if (isset($_POST['start_id']) && isset($_POST['dest_id']) && isset($_POST['new_lat']) && isset($_POST['new_lng']) && isset($_POST['new_alt']) && isset($_POST['edge_id'])  &&  0 != $_POST['edge_id']){

		$start_id = intval($_POST['start_id']);
		$dest_id = intval($_POST['dest_id']);
		$new_lat = floatval($_POST['new_lat']);
		$new_lng = floatval($_POST['new_lng']);
		$edge_id = intval($_POST['edge_id']);

		$result = Utils::cutEdge($start_id, $dest_id, $new_lat, $new_lng, $new_alt, $edge_id);

		if ($result == null || $result == false){

			$result = array(
				'error' => 'Error updating vertex',
				'code'	=> 2
			);

		} else {

			$result = array(
				'status' => $result
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
