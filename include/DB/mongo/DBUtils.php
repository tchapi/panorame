<?php

class Utils {

	const earth_radius = 6371030.0; // in m

	/*
	 * START -- FOR TESTING PURPOSES ONLY
	 */ 

	public static function fillWithRandomStuff($limit){

		global $DBConnection;

		$db = $DBConnection->getDB();

		$db->resetError();

		$vertices = $db->vertices;
		$edges = $db->edges;

		// LAST ID
		$availableFromIdElement = current(iterator_to_array($vertices->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));
		$lastEdgeIdElement = current(iterator_to_array($edges->find(array(), array('_id' => 1))->sort(array('_id' => -1))->limit(1)));

		$availableFromId = $availableFromIdElement['_id'];
		$lastEdgeId = $lastEdgeIdElement['_id'];

		$currentId = $availableFromId + 1;
		$currentNextId = $lastEdgeId + 1 + 1;

		for($i = $availableFromId; $i < $limit + $availableFromId; $i++){

			$randomLat_start = 48.830000 + mt_rand(0,50000) / 1000000;
			$randomLng_start = 2.280000 + mt_rand(0,130000) / 1000000;
			$randomAlt_start = mt_rand(-300,300);

			$randomLat_dest = 48.830000 + mt_rand(0,50000) / 1000000;
			$randomLng_dest = 2.280000 + mt_rand(0,130000) / 1000000;
			$randomAlt_dest = mt_rand(-300,300);

			$distance = self::haversine($randomLat_start, $randomLng_start, $randomLat_dest, $randomLng_dest);
			$grade = $randomAlt_dest - $randomAlt_start;

			$vertices->insert(array( "_id" => $currentId, "lat" => $randomLat_start, "lng" => $randomLng_start, "alt" => $randomAlt_start));
			$vertices->insert(array( "_id" => $currentNextId, "lat" => $randomLat_dest, "lng" => $randomLng_dest, "alt" => $randomAlt_dest ));

			$edges->insert(array( "_id" => ($lastEdgeId + 1), "from_id" => $currentId, "to_id" => $currentNextId, "distance" => $distance, "grade" => $grade, "type" => 2 ));

			$currentId += 2;
			$currentNextId += 2;
		}

		$error = $db->lastError();
		$description = ($error?$error:"OK");

		return array('success' => !$error, 'description' => $description );

	}
	/*
	 * END -- FOR TESTING PURPOSES ONLY
	 */ 

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
	
}
