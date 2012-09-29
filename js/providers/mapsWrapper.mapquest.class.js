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
        
    this.getUrl = function(genericOptions){

        this.ownCallback = false;
        this.delay = 0;
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
        this.standardPinImage = genericOptions.standardPinImage;
        this.closestPointPinImage = genericOptions.closestPointPinImage;

        this.markerIcon = new MQA.Icon(this.standardPinImage,34,50);


        MQA.EventManager.addListener(this.map, 'click', $.proxy(function(e){
          if (this.addPin == true) this.setPosition(e.ll.lat, e.ll.lng, null);
        }, this));

        MQA.EventManager.addListener(this.map, 'moveend', function(e){
          genericOptions.updateOverlayCallback();
        });

        MQA.EventManager.addListener(this.map, 'zoomend', function(e){
          genericOptions.updateOverlayCallback();
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
            this.marker.setBias({x:10,y:0});
            this.map.addShape(this.marker);
            this.marker.setDraggable(false);

            this.map.savedCenter = {lat:lat, lng:lng};
            this.map.slideMapToPoint(this.map.llToPix({lat:lat, lng:lng}));

            if (description != null){
                // Displays the infoWindow
                this.marker.setInfoContentHTML(description);
                this.marker.toggleInfoWindow();
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
    
    this.setDataOverlay = function(edges, closestPoint, limit){

        this.removeDataOverlay();
        this.edges.length = 0;

        var count = edges.length;

        this.edgesCollection = new MQA.ShapeCollection();
        this.edgesCollection.collectionName = 'edges';

        for(var i = 0; i < count; i++) {
            
            if (limit == null || (edges[i].start.cost < limit && edges[i].dest.cost < limit)){
            
                this.edges[i] = new MQA.LineOverlay();
                this.edges[i].setShapePoints([edges[i].start.point.lat, edges[i].start.point.lng, edges[i].dest.point.lat, edges[i].dest.point.lng]);
                this.edgesCollection.add(this.edges[i]);
                this.edges[i].color = this.colorsForType[edges[i].type];
                this.edges[i].borderWith = this.thicknessesForType[edges[i].type];

            } else if (edges[i].start.cost < limit && edges[i].dest.cost > limit){

                 // semi-distance at the end of a leaf
                var percent = (limit-edges[i].start.cost)/(edges[i].dest.cost - edges[i].start.cost);
                var newPoint = {lat: edges[i].start.point.lat + (edges[i].dest.point.lat - edges[i].start.point.lat)*percent, lng: edges[i].start.point.lng + (edges[i].dest.point.lng - edges[i].start.point.lng)*percent};

                this.edges[i] = new MQA.LineOverlay();
                this.edges[i].setShapePoints([edges[i].start.point.lat, edges[i].start.point.lng, newPoint.lat, newPoint.lng]);
                this.edgesCollection.add(this.edges[i]);
                this.edges[i].color = this.colorsForType[edges[i].type];
                this.edges[i].borderWith = this.thicknessesForType[edges[i].type];

            }

        };

        this.closestPoint = new MQA.Poi({lat:closestPoint.lat, lng:closestPoint.lng});
        this.closestPoint.setIcon(new MQA.Icon(this.closestPointPinImage,34,50));
        this.closestPoint.setShadow(false);
        this.closestPoint.setBias({x:10,y:0});
        this.edgesCollection.add(this.closestPoint);

        this.displayDataOverlay();

    };

    this.displayDataOverlay = function(){

        if (this.edgesCollection) {
            this.map.addShapeCollection(this.edgesCollection);
            this.closestPoint.setDraggable(false);
        }
        
    };

    this.removeDataOverlay = function(){

        this.map.removeShapeCollection('edges');

    };

}