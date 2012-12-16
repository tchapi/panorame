<?php header('Content-type: application/javascript'); ?>
<?php if(isset($_GET['edit']) && $_GET['edit'] == 1) $editMode = true; else $editMode = false; ?>
/* Maps Wrapper
 * Google Maps
 */
var mapsWrapper = function(type) {

    // Singleton Stuff
    if ( arguments.callee._singletonInstance )
    return arguments.callee._singletonInstance;
    arguments.callee._singletonInstance = this;

    this.type = type;
    this.map = null;
    this.edges = [];
    this.closestPoint = null;
    this.previousLimit = 0;

    this.getUrl = function(genericOptions){

        this.ownCallback = true;
        this.delay = 0;
        
        /* In case it's already been loaded */
        if (typeof google !== 'undefined') return '';

        return "http://maps.googleapis.com/maps/api/js?sensor=false&key=" + genericOptions.apiKeys.gmaps + "&libraries=places" + 
               "&callback=isocronMap.init";

    };

    this.createMap = function(genericOptions){

        switch(this.type){
            case 'gmaps-hybrid':
                var mapTypeId = google.maps.MapTypeId.HYBRID;
                break;
            case 'gmaps-terrain':
                var mapTypeId = google.maps.MapTypeId.TERRAIN;
                break;
            case 'gmaps-road':
            default:
                var mapTypeId = google.maps.MapTypeId.ROADMAP;
                break;
        }

        var options = {
            zoom: 17,
            center: new google.maps.LatLng(genericOptions.center.lat, genericOptions.center.lng),
            mapTypeId: mapTypeId, 
            streetViewControl: false,
            mapTypeControl: false,
            overviewMapControl: false
        };

        /*Construct an instance of Gmaps with the options object*/ 
        this.map = new google.maps.Map(document.getElementById(genericOptions.canvas), options);

        this.infoWindow = new google.maps.InfoWindow();

        this.positionCallback = genericOptions.positionCallback;

        this.colorsForType = genericOptions.colorsForType;
        this.thicknessesForType = genericOptions.thicknessesForType;
        this.zIndexesForType = genericOptions.zIndexesForType;
        this.standardPinImage = genericOptions.standardPinImage;
        this.closestPointPinImage = genericOptions.closestPointPinImage;

        genericOptions.mapReadyCallback();
<?php if ($editMode === true): ?>
        this.addEdgeCallback = genericOptions.addEdgeCallback;
<?php endif ?>
    };


    this.setupEvents = function(genericOptions){

        // Setup autocomplete on search field
        this.autocomplete = new google.maps.places.Autocomplete(document.getElementById(genericOptions.searchInput));
        this.autocomplete.bindTo('bounds', this.map);

        google.maps.event.addListener(this.autocomplete, 'place_changed', $.proxy(function() {
          var place = this.autocomplete.getPlace();
          this.setPosition(place.geometry.location.lat(), place.geometry.location.lng(), place.formatted_address);
        }, this));

        google.maps.event.addListener(this.map, 'click', $.proxy(this.clickListener, this));

        google.maps.event.addListener(this.map, 'idle', function(event) {
          genericOptions.boundsHaveChangedCallback();
        });
<?php if ($editMode === true): ?>
        // ADMIN ----------------------------------
        // Create an ElevationService
        this.elevator = new google.maps.ElevationService();
<?php endif ?>

    };

    this.setAddPin = function(booleanValue){
        this.addPin = booleanValue;
        if (booleanValue === true) {
            this.map.setOptions({draggableCursor: 'crosshair'});
        } else {
            this.map.setOptions({draggableCursor: 'default'});
        }
    };

<?php if ($editMode === true): ?>
    this.setAddEdge = function(booleanValue, continuousMode){
        this.addEdge = booleanValue;
        if (booleanValue === true) {
            this.map.setOptions({draggableCursor: 'crosshair'});
            this.continuousMode = continuousMode;
        } else {
            this.map.setOptions({draggableCursor: 'default'});
            if(this.addEdgePolyline) this.addEdgePolyline.setMap(null);
            google.maps.event.removeListener(this.addEdgeListener);
        }
    };

    this.drawAddEdge = function(event){

        if (this.addEdgePolyline)
            this.addEdgePolyline.setMap(null);

        var lineCoords = [
            this.addEdge,
            event.latLng
        ];

        this.addEdgePolyline = new google.maps.Polyline({
            path: lineCoords,
            strokeColor: "#000000",
            strokeOpacity: 0.5,
            strokeWeight: 4,
            clickable: false
        });

        this.addEdgePolyline.setMap(this.map);
    };
<?php endif ?>

    this.clickListener = function(event){
        if (this.addPin == true) this.setPosition(event.latLng.lat(), event.latLng.lng(), null);

<?php if ($editMode === true): ?>
        // ADMIN ---------------------------------------
        if (this.addEdge === true) {

            this.addEdge = event.latLng;
            this.addEdgeEnd = true;
            console.log('Point A (start) :' + this.addEdge.lat() + ' - ' + this.addEdge.lng() + ' | Waiting for point B ...');

            this.addEdgeListener = google.maps.event.addListener(this.map, 'mousemove', $.proxy(function (event) {
                this.drawAddEdge(event);
            }, this));

        } else if (this.addEdgeEnd === true) {

            this.elevator.getElevationForLocations({locations:[this.addEdge, event.latLng]}, $.proxy(function(results, status) {
                var alt = [0,0];
                if (status == google.maps.ElevationStatus.OK && results[0] && results[1]) {
                    alt[0] = results[0].elevation;
                    alt[1] = results[1].elevation;
                }
                
                this.addEdgeCallback(this.addEdge.lat(), this.addEdge.lng(), alt[0],event.latLng.lat(), event.latLng.lng(), alt[1]);
                this.addEdge = true;
                this.addEdgeEnd = false;
                console.log('Point B (dest) :' + event.latLng.lat() + ' - ' + event.latLng.lng() + ' | Adding the edge now.');

                this.addEdgePolyline.setMap(null);
                google.maps.event.removeListener(this.addEdgeListener);

                if (this.continuousMode){
                    this.clickListener(event);
                }

            }, this));
        }
        // ADMIN ----------------------------------------
<?php endif ?>
    };

    this.setPosition = function(lat, lng, description){

        if (this.map && lat != null && lng != null) {

            this.position = new google.maps.LatLng(lat, lng);
            this.map.panTo(this.position);
            
            // Creates the marker & infoWindow
            if (!this.marker) {
                this.marker = new google.maps.Marker({
                  map: this.map
                });
            }
            this.marker.setPosition(this.position);
            this.marker.setIcon(new google.maps.MarkerImage(this.standardPinImage, null, null, new google.maps.Point(14,44)));

            if (description != null){
            
                // TODO, display the address / description somewhere?

            }

            this.positionCallback(lat, lng);

        }

    };

    this.getBoundsAsLatLng = function(){

        var bounds = this.map.getBounds();

        if (!bounds) return null;

            var NE = bounds.getNorthEast();
            var SW = bounds.getSouthWest();

        return {NW_lat:NE.lat(), NW_lng:SW.lng(), SE_lat:SW.lat(), SE_lng:NE.lng()};

    };

    this.setDataOverlay = function(edges, limit, display){

        var count = edges.length;
        var startPoint = null, destPoint = null;
        var currentLine = null;

        var canDrawFull = false, mustDraw = false, mustKeep = true;

        for(var i = 0; i < count; i++) {

            // Check if we have to have it on the map, drawn or already drawn
            canDrawFull =
                edges[i].dest.cost < limit;

            mustDraw = 
                edges[i].start.cost < limit &&
                (edges[i].dest.cost > this.previousLimit || edges[i].dest.cost > limit) &&
                (canDrawFull || edges[i].secable == 1);

            mustKeep =
                edges[i].start.cost < limit &&
                edges[i].dest.cost < this.previousLimit &&
                edges[i].dest.cost < limit;

            if ( limit == null || (mustDraw && canDrawFull) ){

                startPoint = edges[i].start.point;
                destPoint = edges[i].dest.point;

            } else if (mustDraw){
                
                // semi-distance at the end of a leaf
                startPoint = edges[i].start.point;
                var percent = (limit-edges[i].start.cost)/(edges[i].dest.cost - edges[i].start.cost);
                destPoint = {lat: edges[i].start.point.lat + (edges[i].dest.point.lat - edges[i].start.point.lat)*percent, lng: edges[i].start.point.lng + (edges[i].dest.point.lng - edges[i].start.point.lng)*percent};
                
            } else if (!mustKeep) { 

                // We scrap the line since we must get rid of it
                if (this.edges[i]) this.edges[i].setMap(null); 
                this.edges[i] = null;

                continue;

            } else { continue; } // We must keep it AS IS

            // Creation of the line
            currentLine = new google.maps.Polyline({
              path: [
                new google.maps.LatLng(startPoint.lat, startPoint.lng),
                new google.maps.LatLng(destPoint.lat, destPoint.lng)],
              strokeColor: this.colorsForType[edges[i].type],
              strokeWeight: this.thicknessesForType[edges[i].type],
              zIndex: this.zIndexesForType[edges[i].type],
<?php if ($editMode === true): ?>
              strokeColor: '#000000',
              strokeWeight: 8,
              // ADMIN ------------------------------------------------------------------------------------------------------------
              strokeOpacity: 0.1,
              icons: [
                {icon:{path:"M 1.5,2 1.5,-2.5 2.5,-1.5",strokeOpacity:0.75, strokeWeight:3, strokeColor: this.colorsForType[edges[i].type]},offset:"50%"},
                {icon: {path: "M 0.4,-0.5 0.4,0.5", strokeOpacity: 0.7, strokeWeight: 4, strokeColor: this.colorsForType[edges[i].type]}, repeat: '3px'}
              ]
              // ADMIN ------------------------------------------------------------------------------------------------------------
<?php endif ?> 
            });

<?php if ($editMode === true): ?>
            // ADMIN ------------------------------------------------------------------------------------------------------------
            google.maps.event.addListener(currentLine, 'click', $.proxy(this.clickListener, this));
            google.maps.event.addListener(currentLine, 'mouseover', function(event){ this.setEditable(true);});
            google.maps.event.addListener(currentLine, 'rightclick', (function(index, iM) {
              return function() {
                databaseWrapper.deleteEdge(index, $.proxy(function(){
                    this.boundsHaveChanged()
                    this.setNotice('Edge #' + index + ' deleted. You cannot undo.', 'success');
                }, iM));
              }
            })(edges[i].id, isocronMap));
            google.maps.event.addListener(currentLine.getPath(), 'set_at', (function(indexes, iM) {
              return function() {
                databaseWrapper.updateVertexCouple(indexes[0], this.getAt(0).lat(), this.getAt(0).lng(), 0, indexes[1], this.getAt(1).lat(), this.getAt(1).lng(), 0, indexes[2], $.proxy(function(){
                    this.setNotice('Vertices #' + indexes[0] + ' and #' + indexes[1] + ' updated. You can undo.', 'info');
                }, iM));
              }
            })([edges[i].start.id, edges[i].dest.id, edges[i].id],isocronMap));
            google.maps.event.addListener(currentLine.getPath(), 'insert_at', (function(indexes, iM) {
              return function() {
                databaseWrapper.cutEdge(indexes[0], indexes[1], this.getAt(1).lat(), this.getAt(1).lng(), 0, indexes[2], $.proxy(function(){
                    this.boundsHaveChanged();
                    this.setNotice('Edge #' + indexes[2] + ' cut in two. You cannot undo.', 'success');
                }, iM));
              }
            })([edges[i].start.id, edges[i].dest.id, edges[i].id], isocronMap));
            // ADMIN ------------------------------------------------------------------------------------------------------------
<?php endif ?> 

            // Removes the old reference :
            if (this.edges[i]) this.edges[i].setMap(null);
            // Pushes the line into the array
            this.edges[i] = currentLine;
            if (display) this.edges[i].setMap(this.map);
        };

        this.previousLimit = limit;

    };

    this.setClosestOverlay = function(closestPoint, display){

        var position = new google.maps.LatLng(closestPoint.lat, closestPoint.lng);

        if (this.closestPoint != null && this.closestPoint.getPosition().equals(position)) {
            return; // we will not display again
        } else {
            this.removeClosestOverlay();
            this.closestPoint = new google.maps.Marker();
            this.closestPoint.setIcon(new google.maps.MarkerImage(this.closestPointPinImage, null, null, new google.maps.Point(14,44)));
            this.closestPoint.setPosition(position);
            if (display == true) this.displayClosestOverlay();
        }

    };

    this.displayDataOverlay = function(){
        if (this.edges) {
            for (i in this.edges) {
                if (this.edges[i]) this.edges[i].setMap(this.map);
            }
        }
    };

    this.displayClosestOverlay = function(){
        if (this.closestPoint){
            this.closestPoint.setMap(this.map);
        }
    };

    this.removeDataOverlay = function(){
        if (this.edges) {
            for (i in this.edges) {
              if (this.edges[i]) this.edges[i].setMap(null);
            }
        }

        this.previousLimit = 0;
    };

    this.removeClosestOverlay = function(){
        if (this.closestPoint){
            this.closestPoint.setMap(null);
        }
    };

}