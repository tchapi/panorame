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

    $db = DBConnection::db();
    
    $getTypes_query = "SELECT `id`, `description`, `slug` from `types` where `editable` = 1;";
    
    $statement = $db->prepare($getMeansAndSpeeds_query);
    $exe = $statement->execute();

    if (!$exe || $exe == false ) {
      return false;
    } else {
      // Fetch the types
      $types = array();
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
          array_push($types, array('id' => $row['id'], 'description' => $row['description'], 'slug' => $row['slug']));
        }
      }

      return $types;

  }
  

  /*
   * Updating a vertex couple
   */
  public static function updateVertexCouple($start_id, $start_lat, $start_lng, $start_alt, $dest_id, $dest_lat, $dest_lng, $dest_alt, $edge_id){

    $db = DBConnection::db();
    
    $startExistsAlready = self::getClosestVertex($start_lat, $start_lng, _closestPointRadius_edit);
    $destExistsAlready = self::getClosestVertex($dest_lat, $dest_lng, _closestPointRadius_edit);

    if ($startExistsAlready == null || intval($startExistsAlready['id']) == $start_id) {
      // Updating start vertex
      $updateVertex1_query = sprintf("UPDATE `vertices` SET `point` = GeomFromText('point(%F %F)', 4326), `elevation` = %d WHERE `id` = %d;",
                $DBConnection->link->escape_string($start_lng),
                $DBConnection->link->escape_string($start_lat),
                $DBConnection->link->escape_string($start_alt),
                $DBConnection->link->escape_string($start_id));

      $updateVertex1_result = $DBConnection->link->query($updateVertex1_query);
    } else {
      // We must update all the edges that use this id with the new start_id
      $changeEdge_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, `from_id` = %d WHERE `from_id` = %d;",
              $DBConnection->link->escape_string(intval($startExistsAlready['id'])),
              $DBConnection->link->escape_string($start_id));

      $changeEdge_result = $DBConnection->link->query($changeEdge_query);

      $changeEdge_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, `to_id` = %d WHERE `to_id` = %d;",
              $DBConnection->link->escape_string(intval($startExistsAlready['id'])),
              $DBConnection->link->escape_string($start_id));

      $changeEdge_result = $DBConnection->link->query($changeEdge_query);
    }

    if ($destExistsAlready == null || intval($destExistsAlready['id']) == $dest_id) {
      // Updating destination vertex
      $updateVertex2_query = sprintf("UPDATE `vertices` SET `point` = GeomFromText('point(%F %F)', 4326), `elevation` = %d WHERE `id` = %d;",
                $DBConnection->link->escape_string($dest_lng),
                $DBConnection->link->escape_string($dest_lat),
                $DBConnection->link->escape_string($dest_alt),
                $DBConnection->link->escape_string($dest_id));

      $updateVertex2_result = $DBConnection->link->query($updateVertex2_query);
    } else {
      // We must update all the edges that use this id with the new dest_id
      $changeEdge_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, `from_id` = %d WHERE `from_id` = %d;",
              $DBConnection->link->escape_string(intval($destExistsAlready['id'])),
              $DBConnection->link->escape_string($dest_id));

      $changeEdge_result = $DBConnection->link->query($changeEdge_query);

      $changeEdge_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, `to_id` = %d WHERE `to_id` = %d;",
              $DBConnection->link->escape_string(intval($destExistsAlready['id'])),
              $DBConnection->link->escape_string($dest_id));

      $changeEdge_result = $DBConnection->link->query($changeEdge_query);
    }

    // We should tag the edges containing these points as 'dirty'
    $tagEdgesAsDirty_query = sprintf("UPDATE `edges` SET `is_dirty` = 1, WHERE `from_id` IN (%d,%d) OR `to_id` IN (%d,%d);",
              $DBConnection->link->escape_string($start_id),
              $DBConnection->link->escape_string($dest_id),
              $DBConnection->link->escape_string($start_id),
              $DBConnection->link->escape_string($dest_id));

    $result = $DBConnection->link->query($tagEdgesAsDirty_query);

    self::consolidate();

    return array(
      '1_updating_first_vertex' => $updateVertex1_result,
      '2_updating_second_vertex' => $updateVertex2_result,
      '3_tagging_edges_as_dirty' => $result
      );
  }

  /*
   * Deleting an edge
   */
  public static function deleteEdge($edge_id){

    $db = DBConnection::db();
    
    // Get the two vertices of the edge
    $selectVertices = sprintf("SELECT `from_id`, `to_id` FROM `edges` WHERE `id` = %d;",
                      $DBConnection->link->escape_string($edge_id));

    $vertices = $DBConnection->link->query($selectVertices);

    if ($vertices != false) {
      $row = $vertices->fetch_assoc();
      $from_id = intval($row['from_id']);
      $to_id = intval($row['to_id']);
    } else {
      return false;
    }
    
    // Deletes the edge
    $deleteEdge_query = sprintf("UPDATE `edges` SET `is_deleted` = 1, `is_dirty`= 1 WHERE `id` = %d;",
              $DBConnection->link->escape_string($edge_id));
    $delete_result = $DBConnection->link->query($deleteEdge_query);
    
    // Checks if any edge still uses vertex with id 'from_id'
    $fromIdCheck_query = sprintf("SELECT `id` FROM `edges` WHERE `is_deleted` = 0 AND (`from_id` = %d OR `to_id`= %d);",
              $DBConnection->link->escape_string($from_id),
              $DBConnection->link->escape_string($from_id));
    $fromIdCheck_result = $DBConnection->link->query($fromIdCheck_query);

    if ($fromIdCheck_result->num_rows === 0){

      // the vertex is not used anymore
      $deleteFromId_query = sprintf("UPDATE `vertices` SET `is_deleted` = 1 WHERE `id` = %d;",
            $DBConnection->link->escape_string($from_id));
      $deleteFromId_result = $DBConnection->link->query($deleteFromId_query);

    }

    // Checks if any edge still uses vertex with id 'to_id'
    $toIdCheck_query = sprintf("SELECT `id` FROM `edges` WHERE `is_deleted` = 0 AND (`from_id` = %d OR `to_id`= %d);",
              $DBConnection->link->escape_string($to_id),
              $DBConnection->link->escape_string($to_id));
    $toIdCheck_result = $DBConnection->link->query($toIdCheck_query);

    if ($toIdCheck_result->num_rows === 0){

      // the vertex is not used anymore
      $deleteToId_query = sprintf("UPDATE `vertices` SET `is_deleted` = 1 WHERE `id` = %d;",
            $DBConnection->link->escape_string($to_id));
      $deleteToId_result = $DBConnection->link->query($deleteToId_query);

    }

    self::consolidate();

    return array(
      '1_select_vertices' => ($vertices!==false)?true:false,
      '2_delete_edge' => $delete_result,
      '3_formId_check' => $fromIdCheck_result,
      '4_delete_fromId_vertex' => $deleteFromId_result,
      '5_toId_check' => $toIdCheck_result,
      '6_delete_toId_vertex' => $deleteToId_result
      );
  }

  /*
   * Cutting an edge into two edges
   */
  public static function cutEdge($start_id, $dest_id, $new_lat, $new_lng, $new_alt, $edge_id){

    $db = DBConnection::db();
    
    $newVertexAlreadyExists = self::getClosestVertex($new_lat, $new_lng, _closestPointRadius_edit);

    if ($newVertexAlreadyExists == null) {

      // Creates the new vertex and retrieves its id
      $newVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), %d);",
                $DBConnection->link->escape_string($new_lng),
                $DBConnection->link->escape_string($new_lat),
                $DBConnection->link->escape_string($new_alt));
      $newVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
              $DBConnection->link->escape_string($new_lng),
              $DBConnection->link->escape_string($new_lat));
      
      $DBConnection->link->query('BEGIN');
      $newVertex_insert_result = $DBConnection->link->query($newVertex_query);
      $newVertex_fetch_result  = $DBConnection->link->query($newVertex_query_fetch);
      $DBConnection->link->query('COMMIT');

      if( $newVertex_fetch_result !== false){
        $row = $newVertex_fetch_result->fetch_assoc();
        $newVertex_id = intval($row['id']);
      } else {
        return false;
      }
      
    } else {
      $newVertex_id = intval($newVertexAlreadyExists['id']);
    }
    
    // Gets the two previous vertices
    $getStartVertex_query = sprintf("SELECT Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` WHERE `id` = %d;",
                            $DBConnection->link->escape_string($start_id));
    $getStartVertex_result = $DBConnection->link->query($getStartVertex_query);
    
    if ($getStartVertex_result !== false){
      $row = $getStartVertex_result->fetch_assoc();
      $start_lat = floatval($row['lat']);
      $start_lng = floatval($row['lng']);
      $start_alt = intval($row['alt']);
    } else { return false; }

    $getDestVertex_query = sprintf("SELECT Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` WHERE `id` = %d;",
              $DBConnection->link->escape_string($dest_id));
    $getDestVertex_result = $DBConnection->link->query($getDestVertex_query);
    
    if ($getDestVertex_result !== false){
      $row = $getDestVertex_result->fetch_assoc();
      $dest_lat = floatval($row['lat']);
      $dest_lng = floatval($row['lng']);
      $dest_alt = intval($row['alt']);
    } else { return false; }
    
    
    // -------------------------------
    // Update edge from start ---> new 
    $startNew_distance = GeoUtils::haversine($start_lat, $start_lng, $new_lat, $new_lng);
    $startNew_grade = $new_alt - $start_alt;

    $startNew_query = sprintf("UPDATE `edges` SET `distance` = %F, `grade`= %d, `to_id`= %d, `is_dirty`= 0 WHERE `id` = %d;",
              $DBConnection->link->escape_string($startNew_distance),
              $DBConnection->link->escape_string($startNew_grade),
              $DBConnection->link->escape_string($newVertex_id),
              $DBConnection->link->escape_string($edge_id));

    $startNew_result = $DBConnection->link->query($startNew_query);

    // Retrieves type that we are going to use for the new edge
    $getType_query = sprintf("SELECT `type` FROM `edges` WHERE `id` = %d;",
                $DBConnection->link->escape_string($edge_id));
    $getType_result = $DBConnection->link->query($getType_query);
    
    if ($getType_result !== false){
      $row = $getType_result->fetch_assoc();
      $type = intval($row['type']);
    } else { return false; }
    
    // -------------------------------
    // Update edge from new ---> dest 
    $newDest_distance = GeoUtils::haversine($new_lat, $new_lng, $dest_lat, $dest_lng);
    $newDest_grade = $dest_alt - $new_alt;

    $newDest_query = sprintf("INSERT INTO `edges` (`from_id`, `to_id`, `distance`, `grade`, `type`, `is_dirty`) 
                 VALUES ('%d', '%d', '%F', '%d', '%d', 0);",
            $DBConnection->link->escape_string($newVertex_id),
            $DBConnection->link->escape_string($dest_id),
            $DBConnection->link->escape_string($newDest_distance),
            $DBConnection->link->escape_string($newDest_grade),
            $DBConnection->link->escape_string($type), 0);

    $newDest_result = $DBConnection->link->query($newDest_query);

    self::consolidate();

    return array(
      '1_insert_new_vertex' => $newVertex_insert_result,
      '2_fetch_new_vertex' => ($newVertex_fetch_result!==false)?true:false,
      '3_get_start_vertex' => ($getStartVertex_result!==false)?true:false,
      '4_get_dest_vertex' => ($getDestVertex_result!==false)?true:false,
      '5_start_new' => $startNew_result,
      '6_getType' => ($getType_result!==false)?true:false,
      '7_new_dest' => $newDest_result
      );
  }


  /*
   * Adding an edge
   */
  public static function addEdge($start_lat, $start_lng, $start_alt, $dest_lat, $dest_lng, $dest_alt, $type){

    $db = DBConnection::db();
    
    $startExistsAlready = self::getClosestVertex($start_lat, $start_lng, _closestPointRadius_edit);
    $destExistsAlready = self::getClosestVertex($dest_lat, $dest_lng, _closestPointRadius_edit);

    // Create start vertex if it doesn't exist
    if ($startExistsAlready == null) {
      $startVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), '%d');",
              $DBConnection->link->escape_string($start_lng),
              $DBConnection->link->escape_string($start_lat),
              $DBConnection->link->escape_string($start_alt));
      $startVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
              $DBConnection->link->escape_string($start_lng),
              $DBConnection->link->escape_string($start_lat));
              
      $DBConnection->link->query('BEGIN');
      $startVertex_insert_result = $DBConnection->link->query($startVertex_query);
      $startVertex_fetch_result  = $DBConnection->link->query($startVertex_query_fetch);
      $DBConnection->link->query('COMMIT');

      if ($startVertex_fetch_result !== false){
        $row = $startVertex_fetch_result->fetch_assoc();
        $startVertex_id = intval($row['id']);
      } else { return false; }
      
    } else {
      $startVertex_id = intval($startExistsAlready['id']);
    }

    // Create dest vertex if it doesn't exist
    if ($destExistsAlready == null) {

      $destVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), '%d');",
            $DBConnection->link->escape_string($dest_lng),
            $DBConnection->link->escape_string($dest_lat),
            $DBConnection->link->escape_string($dest_alt));
      $destVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
              $DBConnection->link->escape_string($dest_lng),
              $DBConnection->link->escape_string($dest_lat));
              
      $DBConnection->link->query('BEGIN');
      $destVertex_insert_result = $DBConnection->link->query($destVertex_query);
      $destVertex_fetch_result  = $DBConnection->link->query($destVertex_query_fetch);
      $DBConnection->link->query('COMMIT');

      if ($destVertex_fetch_result !== false){
        $row = $destVertex_fetch_result->fetch_assoc();
        $destVertex_id = intval($row['id']);
      } else { return false;}
      
    } else {
      $destVertex_id = intval($destExistsAlready['id']);
    }

    // Creates the edge
    $distance = GeoUtils::haversine($start_lat, $start_lng, $dest_lat, $dest_lng);
    $grade = $dest_alt - $start_alt;

    $createEdge_query = sprintf("INSERT INTO `edges` (`from_id`, `to_id`, `distance`, `grade`, `type`, `tagged_by`) 
                 VALUES ('%d', '%d', '%F', '%d', '%d', '%s');",
            $DBConnection->link->escape_string($startVertex_id),
            $DBConnection->link->escape_string($destVertex_id),
            $DBConnection->link->escape_string($distance),
            $DBConnection->link->escape_string($grade),
            $DBConnection->link->escape_string($type),
            $DBConnection->link->escape_string($_COOKIE["panorame_auth_name"]));

    // Executes the query
    $createEdge_result = $DBConnection->link->query($createEdge_query);

    self::consolidate();

    return array(
      '1_start_alreadyExisted' => !empty($startExistsAlready)?true:false,
      '2_dest_alreadyExisted' => !empty($destExistsAlready)?true:false,
      '3_start_insert' => isset($startVertex_insert_result) && $startVertex_insert_result,
      '4_start_fetch' => (isset($startVertex_fetch_result) && $startVertex_fetch_result!==false)?true:false,
      '5_dest_insert' => isset($destVertex_insert_result) && $destVertex_insert_result,
      '6_dest_fetch' => (isset($destVertex_fetch_result) && $destVertex_fetch_result!==false)?true:false,
      '7_create_edge' => $createEdge_result
    );

  }

  /*
   * Consolidate the database
   */
  public static function consolidate(){

    $db = DBConnection::db();
    
    // Finds orphan vertices and deletes them
    $findOrphans_query = "UPDATE `vertices` SET `is_deleted` = 1 WHERE `id` NOT IN 
              (SELECT `from_id` AS `id` FROM `edges` UNION
                SELECT `to_id` AS `id` FROM `edges`)";

    // Executes the query
    $findOrphans_result = $DBConnection->link->query($findOrphans_query);

    // Finds edges of zero distance
    $zeroDistance_query = "UPDATE `edges` SET `is_deleted` = 1 WHERE `from_id` = `to_id`;";

    // Executes the query
    $zeroDistance_result = $DBConnection->link->query($zeroDistance_query);

    // Find non-deleted edges linking to at least one deleted vertex
    $deletedInconsistencies_query = "UPDATE `edges` SET `is_deleted` = 1 WHERE `from_id` IN
                  (SELECT `id` FROM `vertices` WHERE `is_deleted` = 1) OR `to_id` IN
                  (SELECT `id` FROM `vertices` WHERE `is_deleted` = 1)";

    // Executes the query
    $deletedInconsistencies_result = $DBConnection->link->query($deletedInconsistencies_query);

    // Updates distances and grades
    $updateDistances_query = "SELECT consolidate() AS nb;";
    
    // Executes the query
    $updateDistances_result = $DBConnection->link->query($updateDistances_query);

    if ($updateDistances_result !== false) {
      $row = $updateDistances_result->fetch_assoc();
      $updateDistances_result_nb = $row['nb'];
    } else $updateDistances_result_nb = false;

    return array(
      '1_find_orphans' => $findOrphans_result,
      '2_zero_distance' => $zeroDistance_result,
      '3_delete_inconsistencies' => $deletedInconsistencies_result,
      '4_update_distances' => $updateDistances_result
    );
  }
