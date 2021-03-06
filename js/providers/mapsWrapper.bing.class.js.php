<?php header('Content-type: application/javascript'); ?>
/* Maps Wrapper
 * Bing Maps
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

        this.ownCallback = false;
        this.delay = 2000;

        /* In case it's already been loaded */
        if (typeof Microsoft !== 'undefined') return '';

        return "http://ecn.dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=7.0";

    };

    this.createMap = function(genericOptions){

        switch(this.type){
            case 'bing-hybrid':
                var mapTypeId = Microsoft.Maps.MapTypeId.aerial;
                break;
            case 'bing-road':
            default:
                var mapTypeId = Microsoft.Maps.MapTypeId.road;
                break;
        }

        var options = {
            credentials: genericOptions.apiKeys.bing,
            center: new Microsoft.Maps.Location(genericOptions.center.lat, genericOptions.center.lng),
            mapTypeId: mapTypeId,
            zoom: 17,
            //theme: new Microsoft.Maps.Themes.BingTheme(),
            enableSearchLogo: false,
            enableClickableLogo: false,
            disableBirdseye: true,
            showBreadcrumb: false,
            showMapTypeSelector: false,
        };

        /*Construct an instance of Bing maps with the options object*/ 
        this.map = new Microsoft.Maps.Map(document.getElementById(genericOptions.canvas), options);

        this.positionCallback = genericOptions.positionCallback;

        this.colorsForType = genericOptions.colorsForType;
        this.thicknessesForType = genericOptions.thicknessesForType;
        this.zIndexesForType = genericOptions.zIndexesForType;
        this.standardPinImage = genericOptions.standardPinImage;
        this.closestPointPinImage = genericOptions.closestPointPinImage;

        /*Click for adding pins*/
        Microsoft.Maps.Events.addHandler(this.map, 'click', $.proxy(function(e){this.addMarker(e);},this));

        /*Update visible overlay*/
        Microsoft.Maps.Events.addHandler(this.map, 'viewchangeend', genericOptions.boundsHaveChangedCallback);

        /*Geocoding*/
        Microsoft.Maps.loadModule('Microsoft.Maps.Search', { callback: $.proxy(function(){

            this.searchManager = new Microsoft.Maps.Search.SearchManager(this.map);

            genericOptions.mapReadyCallback();

        }, this)});

    };


    this.setupEvents = function(genericOptions){

        $('#'+genericOptions.searchInput).keyup($.proxy(function(){

            if (event.target.value == "" || event.keyCode == 27) {
                $('#multipleChoices').hide();
                $('#'+genericOptions.searchInput).blur();
            } else {

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
                this.searchManager.geocode(geocodeRequest);
            }
        }, this));

    };

    this.setAddPin = function(booleanValue){
        this.addPin = booleanValue;
    };

    this.setAddEdge = function(booleanValue){
        this.addEdge = booleanValue;
    };

    this.addMarker = function(event){

        if (event.targetType == "map" && this.addPin == true) {
            var point = new Microsoft.Maps.Point(event.getX(), event.getY());
            var loc = event.target.tryPixelToLocation(point);
            this.setPosition(loc.latitude,loc.longitude, null);
        }

    }

    this.setPosition = function(lat, lng, description){

        if (this.map && lat != null && lng != null) {

            this.position = new Microsoft.Maps.Location(lat, lng);
            this.map.setView({
                center: this.position
            });
            
            // Creates the marker & infoWindow
            if (!this.marker) {
                this.marker = new Microsoft.Maps.Pushpin(this.position, {zIndex: 11, icon: this.standardPinImage, height: 50, width: 34});
            } else {
                this.marker.setLocation(this.position);
            }

            this.map.entities.push(this.marker);

            if (description != null){
            
                // TODO, display the address / description somewhere?

            }

            this.positionCallback(lat, lng);
        }

    };

    this.getBoundsAsLatLng = function(){

        var bounds = this.map.getBounds();

            var NW = bounds.getNorthwest();
            var SE = bounds.getSoutheast();

        return {NW_lat:NW.latitude, NW_lng:NW.longitude, SE_lat:SE.latitude, SE_lng:SE.longitude};
        
    };
    
    this.setDataOverlay = function(edges, limit, display){

        this.removeDataOverlay();
        this.edges.length = 0;

        var count = edges.length;
        var startPoint = null, destPoint = null;
        var currentLine = null;

        this.edgesCollection = new Microsoft.Maps.EntityCollection();

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

            currentLine = new Microsoft.Maps.Polyline([
                new Microsoft.Maps.Location(startPoint.lat, startPoint.lng),
                new Microsoft.Maps.Location(destPoint.lat, destPoint.lng)
            ], {
                strokeColor:new Microsoft.Maps.Color.fromHex(this.colorsForType[edges[i].type]), 
                strokeThickness: this.thicknessesForType[edges[i].type],
                //zIndex: this.zIndexesForType[edges[i].type] // not working ;(
            });
            this.edgesCollection.push(currentLine);

        };

        if (display === true) this.displayDataOverlay();

    };

    this.setClosestOverlay = function(closestPoint, display){

        var position = new Microsoft.Maps.Location(closestPoint.lat, closestPoint.lng);

        if (this.closestPoint != null && Microsoft.Maps.Location.areEqual(position, this.closestPoint.getLocation())) {
            return; // we will not display again
        } else {
            this.removeClosestOverlay();
            this.closestPoint = new Microsoft.Maps.Pushpin(position,{
                icon: this.closestPointPinImage, height: 50, width: 34, zIndex: 12
            });
            if (display == true) this.displayClosestOverlay();
        }

    };

    this.displayDataOverlay = function(){

        if (this.edgesCollection) {
            this.map.entities.push(this.edgesCollection);
        }
    };

    this.displayClosestOverlay = function(){

        if (this.closestPoint) {
            this.map.entities.push(this.closestPoint);
        }

    };

    this.removeDataOverlay = function(){

        if (this.edgesCollection) {
            this.map.entities.remove(this.edgesCollection);
        }
        this.previousLimit = 0;
    };

    this.removeClosestOverlay = function(){

        if (this.closestPoint) {
            this.map.entities.remove(this.closestPoint);
        }

    };
}