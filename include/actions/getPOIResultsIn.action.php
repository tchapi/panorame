<?php

function doAction() {

  if (isset($_POST['bounds']) && $_POST['bounds'] != null && isset($_POST['provider'])){
  
    $bounds = $_POST['bounds'];
    $provider = intval($_POST['provider']);
    $term = strtolower(trim($_POST['term']));

    $result = PoiService::getPOIResultsIn($provider, floatval($bounds["NW_lat"]), floatval($bounds["NW_lng"]), floatval($bounds["SE_lat"]), floatval($bounds["SE_lng"]), $term);

  } else {

    $result = array(
      'error' => 'Bad Request',
      'code'  => 1
    );
    
  }

  return $result;

}