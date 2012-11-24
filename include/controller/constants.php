<?php

$constants = array(

    'frameworks' => array('mapquest', 'gmaps', 'bing', 'nokia', 'openlayers'),
    'providers'  => array('nokia-terrain', 'nokia-road', 'nokia-hybrid', 'bing-road', 'bing-hybrid', 'osmaps', 'mapquest', 'gmaps-terrain', 'gmaps-road', 'gmaps-hybrid'),
    'engines'     => array('mysql','mongo'),
    'pages'      => array(
      array('slug' =>'map', 'name' => 'Home', 'home' => true),
      array('slug' =>'contact', 'name' => 'Contact'),
      array('slug' =>'about', 'name' => 'About'),
      array('slug' =>'blog', 'name' => 'Blog'),
    )

  );