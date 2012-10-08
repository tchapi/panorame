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

    this.getUrl = function(genericOptions){

        this.ownCallback = true;
        this.delay = 0;
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
    };

<?php if ($editMode === true): ?>
    this.setAddEdge = function(booleanValue){
        this.addEdge = booleanValue;
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

            }, this));
        }
        // ADMIN ----------------------------------------
<?php endif ?>
    };

    this.setPosition = function(lat, lng, description){

        if (this.map && lat != null && lng != null) {

            this.position = new google.maps.LatLng(lat, lng);
            this.map.panTo(this.position);
            this.map.setZoom(17);
            
            // Creates the marker & infoWindow
            if (!this.marker) {
                this.marker = new google.maps.Marker({
                  map: this.map
                });
            }
            this.marker.setPosition(this.position);
            this.marker.setIcon(this.standardPinImage);

            if (description != null){
                // Displays the infoWindow
                this.infoWindow.setContent('<div>' + description + '</div>');
                this.infoWindow.open(this.map, this.marker);
            } else {
                this.infoWindow.close();
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

        this.removeDataOverlay();
        this.edges.length = 0;

        var count = edges.length;
        var startPoint = null, destPoint = null;

        for(var i = 0; i < count; i++) {

            if (limit == null || (edges[i].start.cost < limit && edges[i].dest.cost < limit)){

                startPoint = edges[i].start.point;
                destPoint = edges[i].dest.point;

            } else if (edges[i].start.cost < limit && edges[i].dest.cost > limit){
                
                // semi-distance at the end of a leaf
                startPoint = edges[i].start.point;
                var percent = (limit-edges[i].start.cost)/(edges[i].dest.cost - edges[i].start.cost);
                destPoint = {lat: edges[i].start.point.lat + (edges[i].dest.point.lat - edges[i].start.point.lat)*percent, lng: edges[i].start.point.lng + (edges[i].dest.point.lng - edges[i].start.point.lng)*percent};
                
            } else { continue; }

            // Creation of the line
            this.edges[i] = new google.maps.Polyline({
              path: [
                new google.maps.LatLng(startPoint.lat, startPoint.lng),
                new google.maps.LatLng(destPoint.lat, destPoint.lng)],
              strokeColor: this.colorsForType[edges[i].type],
              strokeWeight: this.thicknessesForType[edges[i].type],
<?php if ($editMode === true): ?>
              // ADMIN ------------------------------------------------------------------------------------------------------------
              strokeOpacity: 0,
              editable: true,
              icons: [
                {icon:{path:"M 2,5 2,-5 3.5,-2",strokeOpacity:0.75, strokeWeight:3, strokeColor: this.colorsForType[edges[i].type]},offset:"50%"},
                {icon: {path: "M 0.5,0 0.5,1", strokeOpacity: 1, strokeWeight: 4 }, offset: '1', repeat: '3px'}
              ]
              // ADMIN ------------------------------------------------------------------------------------------------------------
<?php endif ?>
            });

            google.maps.event.addListener(this.edges[i], 'click', $.proxy(this.clickListener, this));

<?php if ($editMode === true): ?>
            // ADMIN ------------------------------------------------------------------------------------------------------------
            google.maps.event.addListener(this.edges[i], 'rightclick', (function(index, iM) {
              return function() {
                databaseWrapper.deleteEdge(index, $.proxy(iM.boundsHaveChanged, iM));
              }
            })(edges[i].id, isocronMap));
            google.maps.event.addListener(this.edges[i].getPath(), 'set_at', (function(indexes, iM) {
              return function() {
                databaseWrapper.updateVertexCouple(indexes[0], this.getAt(0).lat(), this.getAt(0).lng(), 0, indexes[1], this.getAt(1).lat(), this.getAt(1).lng(), 0, indexes[2], $.proxy(iM.boundsHaveChanged, iM));
              }
            })([edges[i].start.id, edges[i].dest.id, edges[i].id],isocronMap));
            google.maps.event.addListener(this.edges[i].getPath(), 'insert_at', (function(indexes, iM) {
              return function() {
                databaseWrapper.cutEdge(indexes[0], indexes[1], this.getAt(1).lat(), this.getAt(1).lng(), 0, indexes[2], $.proxy(iM.boundsHaveChanged, iM));
              }
            })([edges[i].start.id, edges[i].dest.id, edges[i].id], isocronMap));
            // ADMIN ------------------------------------------------------------------------------------------------------------
<?php endif ?>
        };

        if (display == true) this.displayDataOverlay();
    };

    this.setClosestOverlay = function(closestPoint, display){

        var position = new google.maps.LatLng(closestPoint.lat, closestPoint.lng);

        if (this.closestPoint != null && this.closestPoint.getPosition().equals(position)) {
            return; // we will not display again
        } else {
            this.removeClosestOverlay();
            this.closestPoint = new google.maps.Marker();
            this.closestPoint.setIcon(this.closestPointPinImage);
            this.closestPoint.setPosition(position);
            if (display == true) this.displayClosestOverlay();
        }

    };

    this.displayDataOverlay = function(){

        if (this.edges) {
            for (i in this.edges) {
                this.edges[i].setMap(this.map);
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
              this.edges[i].setMap(null);
            }
        }
    };

    this.removeClosestOverlay = function(){

        if (this.closestPoint){
            this.closestPoint.setMap(null);
        }

    };

}