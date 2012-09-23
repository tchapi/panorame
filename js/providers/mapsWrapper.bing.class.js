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

    this.getUrl = function(genericOptions){

        this.ownCallback = false;
        this.delay = 1000;
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

        this.colors = genericOptions.colors;
        this.thicknesses = genericOptions.thicknesses;

        /*Click for adding pins*/
        Microsoft.Maps.Events.addHandler(this.map, 'click', $.proxy(function(e){this.addMarker(e);},this));

        /*Update visible overlay*/
        Microsoft.Maps.Events.addHandler(this.map, 'viewchangeend', genericOptions.updateOverlayCallback);

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
                this.marker = new Microsoft.Maps.Pushpin(this.position);
            } else {
                this.marker.setLocation(this.position);
            }

            this.map.entities.push(this.marker);

            if (description != null){
                // Displays the infoWindow
               if (!this.infoWindow) {
                    this.infoWindow = new Microsoft.Maps.Infobox(this.position);
                    this.map.entities.push(this.infoWindow);
                } else {
                    this.infoWindow.setLocation(this.position)
                }
                this.infoWindow.setOptions({ title:description});
                this.infoWindow.setOptions({ visible:true });
            } else {
                if (this.infoWindow) this.infoWindow.setOptions({ visible:false });
            }

            this.marker.setOptions({infobox: this.infoWindow});

            this.positionCallback(lat, lng);
        }

    };

    this.getBoundsAsLatLng = function(){

        var bounds = this.map.getBounds();

            var NW = bounds.getNorthwest();
            var SE = bounds.getSoutheast();

        return {NW_lat:NW.latitude, NW_lng:NW.longitude, SE_lat:SE.latitude, SE_lng:SE.longitude};
        
    };
    
    this.setEdgesAndDisplay = function(edges, count){

        this.removeOverlays();
        this.edges.length = 0;

        this.edgesCollection = new Microsoft.Maps.EntityCollection();

        for(var i = 0; i < count; i++) {
            this.edges[i] = new Microsoft.Maps.Polyline([
                new Microsoft.Maps.Location(edges[i].start.point.lat, edges[i].start.point.lng),
                new Microsoft.Maps.Location(edges[i].dest.point.lat, edges[i].dest.point.lng)
            ], {
                strokeColor:new Microsoft.Maps.Color.fromHex(this.colors[edges[i].type]), 
                strokeThickness: this.thicknesses[edges[i].type]
            });
            this.edgesCollection.push(this.edges[i]);
        };

        this.displayOverlays();

    };

    this.displayOverlays = function(){

        if (this.edgesCollection) {
            this.map.entities.push(this.edgesCollection);
        }

    };

    this.removeOverlays = function(){

        if (this.edgesCollection) {
            this.map.entities.remove(this.edgesCollection);
        }

    };
}