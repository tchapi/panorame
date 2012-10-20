<?php

  $app = '_BACKEND';
  include_once('../config.php');

  if (isset($_POST['lat']) && isset($_POST['lng']) ){

    $lat = floatval($_POST['lat']);
    $lon = floatval($_POST['lng']);

    $radius = isset($_POST['radius'])?intval($_POST['radius']):_closestPointRadius_search;

    $result = DBUtils::getClosestVertex($lat, $lon, $radius);
  
  } else {
  
    $result = array(
      'error' => 'Bad Request',
      'code'  => 1
    );
    
  }

  header('Content-type: application/json');
  print json_encode($result);
