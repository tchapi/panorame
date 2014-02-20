<?php

class MapUtils implements MapUtilsInterface {

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

      array_push($means, array('id' => $mean['_id'], 'slug' => $mean['slug'], 'description' => $mean['description'], 'explorables' => $explorables));
    
    }

    return $means;

  }

  /*
   * Restricts a query for a vertex (of table v) for a bounding box
   * Takes the POI into account if existing
   */
  public static function restrictToBBox($array, $type, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

    if (isset($POI_lat) && $POI_lat != null && isset($POI_lng) && $POI_lng != null) {

      // Poi should be included
    	$bbox = GeoUtils::calculateBBox(array($NW_lat, $SE_lat, $POI_lat), array($NW_lng, $SE_lng, $POI_lng));

    } else {

      // Just a bounds without the POI
      $bbox = GeoUtils::calculateBBox(array($NW_lat, $SE_lat), array($NW_lng, $SE_lng));
      
    }

    $boundingBox = array(array($bbox['NW_lng'], $bbox['SE_lat']), array($bbox['SE_lng'],  $bbox['NW_lat']));

    if ($type == 'vertices') {
      $restrict =  array('point' => array('$within' => array( '$box' => $boundingBox )));
    } else {
      $restrict =  array('start.point' => array('$within' => array( '$box' => $boundingBox )));
    }

    return array_merge($array, $restrict);
  }

  /*
   * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
   */
  public static function getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

  	global $DBConnection;
    $db = $DBConnection->getDB();

    $restrictArray = self::restrictToBBox(array('is_deleted' => 0), 'vertices', $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
    
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
  public static function getEdgesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $restrictToType, $POI_lat, $POI_lng){

  	global $DBConnection;
    $db = $DBConnection->getDB();

    // Extends the bounds
    $b = GeoUtils::extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng, null);

    // Restrict to a type ?
    if ($restrictToType == null || $restrictToType == 0) {
      $restrictArray = array();
    } else {
      $restrictArray = array('type' => $restrictToType);
    }

    $restrictArray = self::restrictToBBox($restrictArray, 'edges', $b['NW_lat'], $b['NW_lng'], $b['SE_lat'], $b['SE_lng'], $POI_lat, $POI_lng);
    
    $edges = iterator_to_array($db->edges_computed->find($restrictArray));

 		$edgesArray = array();

    foreach($edges as $edge){
    	$edge["id"] = $edge["_id"];
      unset($edge["_id"]);

      $lat = $edge["start"]["point"][1];
      $lng = $edge["start"]["point"][0];
      unset($edge["start"]["point"]);
      $edge["start"]["point"] = array("lat" => $lat, "lng" => $lng);

      $edge["start"]["point"]["alt"] = $edge["start"]["alt"];
      unset($edge["start"]["alt"]);

      $lat = $edge["dest"]["point"][1];
      $lng = $edge["dest"]["point"][0];
      unset($edge["dest"]["point"]);
      $edge["dest"]["point"] = array("lat" => $lat, "lng" => $lng);

      $edge["dest"]["point"]["alt"] = $edge["dest"]["alt"];
      unset($edge["dest"]["alt"]);

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
    $b = GeoUtils::extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng, null);

    global $DBConnection;
    $db = $DBConnection->getDB();

    $restrictArray = self::restrictToBBox(array(), 'edges', $b['NW_lat'], $b['NW_lng'], $b['SE_lat'], $b['SE_lng'], $POI_lat, $POI_lng);
    
    $reduceFrom = "function(obj, prev){
    	prev.children.push({\"id\": obj.dest.id, \"path_id\": obj._id, \"distance\": obj.distance, \"grade\": obj.grade, \"type\": obj.type, \"secable\": obj.secable });
    	prev.point = { \"lat\": obj.start.point[1], \"lng\": obj.start.point[0], \"alt\": obj.start.point.alt};
    }";

    $reduceTo = "function(obj, prev){
    	prev.children = null;
    	prev.point = { \"lat\": obj.dest.point[1], \"lng\": obj.dest.point[0], \"alt\": obj.dest.point.alt};
    }";

    $resultFrom = $db->edges_computed->group(array('start.id' => 1), array( 'children' => array()), $reduceFrom, array("condition" => $restrictArray));
    $resultTo = $db->edges_computed->group(array('dest.id' => 1), array( 'children' => array()), $reduceTo, array("condition" => $restrictArray));
    
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
	    	"distance" =>  GeoUtils::haversine($lat, $lng, $closest['point'][1], $closest['point'][0])

	    	);
	  }

	}

}
