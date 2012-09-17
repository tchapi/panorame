<?php

	$app = '_BACKEND';
	include_once('../config.php');

	$start_lat = floatval($_POST['start_lat']);
	$start_lon = floatval($_POST['start_lon']);
	$start_alt = intval($_POST['start_alt']);

	$dest_lat = floatval($_POST['dest_lat']);
	$dest_lon = floatval($_POST['dest_lon']);
	$dest_alt = intval($_POST['dest_alt']);

	$type = intval($_POST['type']);

	$result = Utils::addEdge($start_lat, $start_lon, $start_alt, $dest_lat, $dest_lon, $dest_alt, $type);
	