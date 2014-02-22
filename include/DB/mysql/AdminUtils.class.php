<?php

class AdminUtils extends MapUtils {

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
    
    $statement = $db->prepare($getTypes_query);
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

    if ( is_null($startExistsAlready) || intval($startExistsAlready['id']) === intval($start_id) ) {
      
      // Updating start vertex
      $updateVertex1_query = sprintf("UPDATE `vertices` SET `point` = GeomFromText('point(%F %F)', 4326), `elevation` = :elevation WHERE `id` = :id;",
        floatval($start_lng),
        floatval($start_lat));

      $statement = $db->prepare($updateVertex1_query);
        $statement->bindParam(':elevation', $start_alt, PDO::PARAM_INT);
        $statement->bindParam(':id', $start_id, PDO::PARAM_INT);

      $updateVertex1_result = $statement->execute();

    } else {

      // We must update all the edges that use this id with the new start_id
      $changeEdge_query = "UPDATE `edges` SET `is_dirty` = 1, `from_id` = :from_id WHERE `from_id` = :start_id;";
      
      $statement = $db->prepare($changeEdge_query);
        $statement->bindParam(':from_id', $startExistsAlready['id'], PDO::PARAM_INT);
        $statement->bindParam(':start_id', $start_id, PDO::PARAM_INT);

      $changeEdge_result = $statement->execute();

      $changeEdge_query = "UPDATE `edges` SET `is_dirty` = 1, `to_id` = :to_id WHERE `to_id` = :start_id;";
      
      $statement = $db->prepare($changeEdge_query);
        $statement->bindParam(':to_id', $startExistsAlready['id'], PDO::PARAM_INT);
        $statement->bindParam(':start_id', $start_id, PDO::PARAM_INT);

      $changeEdge_result = $statement->execute();

    }

    if ( is_null($destExistsAlready) || intval($destExistsAlready['id']) === intval($dest_id) ) {

      // Updating destination vertex
      $updateVertex2_query = sprintf("UPDATE `vertices` SET `point` = GeomFromText('point(%F %F)', 4326), `elevation` = :elevation WHERE `id` = :id;",
                floatval($dest_lng),
                floatval($dest_lat));
      
      $statement = $db->prepare($updateVertex2_query);
        $statement->bindParam(':elevation', $dest_alt, PDO::PARAM_INT);
        $statement->bindParam(':id', $dest_id, PDO::PARAM_INT);

      $updateVertex2_result = $statement->execute();

    } else {
      
      // We must update all the edges that use this id with the new dest_id
      $changeEdge_query = "UPDATE `edges` SET `is_dirty` = 1, `from_id` = :from_id WHERE `from_id` = :dest_id;";
      
      $statement = $db->prepare($changeEdge_query);
        $statement->bindParam(':from_id', $destExistsAlready['id'], PDO::PARAM_INT);
        $statement->bindParam(':dest_id', $dest_id, PDO::PARAM_INT);

      $changeEdge_result = $statement->execute();


      $changeEdge_query = "UPDATE `edges` SET `is_dirty` = 1, `to_id` = :to_id WHERE `to_id` = :dest_id;";
      
      $statement = $db->prepare($changeEdge_query);
        $statement->bindParam(':to_id', $destExistsAlready['id'], PDO::PARAM_INT);
        $statement->bindParam(':dest_id', $dest_id, PDO::PARAM_INT);

      $changeEdge_result = $statement->execute();

    }

    // We should tag the edges containing these points as 'dirty'
    $tagEdgesAsDirty_query = "UPDATE `edges` SET `is_dirty` = 1, WHERE `from_id` IN (:start_id,:dest_id) OR `to_id` IN (:start_id,:dest_id);";

    $statement = $db->prepare($tagEdgesAsDirty_query);
        $statement->bindParam(':start_id', $start_id, PDO::PARAM_INT);
        $statement->bindParam(':dest_id', $dest_id, PDO::PARAM_INT);

    $result = $statement->execute();

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
    $selectVertices = "SELECT `from_id`, `to_id` FROM `edges` WHERE `id` = :edge_id;";

    $statement = $db->prepare($selectVertices);
        $statement->bindParam(':edge_id', $edge_id, PDO::PARAM_INT);

    $vertices = $statement->execute();

    if ($vertices != false) {

      $row = $statement->fetch(PDO::FETCH_ASSOC);
      $from_id = intval($row['from_id']);
      $to_id = intval($row['to_id']);

    } else {

      return false;

    }
    
    // Deletes the edge
    $deleteEdge_query = "UPDATE `edges` SET `is_deleted` = 1, `is_dirty`= 1 WHERE `id` = :edge_id;";
    
    $statement = $db->prepare($deleteEdge_query);
        $statement->bindParam(':edge_id', $edge_id, PDO::PARAM_INT);

    $delete_result = $statement->execute();
    
    // Checks if any edge still uses vertex with id 'from_id'
    $fromIdCheck_query = "SELECT `id` FROM `edges` WHERE `is_deleted` = 0 AND (`from_id` = :from_id OR `to_id`= :from_id);";

    $statement = $db->prepare($fromIdCheck_query);
        $statement->bindParam(':from_id', $from_id, PDO::PARAM_INT);

    $fromIdCheck_result = $statement->execute();

    if ($statement->rowCount() === 0){

      // the vertex is not used anymore
      $deleteFromId_query = "UPDATE `vertices` SET `is_deleted` = 1 WHERE `id` = :from_id;";

      $statement = $db->prepare($deleteFromId_query);
        $statement->bindParam(':from_id', $from_id, PDO::PARAM_INT);

      $deleteFromId_result = $statement->execute();

    }

    // Checks if any edge still uses vertex with id 'to_id'
    $toIdCheck_query = "SELECT `id` FROM `edges` WHERE `is_deleted` = 0 AND (`from_id` = :to_id OR `to_id`= :to_id);";

    $statement = $db->prepare($toIdCheck_query);
        $statement->bindParam(':to_id', $to_id, PDO::PARAM_INT);

    $toIdCheck_result = $statement->execute();

    if ($statement->rowCount() === 0){

      // the vertex is not used anymore
      $deleteToId_query = "UPDATE `vertices` SET `is_deleted` = 1 WHERE `id` = :to_id;";

      $statement = $db->prepare($deleteToId_query);
        $statement->bindParam(':to_id', $to_id, PDO::PARAM_INT);

      $deleteToId_result = $statement->execute();

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

    if ( is_null($newVertexAlreadyExists) ) {

      // Creates the new vertex and retrieves its id
      $newVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`, `is_deleted`) VALUES (GeomFromText('point(%F %F)', 4326), :elevation, 0);",
                floatval($new_lng),
                floatval($new_lat));
      
      $statement_1 = $db->prepare($newVertex_query);
      $statement_1->bindParam(':elevation', $new_alt, PDO::PARAM_INT);

      $newVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
                floatval($new_lng),
                floatval($new_lat));
      
      $statement_2 = $db->prepare($newVertex_query_fetch);

      $db->beginTransaction();
        $newVertex_insert_result = $statement_1->execute();
        $newVertex_fetch_result  = $statement_2->execute();
      $db->commit();

      if( $newVertex_fetch_result !== false){
        $row = $statement_2->fetch(PDO::FETCH_ASSOC);
        $newVertex_id = intval($row['id']);
      } else {
        return false;
      }
      
    } else {
      $newVertex_insert_result = false;
      $newVertex_fetch_result = false;
      $newVertex_id = intval($newVertexAlreadyExists['id']);
    }
    
    // Gets the two previous vertices
    $getStartVertex_query = "SELECT Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` WHERE `id` = :start_id;";
    
    $statement = $db->prepare($getStartVertex_query);
      $statement->bindParam(':start_id', $start_id, PDO::PARAM_INT);

    $getStartVertex_result = $statement->execute();
    
    if ($getStartVertex_result !== false){
      $row = $statement->fetch(PDO::FETCH_ASSOC);
      $start_lat = floatval($row['lat']);
      $start_lng = floatval($row['lng']);
      $start_alt = intval($row['alt']);
    } else { return false; }

    $getDestVertex_query = "SELECT Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `vertices` WHERE `id` = :dest_id;";

    $statement = $db->prepare($getDestVertex_query);
      $statement->bindParam(':dest_id', $dest_id, PDO::PARAM_INT);

    $getDestVertex_result = $statement->execute();

    if ($getDestVertex_result !== false){
      $row = $statement->fetch(PDO::FETCH_ASSOC);
      $dest_lat = floatval($row['lat']);
      $dest_lng = floatval($row['lng']);
      $dest_alt = intval($row['alt']);
    } else { 
      return false; 
    }
    
    
    // -------------------------------
    // Update edge from start ---> new 
    $startNew_distance = GeoUtils::haversine($start_lat, $start_lng, $new_lat, $new_lng);
    $startNew_grade = $new_alt - $start_alt;

    $startNew_query = "UPDATE `edges` SET `distance` = :distance, `grade`= :grade, `to_id`= :to_id, `is_dirty`= 0 WHERE `id` = :edge_id;";

    $statement = $db->prepare($startNew_query);
      $statement->bindParam(':distance', $startNew_distance, PDO::PARAM_INT);
      $statement->bindParam(':grade', $startNew_grade, PDO::PARAM_INT);
      $statement->bindParam(':to_id', $newVertex_id, PDO::PARAM_INT);
      $statement->bindParam(':edge_id', $edge_id, PDO::PARAM_INT);

    $startNew_result = $statement->execute();

    // Retrieves type that we are going to use for the new edge
    $getType_query = "SELECT `type` FROM `edges` WHERE `id` = :edge_id;";

    $statement = $db->prepare($getType_query);
      $statement->bindParam(':edge_id', $edge_id, PDO::PARAM_INT);

    $getType_result = $statement->execute();
    
    if ($getType_result !== false){
      $row = $statement->fetch(PDO::FETCH_ASSOC);
      $type = intval($row['type']);
    } else { 
      return false; 
    }
    
    // -------------------------------
    // Update edge from new ---> dest 
    $newDest_distance = GeoUtils::haversine($new_lat, $new_lng, $dest_lat, $dest_lng);
    $newDest_grade = $dest_alt - $new_alt;

    $newDest_query = "INSERT INTO `edges` (`from_id`, `to_id`, `distance`, `grade`, `type`, `is_dirty`, `is_deleted`) 
                 VALUES (:from_id, :to_id, :distance, :grade, :type, 0, 0);";

    $statement = $db->prepare($newDest_query);
      $statement->bindParam(':from_id', $newVertex_id, PDO::PARAM_INT);
      $statement->bindParam(':to_id', $dest_id, PDO::PARAM_INT);
      $statement->bindParam(':distance', $newDest_distance, PDO::PARAM_STR);
      $statement->bindParam(':grade', $newDest_grade, PDO::PARAM_INT);
      $statement->bindParam(':type', $type, PDO::PARAM_INT);

    $newDest_result = $statement->execute();
    
    self::consolidate();

    return array(
      '1_insert_new_vertex' => ($newVertex_insert_result!==false)?true:false,
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
    if ($startExistsAlready === null) {
      $startVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`, `is_deleted`) VALUES (GeomFromText('point(%F %F)', 4326), :elevation, 0);",
              floatval($start_lng),
              floatval($start_lat));

      $statement_1 = $db->prepare($startVertex_query);
      $statement_1->bindParam(':elevation', $start_alt, PDO::PARAM_INT);

      $startVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
              floatval($start_lng),
              floatval($start_lat));
      
      $statement_2 = $db->prepare($startVertex_query_fetch);

      $startVertex_insert_result = $statement_1->execute();
      $startVertex_fetch_result  = $statement_2->execute();

      if ($startVertex_fetch_result !== false){
        $row = $statement_2->fetch(PDO::FETCH_ASSOC);
        $startVertex_id = intval($row['id']);
      } else { 
        return false; 
      }
      
    } else {
      $startVertex_id = intval($startExistsAlready['id']);
    }

    // Create dest vertex if it doesn't exist
    if ($destExistsAlready === null) {

      $destVertex_query = sprintf("INSERT INTO `vertices` (`point`, `elevation`, `is_deleted`) VALUES (GeomFromText('point(%F %F)', 4326), :elevation, 0);",
            floatval($dest_lng),
            floatval($dest_lat));

      $statement_1 = $db->prepare($destVertex_query);
      $statement_1->bindParam(':elevation', $dest_alt, PDO::PARAM_INT);

      $destVertex_query_fetch = sprintf("SELECT `id` FROM `vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
              floatval($dest_lng),
              floatval($dest_lat));
              
      $statement_2 = $db->prepare($destVertex_query_fetch);

      $db->beginTransaction();
        $destVertex_insert_result = $statement_1->execute();
        $destVertex_fetch_result  = $statement_2->execute();
      $db->commit();

      if ($destVertex_fetch_result !== false){
        $row = $statement_2->fetch(PDO::FETCH_ASSOC);
        $destVertex_id = intval($row['id']);
      } else { 
        return false;
      }
      
    } else {
      $destVertex_id = intval($destExistsAlready['id']);
    }

    // Creates the edge
    $distance = GeoUtils::haversine($start_lat, $start_lng, $dest_lat, $dest_lng);
    $grade = $dest_alt - $start_alt;

    $createEdge_query = "INSERT INTO `edges` (`from_id`, `to_id`, `distance`, `grade`, `type`, `tagged_by`, `is_dirty`, `is_deleted`) 
                 VALUES (:from_id, :to_id, :distance, :grade, :type, :tagged_by, 0, 0);";

    $statement = $db->prepare($createEdge_query);
      $statement->bindParam(':from_id', $startVertex_id, PDO::PARAM_INT);
      $statement->bindParam(':to_id', $destVertex_id, PDO::PARAM_INT);
      $statement->bindParam(':distance', $distance, PDO::PARAM_STR);
      $statement->bindParam(':grade', $grade, PDO::PARAM_INT);
      $statement->bindParam(':type', $type, PDO::PARAM_INT);
      $statement->bindParam(':tagged_by', $_COOKIE["panorame_auth_name"], PDO::PARAM_STR);

    // Executes the query
    $createEdge_result = $statement->execute();

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
    $findOrphans_result = $db->prepare($findOrphans_query)->execute();

    // Finds edges of zero distance
    $zeroDistance_query = "UPDATE `edges` SET `is_deleted` = 1 WHERE `from_id` = `to_id`;";

    // Executes the query
    $zeroDistance_result = $db->prepare($zeroDistance_query)->execute();

    // Find non-deleted edges linking to at least one deleted vertex
    $deletedInconsistencies_query = "UPDATE `edges` SET `is_deleted` = 1 WHERE `from_id` IN
                  (SELECT `id` FROM `vertices` WHERE `is_deleted` = 1) OR `to_id` IN
                  (SELECT `id` FROM `vertices` WHERE `is_deleted` = 1)";

    // Executes the query
    $deletedInconsistencies_result = $db->prepare($deletedInconsistencies_query)->execute();

    // Updates distances and grades
    $updateDistances_query = "SELECT consolidate() AS nb;";
    $statement = $db->prepare($updateDistances_query);
    
    // Executes the query
    $updateDistances_result = $statement->execute();

    if ($updateDistances_result !== false) {
      $row = $statement->fetch(PDO::FETCH_ASSOC);
      $updateDistances_result_nb = intval($row['nb']);
    } else {
      $updateDistances_result_nb = false;
    }

    return array(
      '1_find_orphans' => $findOrphans_result,
      '2_zero_distance' => $zeroDistance_result,
      '3_delete_inconsistencies' => $deletedInconsistencies_result,
      '4_update_distances' => $updateDistances_result
    );
  }

}
