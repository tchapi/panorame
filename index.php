<?php
  
  require_once('request.php');

?><!DOCTYPE html>
<html>
  <head>
    <title>ISOCRON</title>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <?php echo $addedScript; ?>
    <script>

      // Wraps all that and fires
      window.onload = function() { 
        isocronMap.insertScript(
          "mapCanvas", 
          "searchInput",
          "addPin"
        );
      };

    </script>
  </head>
  <body>

    <header class='row-fluid' id="header">

      <!-- navbar -->
      <div class="span2" id="logo">
        <h1><a href="/">ISOCRON</a></h1>
      </div>

      <div class="input-prepend input-append span4" id="searchForm">
        <span class="add-on"><b class="icon-search"></b> Look for</span><input class="input-xlarge" id="searchInput" type="text" placeholder="What? Where?">
        <button id="self" class='btn btn-primary'><b class="icon-screenshot icon-white"></b> Me</button>
      </div>
      <button id="addPin" class='btn btn-inverse' data-title="Click the map to drop a pin" data-content="After you're done, click 'Finish' to acknowledge."><b class="icon-map-marker icon-white"></b> Drop Pin</button>
      <ul id="multipleChoices" class="dropdown-menu"></ul>

      <div class='span5 pull-right' id="actionForm">

          <div class="input-prepend input-append pull-left">
            <span class="add-on"><b class="icon-resize-full"></b> Radius :</span><input class="input-mini" type="text" id="span" placeholder="300">
            <span class="add-on">m</span>
          </div>
         
          <div class="btn-group pull-left" data-toggle="buttons-radio">
            <button class="btn btn-success radiusType active" data-original-title="Basic radius" ><b class="icon-cog"></b></button>
            <button class="btn btn-warning radiusType" data-original-title="Road distance" ><b class="icon-road"></b></button>
            <button class="btn btn-info radiusType" data-original-title="Suburban transport" ><b class="icon-plane"></b></button>
          </div>

          <button class="btn btn-primary pull-left">Calculate</button>
            
        </div>
      </div>

    </header>
    
    <!-- canvas -->
    <div id="mapCanvas">
      <div class="loader_back">
        <div class="loader"></div>
      </div>
    </div>
    
    <!-- footer -->
    <footer class='row-fluid'>
      <div class="span8" id="copyright"><b class="icon-info-sign"></b> Copyright <a href="https://about.me/tchap">tchap</a></div>
      <div class="pull-right" id="position"><b class="icon-screenshot"></b> <span>Calculating ...</span></div>
      <div class="pull-right" id="objects"><b class="icon-th"></b> <span>0</span> Object(s)</div>
    </footer>

  </body>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js" type="text/javascript"></script>
  <script src="bootstrap/js/bootstrap.min.js"></script>
  <script src="js/helpers/userPositionHelper.js" type="text/javascript"></script>
  <script src="js/isocronMap.class.js" type="text/javascript"></script>
  <script src="js/providers/mapsWrapper.<?php echo $framework; ?>.class.js" type="text/javascript"></script>
  <script>
    /* Instanciates the MAPS API wrapper
     */
    mapsWrapper = new mapsWrapper("<?php echo $provider; ?>");
  </script>
  <script src="js/database/databaseWrapper.class.js" type="text/javascript"></script>
  <script>
    /* Instanciates the DATABASE wrapper
     */
    databaseWrapper = new databaseWrapper();
  </script>
</html>
