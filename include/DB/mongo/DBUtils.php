<?php

class Utils {

	/*
	 * HAVERSINE function for distance between two points on Earth
	 */
	protected static function haversine($lat_1,$long_1,$lat_2,$long_2) {

		$sin_lat   = sin(deg2rad($lat_2  - $lat_1)  / 2.0);
		$sin2_lat  = $sin_lat * $sin_lat;
		 
		$sin_long  = sin(deg2rad($long_2 - $long_2) / 2.0);
		$sin2_long = $sin_long * $sin_long;
		 
		$cos_lat_1 = cos($lat_1);
		$cos_lat_2 = cos($lat_2);
		 
		$sqrt      = sqrt($sin2_lat + ($cos_lat_1 * $cos_lat_2 * $sin2_long));
		 
		$distance  = 2.0 * _earth_radius * asin($sqrt);
		 
		return $distance;

	}


  /*
   * GET The means availables and their respective speeds
   */
  public static function getMeansAndSpeeds(){

  	global $DBConnection;
    $db = $DBConnection->getDB();

    $means_raw = iterator_to_array($db->means->find()->sort(array("_id" => 1)));
    $means = array();

    // Fetch the means
    foreach($means_raw as $mean) {
      
    	$explorablesAsPHPArray = iterator_to_array($db->speeds->find(array('mean_id' => $mean['_id'])));

    	$explorables = array();

      foreach ($explorablesAsPHPArray as $explorable){
        $explorables[$explorable['type_id']] = array($explorable['flat_speed'], $explorable['grade_speed']);
      }

      array_push($means, array('id' => $mean['_id'], 'description' => $mean['description'], 'explorables' => $explorables));
    
    }

    return $means;

  }
    
  /*
   * Extends the given bounding box by _extendBoundsPointRadius, to allow for more smooth panning in the view
   * Allows to have more routes in the view as well, by capilarity
   */
  public static function extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng){

    $ratio = _extendBoundsPointRadius/_earth_radius;
    
    // Calculating new NW point
    $lat_rad = $NW_lat*pi()/180;
    $lng_rad = $NW_lng*pi()/180;
    $cos_lat_rad = cos($lat_rad);
    $sin_lat_rad = sin($lat_rad);

    $brng = (315/180)*pi(); // bearing is -45°
    $newNW_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
    $newNW_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($newNW_lat*pi()/180)))* 180 / pi();

    // Calculating new SE point
    $lat_rad = $SE_lat*pi()/180;
    $lng_rad = $SE_lng*pi()/180;
    $cos_lat_rad = cos($lat_rad);
    $sin_lat_rad = sin($lat_rad);

    $brng = 135/180*pi(); // Bearing is 135°
    $newSE_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
    $newSE_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($newSE_lat*pi()/180)))* 180 / pi();

    return array('NW_lat' => $newNW_lat, 'NW_lng' => $newNW_lng, 'SE_lat' => $newSE_lat, 'SE_lng' => $newSE_lng);

  }

  /*
   * Restricts a query for a vertex (of table v) for a bounding box
   * Takes the POI into account if existing
   */
  public static function restrictToBBox($array, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    if (isset($POI_lat) && $POI_lat != null && isset($POI_lng) && $POI_lng != null) {

    	// TODO !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
      // Poi should be included
    	$boundingBox = array(array($NW_lng, $SE_lat), array($SE_lng,  $NW_lat));
    	$restrict =  array('point' => array('within' => array( '$box' => $boundingBox )));
    	// TODO !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

    } else {

      // Just a bounds without the POI
    	$boundingBox = array(array($NW_lng, $SE_lat), array($SE_lng,  $NW_lat));
    	$restrict =  array('point' => array('within' => array( '$box' => $boundingBox )));
      
    }

    array_merge($array, $restrict);
    return $array;
  }

  /*
   * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
   */
  public static function getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

  	global $DBConnection;
    $db = $DBConnection->getDB();

    $restrictArray = self::restrictToBBox(array('is_deleted' => 0), $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
    
    $vertices = iterator_to_array($db->vertices->find($restrictArray));

    $verticesArray = array();

    foreach($vertices as $vertex){

    	$verticesArray[] = array(
	    	"id" => $vertex['_id'],
	    	"point" =>array(
	    		"lat" => $vertex['point'][1],
	    		"lng" => $vertex['point'][0]
	    		),
	    	"alt" => $vertex['alt']
	    	);
    }

    return $verticesArray;

  }


  /*
   * GET ALL THE EDGES in given bounds expressed as two LAT / LNG couples for NW and SE
   */
  public static function getEdgesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

  	global $DBConnection;
    $db = $DBConnection->getDB();

    $restrictArray = self::restrictToBBox(array(), $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
    
    $edges = iterator_to_array($db->edges_computed->find($restrictArray));

 		$edgesArray = array();

    foreach($edges as $edge){
    	$edge["id"] = $edge["_id"];
    	unset($edge["_id"]);
    	$edgesArray[] = $edge;
    }

    return $edgesArray;

  }

	/*
   * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
   * AND their 1th children
   */
  public static function getVerticesAndChildrenIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    // Extends the bounds
    $b = self::extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng);

    global $DBConnection;
    $db = $DBConnection->getDB();

    $restrictArray = self::restrictToBBox(array(), $b['NW_lat'], $b['NW_lng'], $b['SE_lat'], $b['SE_lng'], $POI_lat, $POI_lng);
    
    $reduceFrom = "function(obj, prev){
    	prev.children.push({\"id\": obj.dest.id, \"path_id\": obj._id, \"distance\": obj.distance, \"grade\": obj.grade, \"type\": obj.type, \"secable\": obj.secable });
    	prev.point = { \"lat\": obj.start.point.lat, \"lng\": obj.start.point.lng, \"alt\": obj.start.point.alt};
    }";

    $reduceTo = "function(obj, prev){
    	prev.children = null;
    	prev.point = { \"lat\": obj.dest.point.lat, \"lng\": obj.dest.point.lng, \"alt\": obj.dest.point.alt};
    }";

    $resultFrom = $db->edges_computed->group(array('start.id' => 1), array( 'children' => array()), $reduceFrom, array("condition" =>$restrictArray));
    $resultTo = $db->edges_computed->group(array('dest.id' => 1), array( 'children' => array()), $reduceTo, array("condition" =>$restrictArray));
    
    $edges = $resultFrom['retval'];
    $edgesLeafVertices = $resultTo['retval'];
    
    $edgesArray = array();
    
    foreach($edges as $edge){
    
    	$edgesArray[$edge['start.id']] = $edge;
    	unset($edgesArray[$edge['start.id']]['start.id']);
    
    }
    
    foreach($edgesLeafVertices as $edgeLeaf){
    
    	if (!isset($edgesArray[$edgeLeaf['dest.id']])) {
    		$edgesArray[$edgeLeaf['dest.id']] = $edgeLeaf;
    		unset($edgesArray[$edgeLeaf['dest.id']]['dest.id']);
    	}
    
    }
    
    return $edgesArray;
	}

	/*
   * GET The closest vertex from a given lat / lng couple within a x meters radius
   */
  public static function getClosestVertex($lat, $lng, $radius_in_m){

  	global $DBConnection;
    $db = $DBConnection->getDB();

    $closest = current(iterator_to_array($db->vertices->find(array('is_deleted' => 0, 'point' => array( '$nearSphere' => array( floatval($lng), floatval($lat)), '$maxDistance' => $radius_in_m/_earth_radius)))->limit(1)));

    if ($closest == false){
    	return null;
    } else {
	    return array(
	    	"id" => $closest['_id'],
	    	"point" =>array(
	    		"lat" => $closest['point'][1],
	    		"lng" => $closest['point'][0]
	    		),
	    	"alt" => $closest['alt'], 
	    	"distance" =>  self::haversine($lat, $lng, $closest['point'][1], $closest['point'][0])

	    	);
	  }

	}

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
			"type" => $type 
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
                  'point' => array(
                    'lat' => floatval($startVertex["point"][1]),
                    'lng' => floatval($startVertex["point"][0]),
                    'alt' => intval($startVertex["alt"])
                  )
                ),
                'dest' => array(
                  'id' => intval($destVertex["_id"]),
                  'point' => array(
                    'lat' => floatval($destVertex["point"][1]),
                    'lng' => floatval($destVertex["point"][0]),
                    'alt' => intval($destVertex["alt"])
                  )
                ),
                'distance' => floatval(self::haversine($startVertex["point"][1], $startVertex["point"][0], $destVertex["point"][1], $destVertex["point"][0])),
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
  
}
