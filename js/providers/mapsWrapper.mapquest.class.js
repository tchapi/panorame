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
        
    this.getUrl = function(genericOptions){

        this.ownCallback = false;
        this.delay = 0;
        return "http://www.mapquestapi.com/sdk/js/v7.0.s/mqa.toolkit.js?key=" + genericOptions.apiKeys.mapquest;

    };

    this.createMap = function(genericOptions){

        var options = {
          elt: document.getElementById(genericOptions.canvas),     /*ID of element on the page where you want the map added*/ 
          zoom:14,                         /*initial zoom level of the map*/ 
          latLng:genericOptions.center,  /*center of map in latitude/longitude */ 
          mtype:'map'                      /*map type (map)*/ 
        };

        /*Construct an instance of MQA.TileMap with the options object*/ 
        this.map = new MQA.TileMap(options);

        MQA.withModule('viewoptions','largezoom','geocoder', $.proxy(function() {

          this.map.addControl(
            new MQA.ViewOptions()
          );
          this.map.addControl(
            new MQA.LargeZoom(),
            new MQA.MapCornerPlacement(MQA.MapCorner.TOP_LEFT, new MQA.Size(5,5))
          );

        }, this));

        MQA.EventManager.addListener(this.map, 'click', $.proxy(function(e){
          if (this.addPin == true) this.setPosition(e.ll.lat, e.ll.lng, null);
        }, this));

        this.positionCallback = genericOptions.positionCallback;

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

            // Creates the marker
            this.map.removeAllShapes();
            this.marker = new MQA.Poi({lat:lat, lng:lng});
            this.map.addShape(this.marker);

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

}