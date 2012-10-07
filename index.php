<?php
  
  require_once('request.php');

?><!DOCTYPE html>
<html>
  <head>
    <title>Panorame</title>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
<?php if ($editMode === true): ?>
    <link href="css/admin.css" rel="stylesheet">  
<?php else: ?>
    <link href="css/plugins/nouislider.css" rel="stylesheet">
<?php endif ?>
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
        <h1><a href="/">PANORA<span>ME</span></a></h1>
      </div>

      <div class="span5">
        <div class="input-prepend input-append pull-left" id="searchForm">
          <span class="add-on"><b class="icon-search"></b> Look for</span><input class="input-xlarge" id="searchInput" type="text" placeholder="What? Where?">
          <button id="self" class='btn btn-primary'><b class="icon-screenshot icon-white"></b> Me</button>
        </div>
        <button id="addPin" class='btn btn-inverse pull-left' data-title="Click the map to drop a pin" data-content="After you're done, click 'Finish' to acknowledge."><b class="icon-map-marker icon-white"></b> Drop Pin</button>
        <ul id="multipleChoices" class="dropdown-menu"></ul>
      </div>

      <div class='span5' id="actionForm">

<?php if ($editMode === true): ?> 
          <div id="editMode" class="alert alert-warning pull-right"><strong>Editing mode</strong></div> 
<?php else: ?>
          <div id="limitDiv" class="alert alert-info pull-right">
            <b id="toggleDataOverlay" data-original-title="Toggle overlays" class="icon-eye-open pull-left"></b>
            <div id="limitSlider" class="noUiSlider pull-right"></div>
            <div id="limitValue"></div>
          </div>
<?php endif ?>
        </div>
      </div>

    </header>

<?php if ($editMode === true): ?>
    <!-- ADMIN -->
    <div id="admin" class="modal">
      <div class="modal-header">
        <h3>Edition</h3>
      </div>
      <div class="form-inline modal-body">
        <div class="form-line">
          <label for="addEdge_type"><strong>Type</strong> : </label> <select id="addEdge_type"></select>
        </div>
        <div class="form-line">
          <strong>Automatically make both ways :</strong><br />
          <input name="addEdge_autoReverse" type="radio" value="0" checked><label class="radio" for="addEdge_autoReverse">None</label>
          <input name="addEdge_autoReverse" type="radio" value="same"><label class="radio text-success" for="addEdge_autoReverse">Same</label>
          <input name="addEdge_autoReverse" type="radio" value="3"><label class="radio text-warning" for="addEdge_autoReverse">Walk</label>
          <input name="addEdge_autoReverse" type="radio" value="4"><label class="radio text-error" for="addEdge_autoReverse">Cycles</label>
        </div>  
        <p class="text-info"><em>To delete an edge, right-click on it.</em></p>
      </div>
      <div class="modal-footer">
        <button id="consolidate" class="btn btn-warning" ><b class="icon-random icon-white"></b> Consolidate</button>
        <button id="addEdge" class="btn btn-info" ><b class="icon-plus-sign icon-white"></b> Add edges</button>
      </div>
    </div>
<?php endif ?>

    <!-- canvas -->
    <div id="mapCanvas">
      <div class="loader_back">
        <div class="loader"></div>
      </div>
    </div>
    
    <!-- footer -->
    <footer class='row-fluid'>
      <div class="span6" id="copyright"><b class="icon-info-sign"></b> Copyright <a href="https://about.me/tchap">tchap</a> & <a href="#">bowni</a></div>
      <div class="pull-right" id="position"><b class="icon-screenshot"></b> <span>Calculating ...</span></div>
      <div class="pull-right" id="objects"><b class="icon-th"></b> <span>0</span> Object(s)</div>
    </footer>

  </body>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js" type="text/javascript"></script>
  <script src="bootstrap/js/bootstrap.min.js"></script>
<?php if ($editMode !== true): ?>
  <script src="js/plugins/jquery.nouislider.min.js"></script>
  <?php echo $addedScript; ?>
<?php endif ?>
  <script src="js/helpers/userPositionHelper.js" type="text/javascript"></script>
  <script src="js/isocronMap.class.js.php<?php if ($editMode === true) echo '?edit=1'; ?>" type="text/javascript"></script>
  <script src="js/providers/mapsWrapper.<?php echo $framework; ?>.class.js.php<?php if ($editMode === true) echo '?edit=1'; ?>" type="text/javascript"></script>
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
