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
        this.delay = 500;
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

        var options = {
            center: [genericOptions.center.lat, genericOptions.center.lng],
            zoomLevel: 16,
            components: [ 
                new nokia.maps.map.component.Behavior(),
                new nokia.maps.map.component.ZoomBar()
                // add kinetics
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

        $('#'+genericOptions.searchInput).keyup($.proxy(function(){

            if (event.target.value == "" || event.keyCode == 27) {
                $('#multipleChoices').hide();
                $('#'+genericOptions.searchInput).blur();
            } else {
/*
                var geocodeRequest = {where:event.target.value, count:10, callback:$.proxy(function(response, userData){

                    var results = "";
                    if (!response) return false;
                    
                    for (i = 0; i < response.results.length; i++) {
                        var location = response.results[i];
                          results += '<li lat="'+ location.location.latitude +'" lng="'+ location.location.longitude +'"> ';
                          results += location.name +'</li>';
                    }

                    if (results != "") {
                      $('#multipleChoices').show();
                      $('#multipleChoices').html(results);

                      $('#multipleChoices li').click($.proxy(function(event){
                        $('#multipleChoices').hide();
                        this.setPosition(event.target.getAttribute('lat'), event.target.getAttribute('lng'), event.target.innerHTML);
                      }, this));
                    }

                }, this)};
                this.searchManager.geocode(geocodeRequest); */
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

            this.position = new nokia.maps.geo.Coordinate(lat, lng);
            this.map.setCenter(this.position, 'default');
            
            // Creates the marker & infoWindow
            if (this.marker) {
                this.marker.destroy();
            }
            
            this.marker = new nokia.maps.map.Marker(this.position, {icon: this.standardPinImage, visibility: true, draggable: false, height: 50, width: 34, anchor: new nokia.maps.util.Point(17, 42) });

            this.map.objects.add(this.marker);
/*
            if (description != null){
                // Displays the infoWindow
               if (!this.infoWindow) {
                    this.infoWindow = new Microsoft.Maps.Infobox(this.position);
                    this.map.entities.push(this.infoWindow);
                } else {
                    this.infoWindow.setLocation(this.position)
                }
                this.infoWindow.setOptions({ title:description, zIndex: 10});
                this.infoWindow.setOptions({ visible:true });
            } else {
                if (this.infoWindow) this.infoWindow.setOptions({ visible:false });
            }

            this.marker.setOptions({infobox: this.infoWindow});
*/
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
                new nokia.maps.geo.Coordinate(startPoint.lat, startPoint.lng),
                new nokia.maps.geo.Coordinate(destPoint.lat, destPoint.lng)
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

        var position = new nokia.maps.geo.Coordinate(closestPoint.lat, closestPoint.lng);

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