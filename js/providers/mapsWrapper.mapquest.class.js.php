<?php header('Content-type: application/javascript'); ?>
/* Maps Wrapper
 * Map Quest
 */
var mapsWrapper = function(type) {

    // Singleton Stuff
    if ( arguments.callee._singletonInstance )
    return arguments.callee._singletonInstance;
    arguments.callee._singletonInstance = this;

    this.type = type;
    this.map = null;
    this.edges = [];
    this.previousLimit = 0;
        
    this.getUrl = function(genericOptions){

        this.ownCallback = false;
        this.delay = 0;

        /* In case it's already been loaded */
        if (typeof MQA !== 'undefined') return '';

        return "http://www.mapquestapi.com/sdk/js/v7.0.s/mqa.toolkit.js?key=" + genericOptions.apiKeys.mapquest;

    };

    this.createMap = function(genericOptions){

        var options = {
          elt: document.getElementById(genericOptions.canvas),     /*ID of element on the page where you want the map added*/ 
          zoom:17,                         /*initial zoom level of the map*/ 
          latLng:genericOptions.center,  /*center of map in latitude/longitude */ 
          mtype:'map'                      /*map type (map)*/ 
        };

        /*Construct an instance of MQA.TileMap with the options object*/ 
        this.map = new MQA.TileMap(options);

        this.positionCallback = genericOptions.positionCallback;

        this.colorsForType = genericOptions.colorsForType;
        this.thicknessesForType = genericOptions.thicknessesForType;
        this.zIndexesForType = genericOptions.zIndexesForType;
        this.standardPinImage = genericOptions.standardPinImage;
        this.closestPointPinImage = genericOptions.closestPointPinImage;

        this.markerIcon = new MQA.Icon(this.standardPinImage,34,50);


        MQA.EventManager.addListener(this.map, 'click', $.proxy(function(e){
          if (this.addPin == true) this.setPosition(e.ll.lat, e.ll.lng, null);
        }, this));

        MQA.EventManager.addListener(this.map, 'moveend', function(e){
          genericOptions.boundsHaveChangedCallback();
        });

        MQA.EventManager.addListener(this.map, 'zoomend', function(e){
          genericOptions.boundsHaveChangedCallback();
        });

        MQA.withModule('viewoptions','largezoom','geocoder', 'shapes', $.proxy(function() {

          this.map.addControl(
            new MQA.ViewOptions()
          );
          this.map.addControl(
            new MQA.LargeZoom(),
            new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT, new MQA.Size(5,5))
          );
          
          genericOptions.mapReadyCallback();

        }, this));

    };

    this.setupEvents = function(genericOptions){

        $('#'+genericOptions.searchInput).keyup($.proxy(function(){

            if (event.target.value == "" || event.keyCode == 27) { // ECHAP
                $('#multipleChoices').hide();
                $('#'+genericOptions.searchInput).blur();
            } else {
                // Beurk mais obligé ... seulement Paris
                MQA.Geocoder.geocode({street: event.target.value, city: 'Paris', country: 'France'}, null, null, $.proxy(function(response){
                            
                    var results = "";
                    if (!response.results) return false;

                    for (i = 0; i < response.results.length; i++) {
                        for (j=0; j<response.results[i].locations.length; j++) {
                            var location = response.results[i].locations[j];
                            if (location.geocodeQualityCode.charAt(0) != 'A') {
                              results += '<li lat="'+ location.latLng.lat +'" lng="'+ location.latLng.lng +'"> ';
                              results += ' ' + (location.street || ' ');
                              results += ' ' + (location.adminArea5 || ' ');
                              results += ' ' + (location.adminArea4 || ' ');
                              results += ' ' + (location.adminArea3 || ' ');
                              results += ' ' + (location.adminArea2 || ' ');
                              results += ' ' + (location.postalCode || ' ');
                              results += ' ' + (location.adminArea1 || ' ') +'</li>';
                            }
                        }
                    }

                    if (results != "") {
                      $('#multipleChoices').show();
                      $('#multipleChoices').html(results);

                      $('#multipleChoices li').click($.proxy(function(event){
                        $('#multipleChoices').hide();
                        this.setPosition(event.target.getAttribute('lat'), event.target.getAttribute('lng'), event.target.innerHTML);
                      }, this));
                    }

                }, this));
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

            // Creates the marker
            this.map.removeAllShapes();
            this.marker = new MQA.Poi({lat:lat, lng:lng});
            this.marker.setIcon(this.markerIcon);
            this.marker.setShadow(false);
            this.marker.setBias({x:0,y:-25});
            this.map.addShape(this.marker);
            this.marker.setDraggable(false);

            this.map.savedCenter = {lat:lat, lng:lng};
            this.map.slideMapToPoint(this.map.llToPix({lat:lat, lng:lng}));

            if (description != null){
            
                // TODO, display the address / description somewhere?

            }
            
            this.positionCallback(lat, lng);

        }

    };

    this.getBoundsAsLatLng = function(){

        var bounds = this.map.getBounds();

            var NW = bounds.ul;
            var SE = bounds.lr;

        return {NW_lat:NW.lat, NW_lng:NW.lng, SE_lat:SE.lat, SE_lng:SE.lng};

    };
    
    this.setDataOverlay = function(edges, limit, display){

        if (display == true) this.removeDataOverlay();
        this.edges.length = 0;

        var count = edges.length;
        var startPoint = null, destPoint = null;
        var currentLine = null;

        this.edgesCollection = new MQA.ShapeCollection();
        this.edgesCollection.collectionName = 'edges';

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

            currentLine = new MQA.LineOverlay();
            currentLine.setShapePoints([startPoint.lat, startPoint.lng, destPoint.lat, destPoint.lng]);
            this.edgesCollection.add(currentLine);
            currentLine.color = this.colorsForType[edges[i].type];
            currentLine.borderWith = this.thicknessesForType[edges[i].type];
            currentLine.zIndex = this.zIndexesForType[edges[i].type];

        };

        if (display == true) this.displayDataOverlay();
    };

    this.setClosestOverlay = function(closestPoint, display){

        var position = {lat:closestPoint.lat, lng:closestPoint.lng};

        if (this.closestPoint != null && position == this.closestPoint.latLng) {
            return; // we will not display again
        } else {
            this.removeClosestOverlay();
            this.closestPoint = new MQA.Poi(position);
            this.closestPoint.setIcon(new MQA.Icon(this.closestPointPinImage,34,50));
            this.closestPoint.setShadow(false);
            this.closestPoint.setBias({x:0,y:-25});
            if (display == true) this.displayClosestOverlay();
        }

    };

    this.displayDataOverlay = function(){

        if (this.edgesCollection) {
            this.map.addShapeCollection(this.edgesCollection);
        }
        
    };

    this.displayClosestOverlay = function(){

        if (this.closestPoint) {
            this.map.addShape(this.closestPoint);
            this.closestPoint.setDraggable(false);
        }

    };

    this.removeDataOverlay = function(){

        this.map.removeShapeCollection('edges');
        this.previousLimit = 0;
    };

    this.removeClosestOverlay = function(){

        if (this.closestPoint) {
            this.map.removeShape(this.closestPoint);
        }

    };
}