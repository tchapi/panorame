<?php

function doAction() {

  if (isset($_POST['bounds']) && !is_null($_POST['bounds']) && is_array($_POST['bounds']) && isset($_POST['poi']) && isset($_POST['type'])) {

    switch ($_POST['type']){
      case 'vertices':
      case 'edges':
      case 'tree':
        $type = $_POST['type'];
        break;
      default:
        $type = 'vertices';
    }

    $restrictToType = $_POST['restrictToType'];
    $bounds = $_POST['bounds'];

    // Get POI Closest point
    $poi = array('lat' => $_POST['poi']['lat'], 'lng' => $_POST['poi']['lng']);
    $closestVertex = MapUtils::getClosestVertex($poi['lat'], $poi['lng'], _closestPointRadius_search);

    // Converts to floats
    $NW_lat = floatval($bounds["NW_lat"]);
    $NW_lng = floatval($bounds["NW_lng"]);
    $SE_lat = floatval($bounds["SE_lat"]);
    $SE_lng = floatval($bounds["SE_lng"]);
    
    // Get objects
    if ($type === 'vertices'){

      $objects = MapUtils::getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $closestVertex['point']['lat'], $closestVertex['point']['lng']);
    
    } elseif ($type === 'edges'){

      $objects = MapUtils::getEdgesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $restrictToType, $closestVertex['point']['lat'], $closestVertex['point']['lng']);

    } elseif ($type === 'tree'){

      $objects = MapUtils::getVerticesAndChildrenIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $closestVertex['point']['lat'], $closestVertex['point']['lng']);

    }

    $result = array(
      'closest' => $closestVertex,
      'count'   => count($objects),
      $type     => $objects
    );

  } else {

    $result = array(
      'error' => 'Bad Request',
      'code'  => 1
    );
    
  }

  return $result;
}
