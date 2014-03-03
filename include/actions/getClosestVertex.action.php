<?php

function doAction() {

  if (isset($_POST['lat']) && isset($_POST['lng']) ){

    $lat = floatval($_POST['lat']);
    $lon = floatval($_POST['lng']);

    $radius = isset($_POST['radius'])?intval($_POST['radius']):_closestPointRadius_search;

    $vertex = MapUtils::getClosestVertex($lat, $lon, $radius);
    $result = $vertex->toArray();
  
  } else {
  
    $result = array(
      'error' => 'Bad Request',
      'code'  => 1
    );
    
  }

  return $result;
}
