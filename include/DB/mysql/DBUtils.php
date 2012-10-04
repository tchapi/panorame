<?php

class Utils {

	const earth_radius = 6371030.0; // in m
	
	/*
	 * START -- FOR TESTING PURPOSES ONLY
	 */ 
	public static function fillWithRandomStuff($limit){

		// LAST ID
		$lastId_query = "SELECT `id` FROM  `vertices` ORDER BY `id` DESC LIMIT 1";
		$exe = mysql_query($lastId_query);
		$row = mysql_fetch_array($exe, MYSQL_ASSOC);

		$availableFromId = $row['id'] + 1;

		$vertices = "INSERT INTO  `isocron`.`vertices` (`id`, `point` ,`elevation`) VALUES ";
		$edges = "INSERT INTO  `isocron`.`edges` (`from_id` ,`to_id`,`distance` ,`grade`, `type`) VALUES ";

		$currentId = $availableFromId;
		$currentNextId = $availableFromId + 1;

		for($i = $availableFromId; $i < $limit + $availableFromId; $i++){

			$randomLat_start = 48.830000 + mt_rand(0,50000) / 1000000;
			$randomLng_start = 2.280000 + mt_rand(0,130000) / 1000000;
			$randomAlt_start = mt_rand(-300,300);

			$randomLat_dest = 48.830000 + mt_rand(0,50000) / 1000000;
			$randomLng_dest = 2.280000 + mt_rand(0,130000) / 1000000;
			$randomAlt_dest = mt_rand(-300,300);

			$distance = self::haversine($randomLat_start, $randomLng_start, $randomLat_dest, $randomLng_dest);
			$grade = $randomAlt_dest - $randomAlt_start;

			if ($i != $availableFromId) $vertices .= ", ";
			$vertices .= sprintf("( %d, GEOMFROMTEXT(  'POINT(%F %F)', 4326 ) ,  '%d' ), ( %d, GEOMFROMTEXT(  'POINT(%F %F)', 4326 ) ,  '%d' )",
						mysql_real_escape_string($currentId),
						mysql_real_escape_string($randomLng_start),
						mysql_real_escape_string($randomLat_start),
						mysql_real_escape_string($randomAlt_start),
						mysql_real_escape_string($currentNextId),
						mysql_real_escape_string($randomLng_dest),
						mysql_real_escape_string($randomLat_dest),
						mysql_real_escape_string($randomAlt_dest));

			if ($i != $availableFromId) $edges .= ", ";
			$edges .= sprintf("( '%d', '%d', '%F', '%d', 0 )",
							mysql_real_escape_string($currentId),
							mysql_real_escape_string($currentNextId),
							mysql_real_escape_string($distance),
							mysql_real_escape_string($grade));

			$currentId += 2;
			$currentNextId += 2;
		}

		$exe = mysql_query($vertices);
		$error = !(mysql_errno() == 0);

		$exe = mysql_query($edges);
		$error = $error && !(mysql_errno() == 0);

		$description = ($error?mysql_error():"OK");

		return array('success' => !$error, 'description' => $description );

	}
	/*
	 * END -- FOR TESTING PURPOSES ONLY
	 */ 

	/*
	 * HAVERSINE function for distance between two points on Earth
	 */
	public static function haversine($lat_1,$long_1,$lat_2,$long_2) {

		$sin_lat   = sin(deg2rad($lat_2  - $lat_1)  / 2.0);
		$sin2_lat  = $sin_lat * $sin_lat;

		$sin_long  = sin(deg2rad($long_2 - $long_1) / 2.0);
		$sin2_long = $sin_long * $sin_long;

		$cos_lat_1 = cos($lat_1);
		$cos_lat_2 = cos($lat_2);
		 
		$sqrt      = sqrt($sin2_lat + ($cos_lat_1 * $cos_lat_2 * $sin2_long));
		
		$distance  = 2.0 * self::earth_radius * asin($sqrt);
		 
		return $distance;

	}

	/*
	 * Adds a BOUNDS restrict to a query
	 */

	public static function extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng){

		$ratio = _extendBoundsPointRadius/self::earth_radius;
		$lat_rad = $NW_lat*pi()/180;
		$lng_rad = $NW_lng*pi()/180;
		$cos_lat_rad = cos($lat_rad);
		$sin_lat_rad = sin($lat_rad);

		$brng = (315/180)*pi();
		$newNW_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
		$newNW_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($newNW_lat*pi()/180)))* 180 / pi();

		$lat_rad = $SE_lat*pi()/180;
		$lng_rad = $SE_lng*pi()/180;
		$cos_lat_rad = cos($lat_rad);
		$sin_lat_rad = sin($lat_rad);

		$brng = 135/180*pi();
		$newSE_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
		$newSE_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($newSE_lat*pi()/180)))* 180 / pi();

		return array('NW_lat' => $newNW_lat, 'NW_lng' => $newNW_lng, 'SE_lat' => $newSE_lat, 'SE_lng' => $newSE_lng);

	}

	public static function restrictForVertex($query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

		if (isset($POI_lat) && $POI_lat != null && isset($POI_lng) && $POI_lng != null) {

			$where_clause = sprintf(" WHERE MBRIntersects( v.`point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F))') )",
									// plus simple de faire x < x_bounds and y < y_bounds ? meilleur temps d'éxécution ? a tester
							mysql_real_escape_string($NW_lng),
							mysql_real_escape_string($NW_lat),
							mysql_real_escape_string($SE_lng),
							mysql_real_escape_string($SE_lat),
							mysql_real_escape_string($POI_lng),
							mysql_real_escape_string($POI_lat),
							mysql_real_escape_string($NW_lng),
							mysql_real_escape_string($NW_lat));

		} else {

			$where_clause = sprintf(" WHERE MBRIntersects( v.`point`, GeomFromText('POLYGON((%F %F, %F %F, %F %F))') )",
									// plus simple de faire x < x_bounds and y < y_bounds ? meilleur temps d'éxécution ? a tester
							mysql_real_escape_string($NW_lng),
							mysql_real_escape_string($NW_lat),
							mysql_real_escape_string($SE_lng),
							mysql_real_escape_string($SE_lat),
							mysql_real_escape_string($NW_lng),
							mysql_real_escape_string($NW_lat));
			
		}

		return $query.$where_clause;
	}

	/*
	 * Adds a BOUNDS restrict to a query
	 */
	public static function restrictForEdgeBBox($query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

		if (isset($POI_lat) && $POI_lat != null && isset($POI_lng) && $POI_lng != null) {

			$where_clause = sprintf(" WHERE MBRIntersects( LINESTRING(v.point,v_dest.point), GeomFromText('POLYGON((%F %F, %F %F, %F %F, %F %F))') )",
									// plus simple de faire x < x_bounds and y < y_bounds ? meilleur temps d'éxécution ? a tester
							mysql_real_escape_string($NW_lng),
							mysql_real_escape_string($NW_lat),
							mysql_real_escape_string($SE_lng),
							mysql_real_escape_string($SE_lat),
							mysql_real_escape_string($POI_lng),
							mysql_real_escape_string($POI_lat),
							mysql_real_escape_string($NW_lng),
							mysql_real_escape_string($NW_lat));

		} else {

			$where_clause = sprintf(" WHERE MBRIntersects( LINESTRING(v.point,v_dest.point), GeomFromText('POLYGON((%F %F, %F %F, %F %F))') )",
									// plus simple de faire x < x_bounds and y < y_bounds ? meilleur temps d'éxécution ? a tester
							mysql_real_escape_string($NW_lng),
							mysql_real_escape_string($NW_lat),
							mysql_real_escape_string($SE_lng),
							mysql_real_escape_string($SE_lat),
							mysql_real_escape_string($NW_lng),
							mysql_real_escape_string($NW_lat));		


		}

		return $query.$where_clause;
	}

	/*
	 * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
	 */
	public static function getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

		$getVerticesIn_query = sprintf("SELECT `id`, Y(`point`) AS lat, X(`point`) AS lng, `elevation` AS alt FROM `isocron`.`vertices` v");

		$getVerticesIn_query = self::restrictForVertex($getVerticesIn_query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);

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
					        		'lng' => floatval($row["lng"]),
					        		'alt' => floatval($row["alt"])
					        	)
					        );
	      }
	      return $result;

		}

	}

	/*
	 * GET ALL THE VERTICES in given bounds expressed as two LAT / LNG couples for NW and SE
	 * AND their 1th children
	 */
	public static function getVerticesAndChildrenIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

		$b = self::extendBBox($NW_lat, $NW_lng, $SE_lat, $SE_lng);

		$getVerticesAndChildrenIn_query = sprintf("SELECT v.`id` AS id, Y(v.`point`) AS lat, X(v.`point`) AS lng, v.`elevation` AS alt, 
										group_concat(CONCAT('{\"id\":',e.`to_id`, ', \"path_id\":', e.`id`, ', \"distance\":', e.`distance`, ', \"grade\":', e.`grade`, ', \"type\":', e.`type`,'}')) AS children FROM `isocron`.`vertices` v
										LEFT JOIN `isocron`.`edges` e ON (e.`from_id` = v.`id` AND e.`is_deleted` =0)");

		$getVerticesAndChildrenIn_query = self::restrictForVertex($getVerticesAndChildrenIn_query, $b['NW_lat'], $b['NW_lng'], $b['SE_lat'], $b['SE_lng'], $POI_lat, $POI_lng);
		$getVerticesAndChildrenIn_query .= " GROUP BY v.`id`";

		$exe = mysql_query($getVerticesAndChildrenIn_query);

		// Returns true if the query was well executed
		if (!$exe || $exe == false ) {
		  return false;
		} else {
		  // Fetch the points
		  $result = array();
	      while ($row = mysql_fetch_array($exe, MYSQL_ASSOC)) {
	      	
	      	if ($row["children"] == null){
	      		$childrenAsPHPArray = null;
	      	} else {
	      		$childrenAsPHPArray = json_decode('['.$row["children"].']');
	      	}

	        $result[intval($row["id"])] = array( 
					        	'point' => array(
					        		'lat' => floatval($row["lat"]),
					        		'lng' => floatval($row["lng"]),
					        		'alt' => floatval($row["alt"])
					        	),
					        	'children' => $childrenAsPHPArray
					        );
	      }

	      return $result;

		}

	}

	/*
	 * GET ALL THE EDGES in given bounds expressed as two LAT / LNG couples for NW and SE
	 */
	public static function getEdgesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng){

		$getEdgesIn_query = "SELECT e.`id` AS id, Y(v.`point`) AS lat_start, X(v.`point`) AS lng_start, v.`elevation` AS alt_start, v.`id` AS id_start,
									Y(v_dest.`point`) AS lat_dest, X(v_dest.`point`) AS lng_dest, v_dest.`elevation` AS alt_dest, v_dest.`id` AS id_dest,
									e.`distance` AS distance, e.`grade` AS grade, e.`type` AS type
									FROM `isocron`.`edges` e
									INNER JOIN `isocron`.`vertices` v ON v.`id` = e.`from_id`
									INNER JOIN `isocron`.`vertices` v_dest ON v_dest.`id` = e.`to_id`";

		$getEdgesIn_query = self::restrictForEdgeBBox($getEdgesIn_query, $NW_lat, $NW_lng, $SE_lat, $SE_lng, $POI_lat, $POI_lng);
		$getEdgesIn_query .= " AND e.`is_deleted` = 0";
		
		$exe = mysql_query($getEdgesIn_query);

		// Returns true if the query was well executed
		if (!$exe || $exe == false ) {
		  return false;
		} else {
		  // Fetch the edges
		  $result = array();
	      while ($row = mysql_fetch_array($exe, MYSQL_ASSOC)) {
	        $result[] = array(
					        	'id' => intval($row["id"]), 
					        	'start' => array(
					        		'id' => floatval($row["id_start"]),
					        		'point' => array(
						        		'lat' => floatval($row["lat_start"]),
						        		'lng' => floatval($row['lng_start']),
						        		'alt' => intval($row["alt_start"])
						        	)
					        	),
					        	'dest' => array(
					        		'id' => floatval($row["id_dest"]),
					        		'point' => array(
						        		'lat' => floatval($row["lat_dest"]),
						        		'lng' => floatval($row['lng_dest']),
						        		'alt' => intval($row["alt_dest"])
						        	)
					        	),
					        	'distance' => floatval($row['distance']),
					        	'grade' => intval($row['grade']),
					        	'type' => intval($row['type'])
					        );
	      }
	      return $result;

		}

	}

	/*
	 * GET The closest vertex from a given lat / lng couple within a x meters radius
	 */
	public static function build_sorter($lat, $lng) {
		return function ($a, $b) use ($lat, $lng)
		{

			$a_ = Utils::haversine($a['point']['lat'], $a['point']['lng'], $lat, $lng);
			$b_ = Utils::haversine($b['point']['lat'], $b['point']['lng'], $lat, $lng);

		    if ($a_ == $b_) {
		        return 0;
		    }
		    return ($a_ < $b_) ? -1 : 1;
		};
	}

	public static function getClosestVertex($lat, $lng, $radius_in_m){

		$ratio = $radius_in_m/self::earth_radius;
		$lat_rad = $lat*pi()/180;
		$lng_rad = $lng*pi()/180;
		$cos_lat_rad = cos($lat_rad);
		$sin_lat_rad = sin($lat_rad);

		$brng = (315/180)*pi();
		$NW_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
		$NW_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($NW_lat*pi()/180)))* 180 / pi();

		$brng = 135/180*pi();
		$SE_lat = asin( $sin_lat_rad*cos($ratio) + $cos_lat_rad*sin($ratio)*cos($brng) )* 180 / pi();
		$SE_lng = ($lng_rad + atan2(sin($brng)*sin($ratio)*$cos_lat_rad, cos($ratio)-$sin_lat_rad*sin($SE_lat*pi()/180)))* 180 / pi();

		$vertices = self::getVerticesIn($NW_lat, $NW_lng, $SE_lat, $SE_lng, null, null);

		if (isset($vertices[0])){

			// Sort (quick sort)
			usort($vertices, self::build_sorter($lat, $lng));
			
			$closest = $vertices[0];
			$closest['distance'] = self::haversine($lat, $lng, $closest['point']['lat'], $closest['point']['lng']);

			return $closest;

		} else {

			return null;

		}
	}

	public static function getTypes(){

		$query = "SELECT `id`, `slug` from `isocron`.`types` where editable = 1;";
		$exe = mysql_query($query);

		// Returns true if the query was well executed
		if (!$exe || $exe == false ) {
		  return false;
		} else {
		  // Fetch the types
		  $result = array();
	      while ($row = mysql_fetch_array($exe, MYSQL_ASSOC)) {
	      	array_push($result, array('id' => $row['id'], 'description' => $row['slug']));
	      }
	  	}

	  	return $result;

	}

	/*
	 * Updating a vertex
	 */

	public static function updateVertexCouple($start_id, $start_lat, $start_lng, $start_alt, $dest_id, $dest_lat, $dest_lng, $dest_alt, $edge_id){

		$query1 = sprintf("UPDATE `isocron`.`vertices` SET `point` = GeomFromText('point(%F %F)', 4326), `elevation` = %d WHERE `id` = %d;",
							mysql_real_escape_string($start_lng),
							mysql_real_escape_string($start_lat),
							mysql_real_escape_string($start_alt),
							mysql_real_escape_string($start_id));

		$exe1 = mysql_query($query1);

		$query2 = sprintf("UPDATE `isocron`.`vertices` SET `point` = GeomFromText('point(%F %F)', 4326), `elevation` = %d WHERE `id` = %d;",
							mysql_real_escape_string($dest_lng),
							mysql_real_escape_string($dest_lat),
							mysql_real_escape_string($dest_alt),
							mysql_real_escape_string($dest_id));

		$exe2 = mysql_query($query2);

		$distance = self::haversine($start_lat, $start_lng, $dest_lat, $dest_lng);
		$grade = $dest_alt - $start_alt;

		$query3 = sprintf("UPDATE `isocron`.`edges` SET `distance` = %F, `grade`= %d WHERE `id` = %d;",
							mysql_real_escape_string($distance),
							mysql_real_escape_string($grade),
							mysql_real_escape_string($edge_id));

		$exe2 = mysql_query($query3);

		return $query1.$query2.$query3;
	}

	/*
	 * Deleting an edge
	 */

	public static function deleteEdge($edge_id){

		$query = sprintf("SELECT from_id, to_id FROM `isocron`.`edges` WHERE `id` = %d;",
							mysql_real_escape_string($edge_id));

		$exe = mysql_query($query);

		$row = mysql_fetch_array($exe, MYSQL_ASSOC);
		$from_id = intval($row['from_id']);
		$to_id = intval($row['to_id']);

		$query2 = sprintf("UPDATE `isocron`.`edges` SET is_deleted = 1 WHERE `id` = %d;",
							mysql_real_escape_string($edge_id));

		$exe2 = mysql_query($query2);

		// if form_id remaining
		$query3 = sprintf("SELECT id FROM `isocron`.`edges` WHERE is_deleted = 0 AND (`from_id` = %d OR `to_id`= %d);",
							mysql_real_escape_string($from_id),
							mysql_real_escape_string($from_id));

		$exe2 = mysql_query($query3);

		if (mysql_num_rows($exe2) === 0){

		 	$query = sprintf("UPDATE `isocron`.`vertices` SET is_deleted = 1 WHERE `id` = %d;",
						mysql_real_escape_string($from_id));

			mysql_query($query);

		}

		// if to_id remaining
		$query3 = sprintf("SELECT id FROM `isocron`.`edges` WHERE is_deleted = 0 AND (`from_id` = %d OR `to_id`= %d);",
							mysql_real_escape_string($to_id),
							mysql_real_escape_string($to_id));

		$exe2 = mysql_query($query3);

		if (mysql_num_rows($exe2) === 0){

		 	$query = sprintf("UPDATE `isocron`.`vertices` SET is_deleted = 1 WHERE `id` = %d;",
						mysql_real_escape_string($to_id));

			mysql_query($query);

		}

		return true;
	}

	/*
	 * Cutting a vertex
	 */

	public static function cutEdge($start_id, $dest_id, $new_lat, $new_lng, $new_alt, $edge_id){

		$query = sprintf("INSERT INTO `isocron`.`vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), %d);",
							mysql_real_escape_string($new_lng),
							mysql_real_escape_string($new_lat),
							mysql_real_escape_string($new_alt));
		$query_fetch = sprintf("SELECT id FROM `isocron`.`vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
						mysql_real_escape_string($new_lng),
						mysql_real_escape_string($new_lat));
		mysql_query('BEGIN');
		mysql_query($query);
		$exe = mysql_query($query_fetch);
		mysql_query('COMMIT');

		$row = mysql_fetch_array($exe, MYSQL_ASSOC);
		$new_id = intval($row['id']);

		$query2 = sprintf("SELECT Y(`point`) as lat, X(`point`) as lng, elevation as alt FROM `isocron`.`vertices` WHERE `id` = %d;",
							mysql_real_escape_string($start_id));

		$exe = mysql_query($query2);		
		$row = mysql_fetch_array($exe, MYSQL_ASSOC);
		$start_lat = intval($row['lat']);
		$start_lng = intval($row['lng']);
		$start_alt = intval($row['alt']);


		$query2b = sprintf("SELECT Y(`point`) as lat, X(`point`) as lng, elevation as alt FROM `isocron`.`vertices` WHERE `id` = %d;",
							mysql_real_escape_string($dest_id));

		$exe = mysql_query($query2b);		
		$row = mysql_fetch_array($exe, MYSQL_ASSOC);
		$dest_lat = intval($row['lat']);
		$dest_lng = intval($row['lng']);
		$dest_alt = intval($row['alt']);

		// update 1

		$distance = self::haversine($start_lat, $start_lng, $new_lat, $new_lng);
		$grade = $new_alt - $start_alt;

		$query3 = sprintf("UPDATE `isocron`.`edges` SET `distance` = %F, `grade`= %d, `to_id`= %d WHERE `id` = %d;",
							mysql_real_escape_string($distance),
							mysql_real_escape_string($grade),
							mysql_real_escape_string($new_id),
							mysql_real_escape_string($edge_id));

		mysql_query($query3);

		$query3b = sprintf("SELECT type FROM `isocron`.`edges` WHERE `id` = %d;",
							mysql_real_escape_string($edge_id));

		$exe = mysql_query($query3b);		
		$row = mysql_fetch_array($exe, MYSQL_ASSOC);
		$type = intval($row['type']);

		// create 2

		$distance = self::haversine($new_lat, $new_lng, $dest_lat, $dest_lng);
		$grade = $dest_alt - $new_alt;

		$query4 = sprintf("INSERT INTO `isocron`.`edges` (`from_id`, `to_id`, `distance`, `grade`, `type`) 
							   VALUES ('%d', '%d', '%F', '%d', '%d');",
						mysql_real_escape_string($new_id),
						mysql_real_escape_string($dest_id),
						mysql_real_escape_string($distance),
						mysql_real_escape_string($grade),
						mysql_real_escape_string($type));

		mysql_query($query4);

		return $query.$query_fetch.$query2.$query2b.$query3.$query3b.$query4;
	}

	/*
	 * Adding an edge
	 */
	
	public static function addEdge($start_lat, $start_lng, $start_alt, $dest_lat, $dest_lng, $dest_alt, $type){

		$startExistsAlready = self::getClosestVertex($start_lat, $start_lng, _closestPointRadius_edit);
		$destExistsAlready = self::getClosestVertex($dest_lat, $dest_lng, _closestPointRadius_edit);

		if ($startExistsAlready == null) {
			$start_point_query = sprintf("INSERT INTO `isocron`.`vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), '%d');",
							mysql_real_escape_string($start_lng),
							mysql_real_escape_string($start_lat),
							mysql_real_escape_string($start_alt));
			$start_point_query_fetch = sprintf("SELECT id FROM `isocron`.`vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
							mysql_real_escape_string($start_lng),
							mysql_real_escape_string($start_lat));
			mysql_query('BEGIN');
			mysql_query($start_point_query);
			$exe = mysql_query($start_point_query_fetch);
			mysql_query('COMMIT');

			$row = mysql_fetch_array($exe, MYSQL_ASSOC);
			$start_id = intval($row['id']);
		} else {
			$start_id = intval($startExistsAlready['id']);
		}

		if ($destExistsAlready == null) {

			$dest_point_query = sprintf("INSERT INTO `isocron`.`vertices` (`point`, `elevation`) VALUES (GeomFromText('point(%F %F)', 4326), '%d');",
						mysql_real_escape_string($dest_lng),
						mysql_real_escape_string($dest_lat),
						mysql_real_escape_string($dest_alt));
			$dest_point_query_fetch = sprintf("SELECT id FROM `isocron`.`vertices` WHERE `point` = GeomFromText('point(%F %F)', 4326);",
							mysql_real_escape_string($dest_lng),
							mysql_real_escape_string($dest_lat));
			mysql_query('BEGIN');
			mysql_query($dest_point_query);
			$exe = mysql_query($dest_point_query_fetch);
			mysql_query('COMMIT');

			$row = mysql_fetch_array($exe, MYSQL_ASSOC);
			$dest_id = intval($row['id']);
		} else {
			$dest_id = intval($destExistsAlready['id']);
		}

		$distance = self::haversine($start_lat, $start_lng, $dest_lat, $dest_lng);
		$grade = $dest_alt - $start_alt;

		$edge_query = sprintf("INSERT INTO `isocron`.`edges` (`from_id`, `to_id`, `distance`, `grade`, `type`) 
							   VALUES ('%d', '%d', '%F', '%d', '%d');",
						mysql_real_escape_string($start_id),
						mysql_real_escape_string($dest_id),
						mysql_real_escape_string($distance),
						mysql_real_escape_string($grade),
						mysql_real_escape_string($type));

		// Executes the query for the starting point
		mysql_query('BEGIN');
		$exe = mysql_query($edge_query);
		$lastId = mysql_query("SELECT last_insert_id() AS id;");
		mysql_query('COMMIT');

		// Returns true if the query was well executed
		if (!$exe || $exe == false ) {
		  return false;
		} else {

		  $row_lastId = mysql_fetch_array($lastId, MYSQL_ASSOC);
		  // Fetch the info
		  return array(
		  	'start' => array(
		  		'alreadyExisted' => !empty($startExistsAlready)?true:false,
		  		'id' => $start_id,
		  		'lat' => $start_lat,
				'lng' => $start_lng,
				'alt' => $start_alt
		  		),
		  	'dest' => array(
		  		'alreadyExisted' => !empty($destExistsAlready)?true:false,
		  		'id' => $dest_id,
		  		'lat' => $dest_lat,
				'lng' => $dest_lng,
				'alt' => $dest_alt
		  		),
		  	'edge' => array(
		  		'id' => intval($row_lastId['id']),
			  	'type' => $type,
			  	'distance' => $distance,
			  	'grade' => $grade
		  		)
		  );
		}

	}
	
}
