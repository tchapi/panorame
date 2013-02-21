<?php


function doAction() {
  
  if (isset($_POST['edge_id'])){

    $edge_id = intval($_POST['edge_id']);

    $result = AdminUtils::deleteEdge($edge_id);
  
    if ($result == null || $result == false){

      $result = array(
        'error' => 'Error deleting edge',
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
