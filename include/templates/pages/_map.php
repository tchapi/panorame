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

<?php if ($parameters['editMode'] === true): ?> 
    <div class="span8">  
      <div id="editMode" class="alert alert-warning pull-right"><span id="toggleDataOverlay" data-original-title="Toggle overlays" class="lsf pull-left">view</span> <strong>Editing mode</strong></div>
      <div id="notice" class="alert alert-info pull-right"><span class="lsf">info</span> <strong></strong></div> 
    </div>
<?php else: ?>
    <div class="span3" id="actionForm">
      <div class="toolTitle">Pick your vehicle ... and your speed :</div>
      <div id="mean" style="display: none">
        <div class="btn-group btn-info" data-toggle="buttons-radio" id="meanSelector"></div>
      </div>
      <div id="speed" style="display: none">
        <div class="btn-group" data-toggle="buttons-radio" id="speedSelector">
          <button type="button" class="btn active" value="-1"><span class="lsf">time</span> slow</button>
          <button type="button" class="btn" value="1"><span class="lsf">dashboard</span> fast</button>
        </div>
      </div>
    </div>

    <div class="span5" id="pois">
      <div class="toolTitle">What are you looking for ?</div>
      <div id="places">
        <ul>
          <!-- EXAMPLES -->
          <li><input type='checkbox' id="restaurants" name="places"/><label for="restaurants"><span class="lsf">meal</span> Restaurants</label></li>
          <li><input type='checkbox' id="bars" name="places"/><label for="bars"><span class="lsf">coffee</span> Bars</label></li>
          <li><input type='checkbox' id="shops" name="places"/><label for="shops"><span class="lsf">gift</span> Shops</label></li>
        </ul>
      </div>
    </div>
<?php endif ?>
  </div>
</section>

<div id="locationRequest" class='modal hide fade'>
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3><span class="lsf">geo</span> Tell us where you are</h3>
  </div>
  <div class="modal-body">
    <p><?php echo _name; ?> is waiting for your approval to use your location. A toolbar or a popup must have been displayed in your browser so that you can acknowledge it and continue.</p><p class="muted">If you do not wish to share it, dismiss this alert and everything will still be running fine.</p>
  </div>
  <div class="modal-footer">
    <a href="#" data-toggle="modal" data-target="#locationRequest" class="btn btn-primary">Dismiss</a>
  </div>
  
</div>

<div id="timeController">
  <div id="limitValue"></div>
  <input id="time" value="0" style="display:none" />
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
