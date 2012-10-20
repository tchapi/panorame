<?php


  include_once('../../Geo/GeoUtils.class.php');

  var_dump(GeoUtils::calculateBBox(array(48.8661, 48.8673, 48.8646), array(-2.3708,2.3759,2.378)) );
echo "\n";

  /*
  48.8661 — -2.3708
48.8673 — 2.3759
48.8646 — 2.378

=> 48.8673 2.3708
=> 48.8646 2.378
  */

var_dump(array(48.8673,-2.3708, 48.8646,2.378));