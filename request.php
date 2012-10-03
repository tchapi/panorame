<?php

$app = '_FRONTEND';
include('include/config.php');

/*
 * FRAMEWORK = API Provider
 */
if (isset($_GET['framework'])){

  switch ($_GET['framework']) {
    case 'mapquest':
    case 'gmaps':
    case 'bing':
    case 'openlayers':
      $framework = $_GET['framework'];
      break;
    default:
      $framework = "gmaps";
      break;
  }

}

/*
 * PROVIDER = Tiles Provider
 */ 
if (isset($_GET['provider'])){

  switch ($_GET['provider']) {
    case 'gmaps-terrain':
    case 'gmaps-road':
    case 'gmaps-hybrid':
      if ($_GET['framework'] == 'openlayers') $addedScript = "<script src='http://maps.google.com/maps/api/js?v=3.7&sensor=false'></script>";
    case 'bing-road':
    case 'bing-hybrid':
    case 'osmaps':
    case 'mapquest':
      $provider = $_GET['provider'];
      break;
  }

}

/*
 * EDIT Mode
 */ 

if (isset($_GET['edit']) && $_GET['edit'] == 1) {

  $framework = 'gmaps';
  $provider  = 'gmaps-road';
  $addedScript == '';

  $editMode = true;

}