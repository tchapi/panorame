<?php

class AdminUtils {

/* ======================================================================== *
 *                                                                          *
 *                            ADMIN FUNCTIONS                               *
 *                                                                          *
 * ======================================================================== */ 

  /*
   * GET The types availables
   */
  public static function getTypes(){

    global $DBConnection;
    $db = $DBConnection->getDB();

    $types_raw = iterator_to_array($db->types->find(array('editable' => 1))->sort(array('_id' => 1)));
    $types = array();

    // Fetch the means
    foreach($types_raw as $type) {
       
       array_push($types, array('id' => $type['_id'], 'description' => $type['description'], 'slug' => $type['slug']));
      
    }

    return $types;

  }

  /*
   * Updating a vertex couple
   */
  public static function updateVertexCouple($start_id, $start_lat, $start_lng, $start_alt, $dest_id, $dest_lat, $dest_lng, $dest_alt, $edge_id){

    $startExistsAlready = self::getClosestVertex($start_lat, $start_lng, _closestPointRadius_edit);
    $destExistsAlready = self::getClosestVertex($dest_lat, $dest_lng, _closestPointRadius_edit);

    global $DBConnection;
    $db = $DBConnection->getDB();

    if ($startExistsAlready == null || intval($startExistsAlready['id']) == $start_id) {
      $db->vertices->update(array('_id' => $start_id), array( '$set' => array( 'point' => array($start_lng, $start_lat), 'alt' => $start_alt )));
    } else {

      $db->edges->update(array('from_id' => $start_id), array('$set' => array( 'from_id' => intval($startExistsAlready['id']))));
      $db->edges->update(array('to_id' => $start_id), array('$set' => array( 'to_id' => intval($startExistsAlready['id']))));

    }

    if ($destExistsAlready == null || intval($destExistsAlready['id']) == $dest_id) {
      $db->vertices->update(array('_id' => $dest_id),array( '$set' => array( 'point' => array($dest_lng, $dest_lat), 'alt' => $dest_alt )));
    } else {

      $db->edges->update(array('from_id' => $dest_id), array('$set' => array( 'from_id' => intval($destExistsAlready['id']))));
      $db->edges->update(array('to_id' => $dest_id), array('$set' => array( 'to_id' => intval($destExistsAlready['id']))));

    }

    self::consolidate();

    return array(
      '1_start_alreadyExisted' => $startExistsAlready,
      '2_dest_alreadyExisted' => $destExistsAlready
      );
  }

  /*
   * Adding an edge
   */
  public static function addEdge($start_lat, $start_lng, $start_alt, $dest_lat, $dest_lng, $dest_alt, $type){

    $startExistsAlready = self::getClosestVertex($start_lat, $start_lng, _closestPointRadius_edit);
    $destExistsAlready = self::getClosestVertex($dest_lat, $dest_lng, _closestPointRadius_edit);

    global $DBConnection;
    $db = $DBConnection->getDB();

    // Create start vertex if it doesn't exist
    if ($startExistsAlready == null) {

      // LAST ID
      $lastVertexIdElement = current(iterator_to_array($db->vertices->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));
      $startVertex_id = intval($lastVertexIdElement['_id'] + 1);

      $db->vertices->insert(array(
          "_id" => $startVertex_id,
          "is_deleted" => 0,
          "point" => array($start_lng, $start_lat),
          "alt" => $start_alt 
          
        ));

    } else {
      $startVertex_id = intval($startExistsAlready['id']);
    }

    // Create dest vertex if it doesn't exist
    if ($destExistsAlready == null) {

      // LAST ID
      $lastVertexIdElement = current(iterator_to_array($db->vertices->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));
      $destVertex_id = intval($lastVertexIdElement['_id'] + 1);

      $db->vertices->insert(array(
          "_id" => $destVertex_id,
          "is_deleted" => 0,
          "point" => array($dest_lng, $dest_lat),
          "alt" => $dest_alt 
        ));
      
    } else {
      $destVertex_id = intval($destExistsAlready['id']);
    }

    // LAST ID
    $lastEdgeIdElement = current(iterator_to_array($db->edges->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));
    $nextEdge_id = intval($lastEdgeIdElement['_id'] + 1);

    $db->edges->insert(array(
      "_id" => $nextEdge_id,
      "is_deleted" => 0,
      "from_id" => $startVertex_id,
      "to_id" => $destVertex_id,
      "type" => $type,
      "tagged_by" => $_COOKIE["panorame_auth_name"]
      ));

    self::consolidate();

    return array(
      '1_start_alreadyExisted' => $startExistsAlready,
      '2_dest_alreadyExisted' => $destExistsAlready,
      '3_start_insert' => $startVertex_id,
      '4_dest_insert' => $destVertex_id,
      '5_create_edge' => $nextEdge_id
    );

  }

  /*
   * Deleting an edge
   */
  public static function deleteEdge($edge_id){

    global $DBConnection;
    $db = $DBConnection->getDB();

    $result = $db->edges->update(array('_id' => $edge_id), array( '$set' => array( 'is_deleted' => 1)));

    self::consolidate();

    return array(
      '1_delete_edge' => $result
      );
  }


  /*
   * Cutting an edge into two edges
   */
  public static function cutEdge($start_id, $dest_id, $new_lat, $new_lng, $new_alt, $edge_id){

    global $DBConnection;
    $db = $DBConnection->getDB();

    $newVertexAlreadyExists = self::getClosestVertex($new_lat, $new_lng, _closestPointRadius_edit);

    if ($newVertexAlreadyExists == null) {

      // Creates the new vertex
      // LAST ID
      $lastVertexIdElement = current(iterator_to_array($db->vertices->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));
      $newVertex_id = intval($lastVertexIdElement['_id'] + 1);

      $db->vertices->insert(array(
          "_id" => $newVertex_id,
          "is_deleted" => 0,
          "point" => array($new_lng, $new_lat),
          "alt" => $new_alt 
        ));
      
    } else {
      $newVertex_id = intval($newVertexAlreadyExists['id']);
    }
    
    // -------------------------------
    // Update edge from start ---> new 
    $db->edges->update(array('_id' => $edge_id), array('$set' => array( 'to_id' => $newVertex_id)));

    // Retrieves the type for the new edge
    $edgeForType = current(iterator_to_array($db->edges->find(array('is_deleted' => 0, '_id' => $edge_id), array('type' => 1))->limit(1)));
    var_dump($edgeForType);
    // -------------------------------
    // Update edge from new ---> dest 
    // LAST ID
    $lastEdgeIdElement = current(iterator_to_array($db->edges->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));
    $newEdge_id = intval($lastEdgeIdElement['_id'] + 1);
var_dump($newEdge_id);
    $db->edges->insert(array(
        '_id' => $newEdge_id,
        'is_deleted' => 0,
        'from_id' => $newVertex_id,
        'to_id' => $dest_id,
        'type' => $edgeForType['type']
      ));

    self::consolidate();

    return array(
      '1_new_already_exists' => $newVertexAlreadyExists
      );
  }


  /*
   * Consolidate the database
   */
  public static function consolidate(){

    global $DBConnection;
    $db = $DBConnection->getDB();

    // Find all edges to compute them
    $edges = iterator_to_array($db->edges->find(array('is_deleted' => 0)));

    // Fetch the edges
    $edgesArray = array();

    $db->edges_computed->remove(array());
    $counter = 0;

    foreach ($edges as $edge) {
    
      $startVertex = current(iterator_to_array($db->vertices->find(array('is_deleted' => 0, '_id' => $edge['from_id']))->limit(1)));
      $destVertex = current(iterator_to_array($db->vertices->find(array('is_deleted' => 0, '_id' => $edge['to_id']))->limit(1)));

      if ($startVertex == null || $destVertex == null) continue;

      $type = current(iterator_to_array($db->types->find(array( '_id' => $edge['type']))->limit(1)));

      if (intval($startVertex["_id"]) != intval($destVertex["_id"])) {

        $db->edges_computed->insert(array(
                '_id' => intval($edge["_id"]), 
                'start' => array(
                  'id' => intval($startVertex["_id"]),
                  'point' => $startVertex["point"],
                  'alt' => intval($startVertex["alt"])
                ),
                'dest' => array(
                  'id' => intval($destVertex["_id"]),
                  'point' => $destVertex["point"],
                  'alt' => intval($destVertex["alt"])
                ),
                'distance' => floatval(GeoUtils::haversine($startVertex["point"][1], $startVertex["point"][0], $destVertex["point"][1], $destVertex["point"][0])),
                'grade' => intval($destVertex["alt"] - $startVertex["alt"]),
                'type' => intval($edge['type']),
                'secable' => intval($type['secable'])
              ));
      
        $counter += 1;
      }

    }

    // Finds orphan vertices and deletes them
    $edges = iterator_to_array($db->edges->find(array('is_deleted' => 0), array('from_id' => 1, 'to_id' => 1)));
    $ninVertices = array();

    foreach ($edges as $edge){

      $ninVertices[] = $edge['to_id'];
      $ninVertices[] = $edge['from_id'];

    }
    
    $result = $db->vertices->update(array( '_id' => array( '$nin' => $ninVertices)), array( '$set' => array( 'is_deleted' => 1)), array('multiple' => true));

    return array(
      'nb_inserted' => $counter
    );
  }
