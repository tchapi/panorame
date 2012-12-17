<?php header('Content-type: application/javascript'); ?>
<?php if(isset($_GET['edit']) && $_GET['edit'] == 1) $editMode = true; else $editMode = false; ?>
/* isocronMap
 *
 */
var isocronMap = function() {

    // Singleton Stuff
    if ( arguments.callee._singletonInstance )
    return arguments.callee._singletonInstance;
    arguments.callee._singletonInstance = this;

    // API keys
    this.apiKeys = {
        gmaps: "AIzaSyAcfMo5fGvQ11nZ7h68vaxSVr7ATu_32ks",
        bing: "AqOiHyGALNGGhB_HHKor3uUOZ7epiJ1k31-hXDcnaUnqOgoDl2GA_mYEAiNGuYWG",
        mapquest: "Fmjtd%7Cluua250z2g%2C2x%3Do5-962al4",
        nokia: { appId: 'mfx1fzXo1YtGMYy99ocR', token: 'Qc6HvlQzr25cBXh-gfc3jw'},
        openlayers: null,
    };

    // Helpers
    this.userPosition = new userPositionHelper();

    // Precision
    this.digits = 10000;

    // Display
    this.displayData = true;

    // Initial limit 
    this.limit = <?php echo $_GET['time'] ?>;

    // Speed slow multiplier
    this.slowness = 0.35;

    this.insertScript = function(canvas, searchInput){

        // We make it all asynchronous
        this.canvas = canvas;
        this.searchInput = searchInput;

        var oScript    = document.createElement('script');
        oScript.type   = 'text/javascript';

        var url = mapsWrapper.getUrl({apiKeys: this.apiKeys, instance: this});

        if (url == "") {

            return this.init();

        } else {

            oScript.src  = url;
            
            if (!mapsWrapper.ownCallback)
                oScript.onload = $.proxy(function(){setTimeout("isocronMap.init()", mapsWrapper.delay);}, this);

            document.body.appendChild(oScript);

        }

    };

    this.init = function(){

        this.setupVisual();

        // Options
        this.options={
            canvas: this.canvas,      
            center:{lat:<?php echo !empty($_GET['lat'])?floatval($_GET['lat']):48.8566667 ?>, lng:<?php echo !empty($_GET['lng'])?floatval($_GET['lng']):2.3509871 ?>}, // PARIS
            searchInput: this.searchInput,
            addPinButton: this.addPinButton,
            apiKeys: this.apiKeys,
            positionCallback: $.proxy(this.updateCurrentPosition, this),
            boundsHaveChangedCallback : $.proxy(this.boundsHaveChanged, this),
            colorsForType: ['', '#000000','#009900','#FF9933','#FF6666','#FF0000','#006699'], // 0, 1, 2
            thicknessesForType: [4, 2, 4, 4, 4, 4, 4], // 0, 1, 2
            zIndexesForType: [0,6,5,4,3,2,1],
            standardPinImage: '/img/pins/Blue/8.png',
            closestPointPinImage: '/img/pins/Green/8.png',
            mapReadyCallback: $.proxy(this.mapIsReady, this),
<?php if ($editMode === true): ?>
            addEdgeCallback: $.proxy(this.addEdge, this)
<?php endif ?>
        };

        mapsWrapper.createMap(this.options);

    };

    this.mapIsReady = function(){

        mapsWrapper.setupEvents(this.options);
<?php if( empty($_GET['lat']) && empty($_GET['lng']) ) : ?>
        // Finally, we center the map at the user's position
        this.setToUserPositionIfAvailable();
<?php endif ?>
    };

    this.setupVisual = function(){

        /* Locate me button */
        this.locateMe = $('#self');
        this.needsLocationPopIn = $("#locationRequest");

        this.locateMe.click($.proxy(function(){
            this.toggleAddPin(false);
            this.setToUserPositionIfAvailable();
        }, this));

        /* Add pin button */
        this.addPinButton  = $('#addPin');
        this.addPinButton.click($.proxy(this.toggleAddPin,this));

        /* Toggle data overlay button */
        this.toggleDataOverlayButton = $('#toggleDataOverlay');
        this.toggleDataOverlayButton.click($.proxy(this.toggleDataOverlay,this));

        /* Poi Chooser */
        this.POIs = $("#poiChooser");
        this.POISort = $("#poiSorter");

        // Insert POIs
        databaseWrapper.getPOIs($.proxy(function(data){

            $.each(data, $.proxy(function(key, value) {   
                
                var currentOptGroup = $('<optgroup>')
                     .attr('data-cat',1)
                     .attr("data-icon",value.icon)
                     .attr("label",value.label).appendTo(this.POIs); 

                $.each(value.items, $.proxy(function(key, item){
                    currentOptGroup
                     .append($('<option>')
                     .attr("data-icon",value.icon)
                     .attr('value', key)
                     .text(item));
                }, this));

            }, this));

            /* POIs Chooser type */
            function formatResult(item) {
                if ($(item.element).data('icon') && $(item.element).data('cat') == "1")
                    return "<span class='lsf'>" + $(item.element).data('icon') + "</span> " + item.text;
                else
                    return item.text;
            }
            function formatSelection(item) {
                return "<span class='lsf " + $(item.element).data('class') + "'>" + $(item.element).data('icon') + "</span> " + item.text;
            }
            
            this.POIs.select2({
                maximumSelectionSize: 3,
                placeholder: "Type anything !",
                formatResult: formatResult,
                formatSelection: formatSelection,
            });
            this.POIs.select2('val',[<?php echo $_GET['poi']; ?>]);
            this.POISort.select2();

        }, this));


<?php if ($editMode === true): ?>
        /* ------------------- ADMIN ------------------- */

        this.addEdgeButton     = $('#addEdge');
        this.typeSelect       = $('#addEdge_type');
        this.typeSelectDisplay= $('#display_type')
        this.autoReverse      = $('input[type=radio][name=addEdge_autoReverse]');

        this.addEdgeButton.click($.proxy(function(event){

            if (this.addEdgeButton.hasClass('active')){
                mapsWrapper.setAddEdge(false);
                this.addEdgeButton.html('<b class="icon-plus-sign icon-white"></b> Add edges (a)');
                this.addEdgeButton.removeClass('active');
                this.continuousMode.removeAttr('disabled');
                this.setNotice('Now leaving adding mode', 'success');
            } else {
                mapsWrapper.setAddEdge(true, this.continuousMode.is(':checked'));
                this.addEdgeButton.html('<b class="icon-ok icon-white"></b> Finish');
                this.addEdgeButton.addClass('active');
                this.continuousMode.attr('disabled', 'disabled');
                this.setNotice('Now in adding mode', 'danger');
            }

        },this));

        // Insert types in admin
        databaseWrapper.getTypes($.proxy(function(data){

            this.typeSelectDisplay
                     .append($("<option></option>")
                     .attr("value","null")
                     .attr("rel", 0)
                     .text("All paths"));

            $.each(data, $.proxy(function(key, value) {   
                this.typeSelect
                     .append($("<option></option>")
                     .attr("value",key)
                     .attr("rel", value.id)
                     .text("(" + value.id + ") "+ value.slug));
                this.typeSelectDisplay
                     .append($("<option></option>")
                     .attr("value",key)
                     .attr("rel", value.id)
                     .text("(" + value.id + ") "+ value.slug)); 
            }, this));

        }, this));

        this.typeSelectDisplay.change($.proxy(function(e){
            this.getDataAndRecalculateGraph();
        }, this));

        this.continuousMode = $('#addEdge_continuous');

        this.notice = $('#notice');
        this.setNotice("Welcome <?php echo $_COOKIE['panorame_auth_name']?>", 'info');

        // Keymaster
        key('esc', $.proxy(function(){
            mapsWrapper.setAddEdge(false);
            this.addEdgeButton.html('<b class="icon-plus-sign icon-white"></b> Add edges (a)');
            this.addEdgeButton.removeClass('active');
            this.continuousMode.removeAttr('disabled');
            this.setNotice('Now leaving adding mode', 'success');
            mapsWrapper.setAddPin(false);
            this.addPinButton.popover('hide');
            this.addPinButton.html('<b class="icon-map-marker icon-white"></b> Pin (e)');
            this.addPinButton.removeClass('active');
        }, this));
        key('a', $.proxy(function(){
            this.addEdgeButton.trigger('click');
        }, this));
        key('z', $.proxy(function(){
            this.continuousMode.trigger('click');
        }, this));
        key('q', $.proxy(function(){
            this.autoReverse[0].checked = true;
        }, this));
        key('s', $.proxy(function(){
            this.autoReverse[1].checked = true;
        }, this));
        key('d', $.proxy(function(){
            this.autoReverse[2].checked = true;
        }, this));
        key('f', $.proxy(function(){
            this.autoReverse[3].checked = true;
        }, this));
        /* ------------------- ADMIN ------------------- */
<?php else: ?>
        /* Time knob */
        this.timeInput    = $('#time');
        this.limitValue   = $('#limitValue');
        this.timeInput.val(this.limit/10);
        this.limitValue.html(this.getLimit());

        this.timeInput.knob({
          min:0,
          max:100,
          inline: false,
          height: 100,
          displayPrevious: true,
          displayInput: false,
          angleOffset:-90,
          angleArc:180,
          ticks: 30,
          tickLength: 0.25,
          extendCanvasRatio: 1.12,
          fgColor: ["0","#7ea8d0", "0.5","#7e4dba", "1","#bd3d9e"],
          shadow: 20,
          shadowColor: 'rgba(0,0,0,0.5)',
          canvasBgColor: '#000',
          tickColor: 'rgba(0,0,0,0.1)',
          tickFgColor: 'rgba(0,0,0,0.3)',
          change: $.proxy(function(v){
                this.limit = Math.max(0,v*10);
                this.limitValue.html(this.getLimit());
                this.timeRangeHasChanged();
            }, this)
        });

        /* Mean of transportation selector */
        this.meanSelect = $('#meanSelector');
        this.speedSelect = $('#speedSelector');
        this.meansAndSpeeds = [];

        // Insert means and speed values
        databaseWrapper.getMeansAndSpeeds($.proxy(function(data){

            $.each(data, $.proxy(function(key, value) {   
                
                var el = $('<button type="button" class="btn btn-info lsf"></button>')
                     .attr("value",value.id)
                     .text(value.slug).tooltip({placement: 'bottom'});

                this.meanSelect.append(el); 
                this.meansAndSpeeds[value.id] = value.explorables;

                if (value.id == <?php echo $_GET['mean'] ?>){
                    el.addClass('active');
                    this.selectedMean = this.meansAndSpeeds[value.id];
                }

            }, this));
            
        }, this));

        $("#mean, #speed").fadeIn();
        this.selectedSpeed = <?php echo $_GET['speed'] ?>;
        this.speedSelect.find("[value=<?php echo $_GET['speed'] ?>]").addClass('active');

        this.meanSelect.mouseup($.proxy(function(e){ 
            this.selectedMean = this.meansAndSpeeds[e.target.value];
            this.meanOrSpeedHasChanged();
        }, this));
        this.speedSelect.mouseup($.proxy(function(e){ 
            this.selectedSpeed = e.target.value;
            this.meanOrSpeedHasChanged();
        }, this));

<?php endif ?>
    
        /* Keyboard shortcuts */
        key('space', $.proxy(function(){
            this.toggleDataOverlay();
        }, this));
        key('e', $.proxy(function(){
            this.toggleAddPin()
        }, this));
        key('w', $.proxy(function(){
            this.locateMe.trigger('click');
        }, this));

        /* Create all tooltips */
        $('.tooltip-trigger').tooltip({placement: 'bottom'});

    };

    this.toggleAddPin = function(booleanValue){

        if (this.addPinButton.hasClass('active') || booleanValue === false){
            mapsWrapper.setAddPin(false);
            this.addPinButton.find('span').html('geo');
            this.addPinButton.removeClass('active');
        } else {
            mapsWrapper.setAddPin(true);
            this.addPinButton.find('span').html('ok');
            this.addPinButton.addClass('active');
        }
    };

    this.toggleDataOverlay = function(booleanValue){

        if (this.displayData == false || booleanValue === true){
            this.toggleDataOverlayButton.addClass('active');
            mapsWrapper.displayDataOverlay();
            mapsWrapper.displayClosestOverlay();
            this.displayData = true;
        } else {
            this.toggleDataOverlayButton.removeClass('active');
            mapsWrapper.removeDataOverlay();
            mapsWrapper.removeClosestOverlay();
            this.displayData = false;
        }
    };

    this.getLimit = function(){

        if (this.limit >= 3600 - 1) return "&infin;";

        var min = Math.floor(this.limit / 60);
        var sec = this.limit - min*60;

        if (sec < 10) {
            sec = '0' + sec;
        }

        if (min < 10) {
            min = '0' + min;
        }
        return ( min + '\'<span>' + sec + '\"</span>');

    };

    this.notifyLocationRequest = function() {

        if (this.userPosition.getState() == states.waiting) {  
            this.needsLocationPopIn.modal('show');
        } else {
            this.needsLocationPopIn.modal('hide');
        }

    };

    this.setToUserPositionIfAvailable = function(){

        this.userPosition.update(mapsWrapper.setPosition, mapsWrapper, $.proxy(this.notifyLocationRequest, this));

    };

    this.updateCurrentPosition = function(lat, lng){

        this.position = {lat: lat, lng: lng};
        $('#infos .position span.position_coords').html(Math.round(lat*this.digits)/this.digits + ' — ' + Math.round(lng*this.digits)/this.digits);
        this.poiHasChanged();

    };

    this.getBounds = function(){

        var bounds = mapsWrapper.getBoundsAsLatLng();

        if (bounds == null) return null;

        $('#infos .position span.position_coords').popover({placement: 'right', trigger: 'click', title:"Actual bounds",
            content: 'NW : (' + Math.round(bounds.NW_lat*this.digits)/this.digits + ', ' + Math.round(bounds.NW_lng*this.digits)/this.digits + ')<br/>' +
                     'SE : (' + Math.round(bounds.SE_lat*this.digits)/this.digits + ', ' + Math.round(bounds.SE_lng*this.digits)/this.digits + ')<br/>'
        });

        return bounds;

    };

    this.processDataAnd

    this.getDataAndRecalculateGraph = function(){

<?php if ($editMode === true): ?>
        databaseWrapper.getObjectsIn(this.getBounds(), 'edges', $("#display_type").find(':selected').attr('rel'), this.position, $.proxy(function(data){
<?php else: ?>
        databaseWrapper.getObjectsIn(this.getBounds(), 'tree', null, this.position, $.proxy(function(data){
<?php endif ?>            
            $('#infos .objects span.obj_count').html(data.count);
            
            if (data !== null && data.closest != null) {

                this.data = data;

                this.recalculateGraph({force: true});
                
            } else {
                mapsWrapper.removeDataOverlay();
                mapsWrapper.removeClosestOverlay();
            }

        }, this));
        
    };

    this.recalculateGraph = function(options){

        if (this.data == null) {
            this.getDataAndRecalculateGraph();
            return;
        }
<?php if ($editMode === true): ?>
        mapsWrapper.removeDataOverlay();
        mapsWrapper.setDataOverlay(
            this.data.edges,
            null,
            this.displayData
        );
        mapsWrapper.setClosestOverlay(this.data.closest.point, this.displayData);
<?php else: ?>
    
        if (options && options.force == true){
            // Force Dijkstra recalculation
            this.edges = this.dijkstra(this.data.tree, this.position, this.data.closest);
            mapsWrapper.removeDataOverlay(); // We need to do this to reset the limit
        }

        if (this.edges != false) {

            mapsWrapper.setDataOverlay(
                this.edges,
                this.limit,
                this.displayData
            );
            mapsWrapper.setClosestOverlay(this.data.closest.point, this.displayData);

        } else {

            mapsWrapper.removeDataOverlay();
            mapsWrapper.removeClosestOverlay();

        }

<?php endif ?>

    };

    /* Event binding */
    this.boundsHaveChanged = function()     { this.getDataAndRecalculateGraph(); };
    this.poiHasChanged = function()         { this.getDataAndRecalculateGraph(); };
    this.timeRangeHasChanged = function()   { this.recalculateGraph({force: false}); }
    this.meanOrSpeedHasChanged = function() { this.recalculateGraph({force: true}); }

    /* DIJKSTRA STUFF */
    this.findMinimumCost = function(array){

        var min = null;
        var min_index = null;

        for(i in array){
            if (array[i] != null && array[i].out !== true && (array[i].cost < min || min == null) ) {
                min = array[i].cost;
                min_index = i;
            }
        }

        return min_index;
    };

    this.calculateCost = function(startingCost, distance, grade, type){

        var speeds = this.selectedMean[type];

        return startingCost + Math.max(0, (distance*speeds[0] + grade*speeds[1])*(1 + this.slowness*(1-this.selectedSpeed)));
       
    };

    this.isExplorable = function(type){

        return (this.selectedMean[type] != null);

    };

    this.dijkstra = function(tree, poi, closestPoint){

        // console.time('dijkstra');
        // init, we need to copy
        var treeCopy = $.extend(true, {}, tree);

        // closest is in the tree <-- thanks to polygon(NW, SE, POI) in DBUtils
        var nodeId = closestPoint.id;
        if (!(node = treeCopy[nodeId])) return false; // if closest is not in tree, return
        
        node.cost = closestPoint.distance;
        node.path = [];

        // Array of computed edges
        var edges = [];

        // The first edge is POI -> root node
        edges.push({
            id: 0,
            //distance: closestPoint.distance,
            // grade: closestPoint.grade,
            type: 1,
            secable: 0,
            start:{
                id: 0,
                point:{
                    lat: poi.lat,
                    lng: poi.lng,
                 //   alt: node.point.alt
                },
                cost: 0,
            },
            dest:{
                id: nodeId,
                point:{
                    lat: closestPoint.point.lat,
                    lng: closestPoint.point.lng,
                    //alt: closestPoint.point.alt
                },
                cost: closestPoint.distance
            }
        });

        // Recurring now :

        var idInTree = null;
        var child = null;
        var newCost = 0;

        while (node != null) {
            
            // node has no cost : it will never be reached 
            if (node.cost == null) {

                // console.log('- found unreachable node : ' + nodeId);

            } else {

                // console.log('- checking reached node : ' + nodeId);

                // node has a cost : it has been reached, check his children
                for(childIndex in node.children) {

                    child = node.children[childIndex];
                    idInTree = child.id;

                    if (treeCopy[idInTree] != null && typeof(treeCopy[idInTree]) !== 'undefined' && this.isExplorable(child.type)){

                        childInTree = treeCopy[idInTree];

                        // We replace the cost if we reach him at a lesser cost
                        newCost  = this.calculateCost(node.cost, child.distance, child.grade, child.type);
                        if ( childInTree.out !== true && 
                            (typeof(childInTree.cost) === 'undefined' || childInTree.cost == null || childInTree.cost > newCost)
                        ){
                            childInTree.cost = newCost;
                            childInTree.path = node.path.slice(); // to make a copy
                            childInTree.path.push(idInTree);

                        }

                        // now we construct the edge we're going to need for display between node and childInTree
                        // console.log('  + found edge : ' + nodeId + ' => ' + idInTree + '(cost=' + childInTree.cost + ')')
                        edges.push({
                            id: child.path_id,
                            distance: child.distance,
                            grade: child.grade,
                            type: child.type,
                            secable: child.secable,
                            start:{
                                id: nodeId,
                                point:{
                                    lat: node.point.lat,
                                    lng: node.point.lng,
                                    //alt: node.point.alt
                                },
                                cost: node.cost,
                            },
                            dest:{
                                id: idInTree,
                                point:{
                                    lat: childInTree.point.lat,
                                    lng: childInTree.point.lng,
                                    //alt: childInTree.point.alt
                                },
                                cost: childInTree.cost
                            }
                        });

                    }

                }
            }

            treeCopy[nodeId].out = true; // Node is out

            // Finding the min from the rest :
            nodeId = this.findMinimumCost(treeCopy);
            if (nodeId != null){
                node = treeCopy[nodeId];
                if (node.path == null) node.path = [];
            } else {
                node = null; //exit
            }

        }
        // console.timeEnd('dijkstra');
        // console.log('-------------- end --------------');
        return edges;
    };

<?php if ($editMode === true): ?>
    // ADMIN ---------------------
    this.addEdge = function(start_lat, start_lng, start_alt, dest_lat, dest_lng, dest_alt){

        databaseWrapper.addEdge(start_lat, start_lng, start_alt, dest_lat, dest_lng, dest_alt, this.typeSelect.find('option:selected').attr('rel'), $.proxy(function(){
            this.boundsHaveChanged();
            this.setNotice('Edge added.', 'success');
        },this));

        var autoReserve_value = this.autoReverse.filter(':checked').val();
        if (autoReserve_value != "0" ) {
            var type = (autoReserve_value == "same")?this.typeSelect.find('option:selected').attr('rel'):autoReserve_value;
            databaseWrapper.addEdge(dest_lat, dest_lng, dest_alt, start_lat, start_lng, start_alt, type, $.proxy(this.boundsHaveChanged,this));
        }
    };

    this.setNotice = function(message, messageClass){

        if (message == "") {
            this.notice.hide();
        } else {
            this.notice.html(' <strong>' + messageClass.toUpperCase() + '</strong> : ' + message);
            this.notice.removeClass().addClass('pull-left alert alert-' + messageClass);
            this.notice.show();
        }

    };
<?php endif ?>
}

/* Instanciates
 *
 */
isocronMap = new isocronMap();