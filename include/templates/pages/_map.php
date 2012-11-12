<section class="row-fluid">
  <div id="toolbar">
    <div class="span4">
      <div class="toolTitle">Choose your starting point .. </div>
      <div class="input-prepend input-append" id="searchForm">
        <input class="input-xlarge" id="searchInput" type="text" placeholder="What? Where?">
        <button class="btn btn-primary tooltip-trigger" id="self" data-original-title="Locate me (w)"><span class="lsf">location</span></button>
        <button class="btn btn-success tooltip-trigger" id="addPin" data-original-title="Choose a place by clicking on the map"><span class="lsf">geo</span></button>
      </div>
      <ul id="multipleChoices" class="dropdown-menu"></ul>
    </div>

    <div class="span3" id="actionForm">
      <div class="toolTitle">Pick your vehicle !</div>
    <?php if ($parameters['editMode'] === true): ?> 
      <div id="editMode" class="alert alert-warning pull-right"><span id="toggleDataOverlay" data-original-title="Toggle overlays" class="lsf pull-left">view</span> <strong>Editing mode</strong></div>
      <div id="notice" class="alert alert-info pull-right"><span class="icon-info-sign pull-left"></span> <strong></strong></div> 
    <?php else: ?>
      <div id="mean">
        <div class="btn-group" data-toggle="buttons-radio" id="meanSelector"></div>
      </div>
      <div id="speed">
        <div class="btn-group" data-toggle="buttons-radio" id="speedSelector">
          <button type="button" class="btn btn-info lsf active" value="-1">minus</button>
          <button type="button" class="btn btn-info lsf" value="1">plus</button>
        </div>
      </div>
    <?php endif ?>
    </div>

    <div class="span5" id="pois">
      <div class="toolTitle">What are you looking for ?</div>
    </div>
  </div>
</section>

<div id="timeController">
  <div id="limitValue"></div>
  <input id="time" value="0" />
</div>

<?php if ($parameters['editMode'] === true): ?>
<!-- adminPanel -->
<?php include(_PATH.'include/templates/partials/_adminPanel.php'); ?>
<?php endif ?>

<!-- canvas -->
<div id="mapCanvas"></div>

<!-- modal for location -->
<?php include(_PATH.'include/templates/partials/_locationModal.php'); ?>

<!-- infos box -->
<?php include(_PATH.'include/templates/partials/_infos.php'); ?>
