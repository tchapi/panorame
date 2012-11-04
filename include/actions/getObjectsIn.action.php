<?php

function doAction() {

  if (isset($_POST['bounds']) && $_POST['bounds'] != null && isset($_POST['poi']) && isset($_POST['type'])){

    switch ($_POST['type']){
      case 'vertices':
      case 'edges':
      case 'tree':
        $type = $_POST['type'];
        break;
      default:
        $type = 'vertices';
    }

    $bounds = $_POST['bounds'];

    // Get POI Closest point
    $poi = array('lat' => $_POST['poi']['lat'], 'lng' => $_POST['poi']['lng']);
    $closestVertex = DBUtils::getClosestVertex($poi['lat'], $poi['lng'], _closestPointRadius_search);

    // Get objects
    if ($type == 'vertices'){

      $objects = DBUtils::getVerticesIn(floatval($bounds["NW_lat"]), floatval($bounds["NW_lng"]), floatval($bounds["SE_lat"]), floatval($bounds["SE_lng"]), $closestVertex['point']['lat'], $closestVertex['point']['lng']);
    
    } elseif ($type == 'edges'){

      $objects = DBUtils::getEdgesIn(floatval($bounds["NW_lat"]), floatval($bounds["NW_lng"]), floatval($bounds["SE_lat"]), floatval($bounds["SE_lng"]), $closestVertex['point']['lat'], $closestVertex['point']['lng']);

    } elseif ($type == 'tree'){

      $objects = DBUtils::getVerticesAndChildrenIn(floatval($bounds["NW_lat"]), floatval($bounds["NW_lng"]), floatval($bounds["SE_lat"]), floatval($bounds["SE_lng"]), $closestVertex['point']['lat'], $closestVertex['point']['lng']);

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
