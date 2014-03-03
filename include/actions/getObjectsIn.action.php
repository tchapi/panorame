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
    $bounds = new Bounds($_POST['bounds']);

    // Get POI Closest point
    $closestVertex = MapUtils::getClosestVertex($_POST['poi']['lat'], $_POST['poi']['lng'], _closestPointRadius_search);

    // Get objects
    if ($type === 'vertices'){

      $objects = MapUtils::getVerticesIn($bounds, $closestVertex['point']);
    
    } elseif ($type === 'edges'){

      $objects = MapUtils::getEdgesIn($bounds, $closestVertex['point'], $restrictToType);

    } elseif ($type === 'tree'){

      $objects = MapUtils::getVerticesAndChildrenIn($bounds, $closestVertex['point']);

    }

    $result = array(
      'closest'  => $closestVertex?$closestVertex['point']->toArray():null,
      'distance' => $closestVertex?$closestVertex['distance']:null,
      'count'    => count($objects),
      $type      => $objects
    );

  } else {

    $result = array(
      'error' => 'Bad Request',
      'code'  => 1
    );
    
  }

  return $result;
}
