<?php header('Content-type: application/javascript'); ?>
/* Maps Wrapper
 * Open Street Maps
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

        this.ownCallback = false;
        this.delay = 0;
        return "http://openlayers.org/api/OpenLayers.js";

    };

    this.createMap = function(genericOptions){

        var options = {
            zoom: 15,
            controls: [
                      new OpenLayers.Control.PanZoomBar(),
                      new OpenLayers.Control.Navigation()
                      ],
            eventListeners: {
                        "click": $.proxy(function(e){
                            if (this.addPin == true) {
                                var lonlat = this.map.getLonLatFromPixel(e.xy);
                                lonlat = this.inverseMercator(lonlat.lat, lonlat.lon);
                                this.setPosition(lonlat.lat, lonlat.lng, null);
                            }
                        }, this),
                        "moveend": genericOptions.boundsHaveChangedCallback,
                        "zoomend": genericOptions.boundsHaveChangedCallback
                    },
            center: new OpenLayers.LonLat(genericOptions.center.lng, genericOptions.center.lat)
        };

        this.map = new OpenLayers.Map(genericOptions.canvas, options);
        
        switch(this.type){
            case 'gmaps-terrain':
                var tiles = new OpenLayers.Layer.Google('base', {type: google.maps.MapTypeId.TERRAIN});
                break;
            case 'gmaps-road':
                var tiles = new OpenLayers.Layer.Google('base');
                break;
            case 'gmaps-hybrid':
                var tiles = new OpenLayers.Layer.Google('base', {type: google.maps.MapTypeId.HYBRID});
                break;
            case 'bing-road':
                var tiles = new OpenLayers.Layer.Bing('base',{key: genericOptions.apiKeys.bing,type: "Road", wrapDateLine: true});
                break;
            case 'bing-hybrid':
                var tiles = new OpenLayers.Layer.Bing('base',{key: genericOptions.apiKeys.bing,type: "AerialWithLabels", wrapDateLine: true});
                break;
            case 'osmaps':
            default:
                var tiles = new OpenLayers.Layer.OSM('base');
        }

        this.map.addLayer(tiles);

        this.markers = new OpenLayers.Layer.Markers( "Markers" );
        this.map.addLayer(this.markers);

        this.positionCallback = genericOptions.positionCallback;

        this.colorsForType = genericOptions.colorsForType;
        this.thicknessesForType = genericOptions.thicknessesForType;
        this.standardPinImage = genericOptions.standardPinImage;
        this.closestPointPinImage = genericOptions.closestPointPinImage;

        var size = new OpenLayers.Size(34,50);
        this.markerIcon = new OpenLayers.Icon(this.standardPinImage, size, new OpenLayers.Pixel(-(size.w/2), -size.h*0.9));

        genericOptions.mapReadyCallback();
    };

    this.setupEvents = function(genericOptions){

        $('#'+genericOptions.searchInput).keyup($.proxy(function(){

            if (event.target.value == "" || event.keyCode == 27) {
                $('#multipleChoices').hide();
                $('#'+genericOptions.searchInput).blur();
            } else {

                $.ajax({
                    url : 'http://nominatim.openstreetmap.org/search?q='+event.target.value+'&format=json&limit=5&addressdetails=1',
                    dataType : 'jsonp',
                    jsonp: 'json_callback',
                    success : $.proxy(function(response){

                        var results = "";
                        if (!response) return false;
                        
                        for (i = 0; i < response.length; i++) {
                            var location = response[i];
                              results += '<li lat="'+ location.lat +'" lng="'+ location.lon +'"> ';
                              if (location.address[location.type]) results += location.address[location.type] + ', ';
                              results += ' ' + (location.address.station || ' ');
                              results += ' ' + (location.address.house_number || ' ');
                              results += ' ' + (location.address.road || ' ');
                              results += ' ' + (location.address.postcode || ' ');
                              results += ' ' + (location.address.city || ' ');
                              results += ' ' + (location.address.country || ' ');
                        }

                        if (results != "") {
                          $('#multipleChoices').show();
                          $('#multipleChoices').html(results);

                          $('#multipleChoices li').click($.proxy(function(event){
                            $('#multipleChoices').hide();
                            this.setPosition(event.target.getAttribute('lat'), event.target.getAttribute('lng'), event.target.innerHTML);
                          }, this));
                        }

                }, this)});
    
            }
        }, this));

    };

    this.setAddPin = function(booleanValue){
        this.addPin = booleanValue;
    };

    this.setAddEdge = function(booleanValue){
        this.addEdge = booleanValue;
    };

    this.inverseMercator = function(unproj_lat, unproj_lng){
        lonlat = OpenLayers.Layer.SphericalMercator.inverseMercator(unproj_lng, unproj_lat);
        return {lat:lonlat.lat, lng:lonlat.lon};
    };

    this.convertTo4326 = function(lat, lng){
        return new OpenLayers.LonLat(lng, lat).transform(new OpenLayers.Projection("EPSG:4326"), this.map.getProjectionObject());
    };

    this.pointFromLonLat = function(lat, lng){
        var convertedPoint = this.convertTo4326(lat, lng);
        return new OpenLayers.Geometry.Point(convertedPoint.lon, convertedPoint.lat); 
    };

    this.setPosition = function(lat, lng, description){

        if (this.map && lat != null && lng != null) {

            this.position = this.convertTo4326(lat, lng);

            // Destroys the marker
            if (this.marker) {
                this.marker.erase();
            }
            this.marker = new OpenLayers.Marker(this.position, this.markerIcon);
            this.markers.addMarker(this.marker);

            this.map.panTo(this.position);    
   
            if (description != null){
                // Displays the infoWindow
                if (!this.infoWindow) {
                    this.infoWindow = new OpenLayers.Popup.FramedCloud("Popup", this.position, null, description, null, true);
                    this.map.addPopup(this.infoWindow);
                } else {
                    this.infoWindow.lonlat = this.position;
                    this.infoWindow.updatePosition();
                    this.infoWindow.show();
                }
            } else{
                if (this.infoWindow) this.infoWindow.hide(); 
            }

            this.positionCallback(lat, lng);

        }
            
    };

    this.getBoundsAsLatLng = function(){

        var bounds = this.map.getExtent();

            var NW = this.inverseMercator(bounds.top, bounds.left);
            var SE = this.inverseMercator(bounds.bottom, bounds.right);

        return {NW_lat:NW.lat, NW_lng:NW.lng, SE_lat:SE.lat, SE_lng:SE.lng};

    };
      
    this.setDataOverlay = function(edges, limit, display){

        if (display == true) this.removeDataOverlay();
        this.edges.length = 0;

        var count = edges.length;
        var startPoint = null, destPoint = null;
        var currentLine = null;

        this.edgesCollection = new OpenLayers.Layer.Vector("edges");

        for(var i = 0; i < count; i++) {
            
            if (limit == null || (edges[i].start.cost < limit && edges[i].dest.cost < limit)){

                startPoint = edges[i].start.point;
                destPoint = edges[i].dest.point;

            } else if (edges[i].start.cost < limit && edges[i].dest.cost > limit && edges[i].secable == 1){
                
                // semi-distance at the end of a leaf
                startPoint = edges[i].start.point;
                var percent = (limit-edges[i].start.cost)/(edges[i].dest.cost - edges[i].start.cost);
                destPoint = {lat: edges[i].start.point.lat + (edges[i].dest.point.lat - edges[i].start.point.lat)*percent, lng: edges[i].start.point.lng + (edges[i].dest.point.lng - edges[i].start.point.lng)*percent};
                
            } else { continue; }

            currentLine = new OpenLayers.Feature.Vector(new OpenLayers.Geometry.LineString([
                this.pointFromLonLat(startPoint.lat, startPoint.lng),
                this.pointFromLonLat(destPoint.lat, destPoint.lng)
            ]));
            currentLine.style = {
                strokeColor: this.colorsForType[edges[i].type],
                strokeWidth: this.thicknessesForType[edges[i].type]
            };

            this.edges.push(currentLine);

        };

        this.edgesCollection.addFeatures(this.edges);
        if (display == true) this.displayDataOverlay();

    };

    this.setClosestOverlay = function(closestPoint, display){

        var position = this.convertTo4326(closestPoint.lat, closestPoint.lng);

        if (this.closestPoint != null && position.lat == this.closestPoint.lonlat.lat && position.lng == this.closestPoint.lonlat.lng) {
            return; // we will not display again
        } else {
            this.removeClosestOverlay();
            var size = new OpenLayers.Size(34,50);
            this.closestPoint = new OpenLayers.Marker(position, new OpenLayers.Icon(this.closestPointPinImage, size, new OpenLayers.Pixel(-size.w/2, -size.h*0.9)));

            if (display == true) this.displayClosestOverlay();
        }

    };

    this.displayDataOverlay = function(){

        if (this.edgesCollection) {
            this.map.addLayer(this.edgesCollection);
            this.map.setLayerIndex(this.edgesCollection, 0);
        }
    };

    this.displayClosestOverlay = function(){

        if (this.closestPoint) {
            this.markers.addMarker(this.closestPoint);
        }
        
    };

    this.removeDataOverlay = function(){

        if (this.edgesCollection) {
            this.map.removeLayer(this.edgesCollection);
        }

    };

    this.removeClosestOverlay = function(){

        if (this.closestPoint) {
            this.markers.removeMarker(this.closestPoint);
        }

    };
}