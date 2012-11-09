      <!-- navbar -->
      <div class="span2" id="logo">
        <h1><a href="/"><?php echo strtoupper(_name); ?></a></h1>
      </div>

      <div class="span5">
        <div class="input-prepend input-append pull-left" id="searchForm">
          <span class="add-on"><span class="lsf">search</span></span><input class="input-xlarge" id="searchInput" type="text" placeholder="What? Where?">
          <button id="self" class='btn btn-primary'><span class="lsf">location</span> Me (w)</button>
        </div>
        <button id="addPin" class='btn btn-inverse pull-left' data-title="Click the map to drop a pin" data-content="After you're done, click 'Finish' to acknowledge."><span class="lsf">geo</span> Pin (e)</button>
        <ul id="multipleChoices" class="dropdown-menu"></ul>
      </div>

      <div class='span5' id="actionForm">

<?php if ($parameters['editMode'] === true): ?> 
          <div id="editMode" class="alert alert-warning pull-right"><span id="toggleDataOverlay" data-original-title="Toggle overlays" class="lsf pull-left">view</span> <strong>Editing mode</strong></div>
          <div id="notice" class="alert alert-info pull-right"><span class="icon-info-sign pull-left"></span> <strong></strong></div> 
<?php else: ?>
          <div id="limitDiv" class="alert alert-info pull-right">
            <span id="toggleDataOverlay" data-original-title="Toggle overlays" class="pull-left lsf">view</span>
            <div id="limitSlider" class="noUiSlider pull-right"></div>
            <div id="limitValue"></div>
          </div>
          <div id="mean" class="pull-right">
            <select id="meanSelector"></select>
          </div>
<?php endif ?>
        </div>
      </div>