<?php

class Utils {

	static $arth_radius = 6367.5; // in km
	
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
		 
		$distance  = 2.0 * self::earth_radius * asin($sqrt);
		 
		return $distance;

	}

	/*
	 * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
	 */
	public static function getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng){

		$getVerticesIn_query = sprintf("SELECT `id`, Y(`point`) AS lat, X(`point`), elevation AS lng FROM `isocron`.`vertices` 
								WHERE Intersects( `point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F, %F %F))') );",
						mysql_real_escape_string($NW_lng),
						mysql_real_escape_string($NW_lat),
						mysql_real_escape_string($SE_lng),
						mysql_real_escape_string($NW_lat),						
						mysql_real_escape_string($SE_lng),
						mysql_real_escape_string($SE_lat),
						mysql_real_escape_string($NW_lng),
						mysql_real_escape_string($SE_lat),
						mysql_real_escape_string($NW_lng),
						mysql_real_escape_string($NW_lat));

		$exe = mysql_query($getVerticesIn_query);

		// Returns true if the query was well executed
		if (!$exe || $exe == false ) {
		  return false;
		} else {
		  // Fetch the points
		  $result = array();
	      while ($row = mysql_fetch_array($exe, MYSQL_ASSOC)) {
	        $result[] = array(
					        	'id' => intval($row["id"]), 
					        	'point' => array(
					        		'lat' => floatval($row["lat"]),
					        		'lng' => floatval($row['lng'])
					        	),
					        	'alt' => floatval($row['elevation'])
					        );
	      }
	      return $result;

		}

	}

	/*
	 * Adding an edge
	 */
	public static function addEdge($start_lat, $start_lon, $start_alt, $dest_lat, $dest_lon, $dest_alt, $type){

		$start_point_query = sprintf("INSERT INTO `isocron`.`vertices` (`id`, `point`, `elevation`) VALUES (NULL, GeomFromText('point(%F %F)'), '%d');",
						mysql_real_escape_string($start_lat),
						mysql_real_escape_string($start_lon),
						mysql_real_escape_string($start_alt));

		$end_point_query = sprintf("INSERT INTO `isocron`.`vertices` (`id`, `point`, `elevation`) VALUES (NULL, GeomFromText('point(%F %F)'), '%d');",
						mysql_real_escape_string($dest_lat),
						mysql_real_escape_string($dest_lon),
						mysql_real_escape_string($dest_alt));

		// Executes the query for the starting point
		$exe = mysql_query($start_point_query);

		// Returns true if the query was well executed
		if (!$exe || $exe == false ) {
		  return false;
		} else {
		  // Fetch the info
		  $row = mysql_fetch_array($exe, MYSQL_ASSOC);
		  if (!$row) return false;
		  var_dump($row);
		}

		// Executes the query for the starting point
		$exe = mysql_query($end_point_query);

		// Returns true if the query was well executed
		if (!$exe || $exe == false ) {
		  return false;
		} else {
		  // Fetch the info
		  $row = mysql_fetch_array($exe, MYSQL_ASSOC);
		  if (!$row) return false;
		  var_dump($row);
		}

		$distance = self::haversine($start_lat, $start_lon, $dest_lat, $dest_lon);
		$grade = $dest_alt - $start_alt;

		$edge_query = sprintf("INSERT INTO `isocron`.`edges` (`id`, `from_id`, `to_id`, `distance`, `grade`, `type`) 
							   VALUES (NULL, '%d', '%d', '%F', '%d', '%d');",
						mysql_real_escape_string($start_id),
						mysql_real_escape_string($end_id),
						mysql_real_escape_string($distance),
						mysql_real_escape_string($grade),
						mysql_real_escape_string($type));
	
		// Executes the query for the starting point
		$exe = mysql_query($edge_query);

		// Returns true if the query was well executed
		if (!$exe || $exe == false ) {
		  return false;
		} else {
		  // Fetch the info
		  $row = mysql_fetch_array($exe, MYSQL_ASSOC);
		  if (!$row) return false;
		  var_dump($row);
		}
	}

}