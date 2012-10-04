<?php

	$app = '_BACKEND';
	include_once('../../config.php');

	$numberOfEdgesToInsert = isset($_GET['n'])?intval($_GET['n']):0;

	if ($numberOfEdgesToInsert > 0 && $numberOfEdgesToInsert < 100000) {
		// Come on, INSERT SOME FUCKING VERTICES AND EDGES !!
		header('Content-type: application/json');
		print json_encode(array( 'number' => $numberOfEdgesToInsert, 'result' => Utils::fillWithRandomStuff($_GET['n'])));
	} else {
		// GO FUCK YOURSELF
		header('Content-type: application/json');
		print json_encode(array( 'number' => $numberOfEdgesToInsert, 'result' => array( 'state' => false, 'description' => 'Too many or too few!')));		
	}