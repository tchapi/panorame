<?php header('Content-type: application/javascript'); ?>
/* Maps Wrapper
 * Nokia Maps
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
        this.delay = 1000;

        /* In case it's already been loaded */
        if (typeof nokia !== 'undefined') return '';

        return "http://api.maps.nokia.com/2.2.3/jsl.js?with=maps,positioning,placesdata";

    };

    this.createMap = function(genericOptions){

        nokia.Settings.set("appId", genericOptions.apiKeys.nokia.appId); 
        nokia.Settings.set("authenticationToken", genericOptions.apiKeys.nokia.token);

        switch(this.type){
            case 'nokia-terrain':
                var baseMapType = nokia.maps.map.Display.TERRAIN;
                break;
            case 'nokia-hybrid':
                var baseMapType = nokia.maps.map.Display.SATELLITE;
                break;
            case 'nokia-road':
            default:
                var baseMapType = nokia.maps.map.Display.NORMAL;
                break;
        }

        this.infoBubble = new nokia.maps.map.component.InfoBubbles();

        var options = {
            center: [genericOptions.center.lat, genericOptions.center.lng],
            zoomLevel: 16,
            components: [ 
                new nokia.maps.map.component.Behavior(),
                new nokia.maps.map.component.ZoomBar(),
                this.infoBubble
            ],
        };

        /*Construct an instance of Nokia maps with the options object*/ 
        this.map = new nokia.maps.map.Display(document.getElementById(genericOptions.canvas), options);

        /* Sets the type */
        this.map.set("baseMapType", baseMapType);

        this.positionCallback = genericOptions.positionCallback;

        this.colorsForType = genericOptions.colorsForType;
        this.thicknessesForType = genericOptions.thicknessesForType;
        this.zIndexesForType = genericOptions.zIndexesForType;
        this.standardPinImage = genericOptions.standardPinImage;
        this.closestPointPinImage = genericOptions.closestPointPinImage;

        /*Click for adding pins*/
        var TOUCH = nokia.maps.dom.Page.browser.touch,
            CLICK = TOUCH ? "tap" : "click";

        this.map.addListener(CLICK, $.proxy(function(e){
            var converted = this.map.pixelToGeo(e.displayX, e.displayY);
            if (this.addPin == true) this.setPosition(converted.latitude, converted.longitude, null);
        },this));

        /*Update visible overlay*/
        this.map.addListener("mapviewchange", genericOptions.boundsHaveChangedCallback);

        genericOptions.mapReadyCallback();

    };


    this.setupEvents = function(genericOptions){

        /* Geocoding */
        this.searchManager = nokia.places.search.manager;

        $('#'+genericOptions.searchInput).keyup($.proxy(function(){

            if (event.target.value == "" || event.keyCode == 27) {
                $('#multipleChoices').hide();
                $('#'+genericOptions.searchInput).blur();
            } else {

                this.searchManager.geoCode({
                    searchTerm: event.target.value,
                    limit: 10,
                    onComplete: $.proxy(function (data, requestStatus, requestId) {

                    var results = "";
                    if (requestStatus != "OK") return false;

                    // The function findPlaces() and reverseGeoCode() of  return results in slightly different formats
                    locations = data.results ? data.results.items : [data.location];    

                    for (i = 0; i < locations.length; i++) {
                        results += '<li lat="'+ locations[i].position.latitude +'" lng="'+ locations[i].position.longitude +'"> ';
                        results += locations[i].address.text +'</li>';
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

    this.setPosition = function(lat, lng, description){

        if (this.map && lat != null && lng != null) {

            this.position = new nokia.maps.geo.Coordinate(parseFloat(lat), parseFloat(lng));
            this.map.setCenter(this.position, 'default');
            
            // Creates the marker & infoWindow
            if (this.marker) {
                this.marker.destroy();
            }
            
            this.marker = new nokia.maps.map.Marker(this.position, {icon: this.standardPinImage, visibility: true, draggable: false, height: 50, width: 34, anchor: new nokia.maps.util.Point(17, 42) });

            this.map.objects.add(this.marker);

            if (description != null){
            
                // TODO, display the address / description somewhere?

            }

            this.positionCallback(lat, lng);
        }

    };

    this.getBoundsAsLatLng = function(){

        var bounds = this.map.getViewBounds();

            var SE = bounds.bottomRight;
            var NW = bounds.topLeft;

        return {NW_lat:NW.latitude, NW_lng:NW.longitude, SE_lat:SE.latitude, SE_lng:SE.longitude};
        
    };
    
    this.setDataOverlay = function(edges, limit, display){

        this.removeDataOverlay();
        this.edges.length = 0;

        var count = edges.length;
        var startPoint = null, destPoint = null;
        var currentLine = null;

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

            currentLine = new nokia.maps.map.Polyline([
                new nokia.maps.geo.Coordinate(parseFloat(startPoint.lat), parseFloat(startPoint.lng)),
                new nokia.maps.geo.Coordinate(parseFloat(destPoint.lat), parseFloat(destPoint.lng))
            ], { pen: {
                strokeColor: this.colorsForType[edges[i].type], 
                lineWidth: this.thicknessesForType[edges[i].type]
            }});
            
            // Pushes the line into the array
            this.edges.push(currentLine);

        };
 
        if (display === true) this.displayDataOverlay();

    };

    this.setClosestOverlay = function(closestPoint, display){

        var position = new nokia.maps.geo.Coordinate(parseFloat(closestPoint.lat), parseFloat(closestPoint.lng));

        if (this.closestPoint != null && position.equals(this.closestPoint.coordinate) ) {
            return; // we will not display again
        } else {
            this.removeClosestOverlay();
            this.closestPoint = new nokia.maps.map.Marker(position, {icon: this.closestPointPinImage, visibility: true, draggable: false, height: 50, width: 34, anchor: new nokia.maps.util.Point(17,42) });
            if (display == true) this.displayClosestOverlay();
        }

    };

    this.displayDataOverlay = function(){

        if (this.edges) {
            for (i in this.edges) {
                this.map.objects.add(this.edges[i]);
            }
        }
    };

    this.displayClosestOverlay = function(){

        if (this.closestPoint){
            this.map.objects.add(this.closestPoint);
        }

    };

    this.removeDataOverlay = function(){

        if (this.edges) {
            for (i in this.edges) {
              this.map.objects.remove(this.edges[i]);
            }
        }
    };

    this.removeClosestOverlay = function(){

        if (this.closestPoint){
            this.map.objects.remove(this.closestPoint);
        }

    };
}