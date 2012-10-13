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

    $db->resetError();

    $means_raw = iterator_to_array($db->means->find());
    $means = array();

    // Fetch the types
    foreach($means_raw as $mean) {
      
    	$explorablesAsPHPArray = iterator_to_array($db->speeds->find(array("mean_id" => $mean['_id'])));

    	$explorables = array();

      foreach ($explorablesAsPHPArray as $explorable){
        $explorables[$explorable['type_id']] = array($explorable['flat_speed'], $explorable['grade_speed']);
      }

      array_push($means, array('id' => $mean['_id'], 'description' => $mean['description'], 'explorables' => $explorables));
    
    }

    return $means;

  }
	
}
