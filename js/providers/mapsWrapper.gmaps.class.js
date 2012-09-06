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

    };


    this.setupEvents = function(genericOptions){

        // Setup autocomplete on search field
        this.autocomplete = new google.maps.places.Autocomplete(document.getElementById(genericOptions.searchInput));
        this.autocomplete.bindTo('bounds', this.map);

        google.maps.event.addListener(this.autocomplete, 'place_changed', $.proxy(function() {
          var place = this.autocomplete.getPlace();
          this.setPosition(place.geometry.location.lat(), place.geometry.location.lng(), place.formatted_address);
        }, this));

        google.maps.event.addListener(this.map, 'click', $.proxy(function(event) {
          if (this.addPin == true) this.setPosition(event.latLng.lat(), event.latLng.lng(), null);
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

}