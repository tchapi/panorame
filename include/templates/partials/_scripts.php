<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js" type="text/javascript"></script>
<script src="bootstrap/js/bootstrap.min.js"></script>
<script src="js/plugins/keymaster.min.js"></script>
<?php if ($parameters['editMode'] !== true): ?>
<script src="js/plugins/jquery.nouislider.min.js"></script>
<?php echo $parameters['addedScript']; ?>
<?php endif ?>
<script src="js/helpers/userPositionHelper.js" type="text/javascript"></script>
<script src="js/isocronMap.class.js.php<?php if ($parameters['editMode'] === true) echo '?edit=1'; ?>" type="text/javascript"></script>
<script src="js/providers/mapsWrapper.<?php echo $parameters['framework']; ?>.class.js.php<?php if ($parameters['editMode'] === true) echo '?edit=1'; ?>" type="text/javascript"></script>
<script src="js/database/databaseWrapper.class.js" type="text/javascript"></script>
<script>

  /* Instanciates the MAPS API wrapper
   */
  mapsWrapper = new mapsWrapper("<?php echo $parameters['provider']; ?>");

  /* Instanciates the DATABASE wrapper
   */
  databaseWrapper = new databaseWrapper();

  // Wraps all that and fires
  window.onload = function() { 
    isocronMap.insertScript(
      "mapCanvas", 
      "searchInput"
    );
  };

</script>