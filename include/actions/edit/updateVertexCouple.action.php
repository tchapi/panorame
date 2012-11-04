<?php


function doAction() {
  
  if (isset($_POST['start_lat']) && isset($_POST['start_lng']) && isset($_POST['start_id']) && isset($_POST['dest_lat']) && isset($_POST['dest_lng']) && isset($_POST['dest_id']) && isset($_POST['edge_id'])){

    $start_lat = floatval($_POST['start_lat']);
    $start_lng = floatval($_POST['start_lng']);
    $start_id = intval($_POST['start_id']);
    $start_alt = intval($_POST['start_alt']);

    $dest_lat = floatval($_POST['dest_lat']);
    $dest_lng = floatval($_POST['dest_lng']);
    $dest_id = intval($_POST['dest_id']);
    $dest_alt = intval($_POST['dest_alt']);

    $edge_id = intval($_POST['edge_id']);

    $result = DBUtils::updateVertexCouple($start_id, $start_lat, $start_lng, $start_alt, $dest_id, $dest_lat, $dest_lng, $dest_alt, $edge_id);
  
    if ($result == null || $result == false){

      $result = array(
        'error' => 'Error updating vertices',
        'code'  => 2
      );

    }

  } else {
  
    $result = array(
      'error' => 'Bad Request',
      'code'  => 1
    );
    
  }

  return $result;
}
