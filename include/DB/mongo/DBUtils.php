<?php

class Utils {

	const earth_radius = 6367500; // in m

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
