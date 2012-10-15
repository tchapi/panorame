<?php

  $app = '_BACKEND';
  include_once('../config.php');

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
    $closestVertex = Utils::getClosestVertex($poi['lat'], $poi['lng'], _closestPointRadius_search);

    // Get objects
    if ($type == 'vertices'){

      $objects = Utils::getVerticesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"], $closestVertex['point']['lat'], $closestVertex['point']['lng']);
    
    } elseif ($type == 'edges'){

      $objects = Utils::getEdgesIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"], $closestVertex['point']['lat'], $closestVertex['point']['lng']);

    } elseif ($type == 'tree'){

      $objects = Utils::getVerticesAndChildrenIn($bounds["NW_lat"], $bounds["NW_lng"], $bounds["SE_lat"], $bounds["SE_lng"], $closestVertex['point']['lat'], $closestVertex['point']['lng']);

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

  header('Content-type: application/json');
  print json_encode($result);
