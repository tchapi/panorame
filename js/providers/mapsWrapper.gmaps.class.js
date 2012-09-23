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
        this.addEdgeCallback = genericOptions.addEdgeCallback;

        this.colors = genericOptions.colors;
        this.thicknesses = genericOptions.thicknesses;

        genericOptions.mapReadyCallback();
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
          genericOptions.updateOverlayCallback();
        });

    };

    this.setAddPin = function(booleanValue){
        this.addPin = booleanValue;
    };

    this.setAddEdge = function(booleanValue){
        this.addEdge = booleanValue;
    };

    this.clickListener = function(event){
        if (this.addPin == true) this.setPosition(event.latLng.lat(), event.latLng.lng(), null);
        if (this.addEdge === true) {
            this.addEdge = {lat:event.latLng.lat(), lng:event.latLng.lng()};
            this.addEdgeEnd = true;
            console.log('Point A (start) :' + this.addEdge.lat + ' - ' + this.addEdge.lng + ' | Waiting for point B ...');
        } else if (this.addEdgeEnd === true) {
            this.addEdgeCallback(this.addEdge.lat, this.addEdge.lng, 0,event.latLng.lat(), event.latLng.lng(), 0, 0);
            this.addEdge = true;
            this.addEdgeEnd = false;
            console.log('Point B (dest) :' + event.latLng.lat() + ' - ' + event.latLng.lng() + ' | Adding the edge now.');
        }
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

            var NE = bounds.getNorthEast();
            var SW = bounds.getSouthWest();

        return {NW_lat:NE.lat(), NW_lng:SW.lng(), SE_lat:SW.lat(), SE_lng:NE.lng()};

    };

    this.setEdgesAndDisplay = function(edges, count){

        this.removeOverlays();
        this.edges.length = 0;

        for(var i = 0; i < count; i++) {
            this.edges[i] = new google.maps.Polyline({
              path: [
                new google.maps.LatLng(edges[i].start.point.lat, edges[i].start.point.lng),
                new google.maps.LatLng(edges[i].dest.point.lat, edges[i].dest.point.lng)],
              strokeColor: this.colors[edges[i].type],
              strokeWeight: this.thicknesses[edges[i].type]
            });
            google.maps.event.addListener(this.edges[i], 'click', $.proxy(this.clickListener, this));

        };

        this.displayOverlays();

    };

    this.setClosestPointAndDisplay = function(){


    };

    this.displayOverlays = function(){

        if (this.edges) {
            for (i in this.edges) {
              this.edges[i].setMap(this.map);
            }
        }

    };

    this.removeOverlays = function(){

        if (this.edges) {
            for (i in this.edges) {
              this.edges[i].setMap(null);
            }
        }

    };

}