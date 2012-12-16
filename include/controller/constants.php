<?php

$constants = array(

    'frameworks' => array('mapquest', 'gmaps', 'bing', 'nokia', 'openlayers'),
    'providers'  => array('nokia-terrain', 'nokia-road', 'nokia-hybrid', 'bing-road', 'bing-hybrid', 'osmaps', 'mapquest', 'gmaps-terrain', 'gmaps-road', 'gmaps-hybrid'),
    'engines'    => array('mysql','mongo'),
    'pages'      => array(
      array('slug' =>'map', 'name' => 'Home', 'home' => true),
      array('slug' =>'contact', 'name' => 'Contact'),
      array('slug' =>'about', 'name' => 'About'),
      array('slug' =>'blog', 'name' => 'Blog'),
    ),

    'limits' => array(
      'mean'  => array(1,2,3),
      'time'  => array('min' => 0, 'max' => 1000),
      'speed' => array(0,1),
      'poi'   => array(1,2,3,4,5,6,7,8)
    ),

    'defaults'   => array(
      'lat'   => 48.8566667,
      'lng'   => 2.3509871,
      'mean'  => 1,
      'time'  => 300,
      'speed' => 1,
      'poi'   => array(2),
      'engine'=> 'mysql'
    )

  );