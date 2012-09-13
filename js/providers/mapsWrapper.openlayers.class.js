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
                        "moveend": genericOptions.updateOverlayCallback,
                        "zoomend": genericOptions.updateOverlayCallback
                    }
        };

        this.map = new OpenLayers.Map(genericOptions.canvas, options);
        
        switch(this.type){
            case 'gmaps-terrain':
                var tiles = new OpenLayers.Layer.Google('', {type: google.maps.MapTypeId.TERRAIN});
                break;
            case 'gmaps-road':
                var tiles = new OpenLayers.Layer.Google();
                break;
            case 'gmaps-hybrid':
                var tiles = new OpenLayers.Layer.Google('', {type: google.maps.MapTypeId.HYBRID});
                break;
            case 'bing-road':
                var tiles = new OpenLayers.Layer.Bing({key: genericOptions.apiKeys.bing,type: "Road",wrapDateLine: true});
                break;
            case 'bing-hybrid':
                var tiles = new OpenLayers.Layer.Bing({key: genericOptions.apiKeys.bing,type: "AerialWithLabels",wrapDateLine: true});
                break;
            case 'osmaps':
            default:
                var tiles = new OpenLayers.Layer.OSM();
        }

        this.map.addLayer(tiles);

        this.map.setCenter(new OpenLayers.LonLat(genericOptions.center.lng, genericOptions.center.lat).transform(
                new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
                this.map.getProjectionObject() // to Spherical Mercator Projection
              ));

        this.markers = new OpenLayers.Layer.Markers( "Markers" );
        this.map.addLayer(this.markers);

        var size = new OpenLayers.Size(21,25);
        this.markerIcon = new OpenLayers.Icon('http://www.openlayers.org/dev/img/marker.png', size, new OpenLayers.Pixel(-(size.w/2), -size.h*1.2));

        this.positionCallback = genericOptions.positionCallback;
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

        $('#'+genericOptions.addPinButton).click($.proxy(function(event){

            var button = $('#'+genericOptions.addPinButton);

            if (button.hasClass('active')){
                this.addPin = false;
                button.html('<b class="icon-map-marker icon-white"></b> Drop Pin');
                button.removeClass('active');
            } else {
                this.addPin = true;
                button.html('<b class="icon-ok icon-white"></b> Finish');
                button.addClass('active');
            }

        },this));

    };

    this.inverseMercator = function(unproj_lat, unproj_lng){
        
        lonlat = OpenLayers.Layer.SphericalMercator.inverseMercator(unproj_lng, unproj_lat);
        return {lat:lonlat.lat, lng:lonlat.lon};
    }

    this.setPosition = function(lat, lng, description){

        if (this.map && lat != null && lng != null) {

            this.position = new OpenLayers.LonLat(lng, lat).transform(
                new OpenLayers.Projection("EPSG:4326"), // transform from WGS 1984
                this.map.getProjectionObject() // to Spherical Mercator Projection
              );

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
        
    this.removeOverlays = function(){

        

    };
}