      <!-- navbar -->
      <div class="span2" id="logo">
        <h1><a href="/"><?php echo strtoupper(_name); ?></a></h1>
      </div>

      <div class="span5">
        <div class="input-prepend input-append pull-left" id="searchForm">
          <span class="add-on"><b class="icon-search"></b></span><input class="input-xlarge" id="searchInput" type="text" placeholder="What? Where?">
          <button id="self" class='btn btn-primary'><b class="icon-screenshot icon-white"></b> Me (w)</button>
        </div>
        <button id="addPin" class='btn btn-inverse pull-left' data-title="Click the map to drop a pin" data-content="After you're done, click 'Finish' to acknowledge."><b class="icon-map-marker icon-white"></b> Pin (e)</button>
        <ul id="multipleChoices" class="dropdown-menu"></ul>
      </div>

      <div class='span5' id="actionForm">

<?php if ($parameters['editMode'] === true): ?> 
          <div id="editMode" class="alert alert-warning pull-right"><b id="toggleDataOverlay" data-original-title="Toggle overlays" class="icon-eye-open pull-left"></b> <strong>Editing mode</strong></div>
          <div id="notice" class="alert alert-info pull-right"><b class="icon-info-sign pull-left"></b> <strong></strong></div> 
<?php else: ?>
          <div id="limitDiv" class="alert alert-info pull-right">
            <b id="toggleDataOverlay" data-original-title="Toggle overlays" class="icon-eye-open pull-left"></b>
            <div id="limitSlider" class="noUiSlider pull-right"></div>
            <div id="limitValue"></div>
          </div>
          <div id="mean" class="pull-right">
            <select id="meanSelector"></select>
          </div>
<?php endif ?>
        </div>
      </div>