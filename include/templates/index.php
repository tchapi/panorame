<!DOCTYPE html>
<html>
  <head>
<?php include(_PATH.'include/templates/partials/_head.php'); ?>
  </head>
  <body>

    <!-- header -->
    <header class='row-fluid'>
<?php include(_PATH.'include/templates/partials/_header.php'); ?>
    </header>

    <div id="page">

      <div id="content">
<?php include(_PATH.'include/templates/pages/_'.$parameters['page']['slug'].'.php'); ?>
      </div>

<!-- modal for location -->
<?php include(_PATH.'include/templates/partials/_locationModal.php'); ?>

      <div id="mainLoader" class="loader_back"><div class="loader"></div></div>
    </div>

  </body>
<?php include(_PATH.'include/templates/partials/_scripts.php'); ?>
</html>