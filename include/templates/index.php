<!DOCTYPE html>
<html>
  <head>
<?php include(_PATH.'include/templates/partials/_head.php'); ?>
  </head>
  <body>

    <!-- header -->
    <header class='row-fluid' id="header">
<?php include(_PATH.'include/templates/partials/_header.php'); ?>
    </header>

<?php if ($parameters['editMode'] === true): ?>
    <!-- adminPanel -->
<?php include(_PATH.'include/templates/partials/_adminPanel.php'); ?>
<?php endif ?>

    <!-- canvas -->
    <div id="mapCanvas">
      <div class="loader_back">
        <div class="loader"></div>
      </div>
    </div>
    
    <!-- footer -->
    <footer class='row-fluid'>
<?php include(_PATH.'include/templates/partials/_footer.php'); ?>
    </footer>

  </body>
<?php include(_PATH.'include/templates/partials/_scripts.php'); ?>
</html>