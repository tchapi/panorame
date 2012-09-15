/* isocronMap
 *
 */
var isocronMap = function() {

    // Singleton Stuff
    if ( arguments.callee._singletonInstance )
    return arguments.callee._singletonInstance;
    arguments.callee._singletonInstance = this;

    // API keys
    this.apiKeys = {
        gmaps: "AIzaSyAcfMo5fGvQ11nZ7h68vaxSVr7ATu_32ks",
        bing: "AqOiHyGALNGGhB_HHKor3uUOZ7epiJ1k31-hXDcnaUnqOgoDl2GA_mYEAiNGuYWG",
        mapquest: "Fmjtd%7Cluua250z2g%2C2x%3Do5-962al4",
        openlayers: null,
    };

    // Helpers
    this.userPosition = new userPositionHelper();

    // Precision
    this.digits = 10000;

    this.insertScript = function(canvas, searchInput, addPinButton){

        // We make it all asynchronous
        this.canvas = canvas;
        this.searchInput = searchInput;
        this.addPinButton = addPinButton;

        var oScript    = document.createElement('script');
        oScript.type   = 'text/javascript';
        oScript.src    = mapsWrapper.getUrl({apiKeys: this.apiKeys, instance: this});
        if (!mapsWrapper.ownCallback)
            oScript.onload = $.proxy(function(){setTimeout("isocronMap.init()", mapsWrapper.delay);}, this);

        document.body.appendChild(oScript);

    };

    this.init = function(){

        this.setupVisual();

        var options={
            canvas: this.canvas,      
            center:{lat:48.8566667, lng:2.3509871}, // PARIS
            searchInput: this.searchInput,
            addPinButton: this.addPinButton,
            apiKeys: this.apiKeys,
            positionCallback: $.proxy(this.updateCurrentPosition, this),
            updateOverlayCallback: $.proxy(this.getData, this)
        };

        mapsWrapper.createMap(options);
        mapsWrapper.setupEvents(options);

        // We setup the events
        $('#self').click($.proxy(function(){
            this.setToUserPositionIfAvailable();
        }, this));

        // Finally, we center the map at the user's position
        this.setToUserPositionIfAvailable();
        this.getData();

    };

    this.setupVisual = function(){

        $('.radiusType').tooltip({placement: 'bottom'});
        $('#addPin').popover({placement: 'bottom'});

    };

    this.setToUserPositionIfAvailable = function(){

        this.userPosition.update(mapsWrapper.setPosition, mapsWrapper);

    };

    this.updateCurrentPosition = function(lat, lng){

        $('#position span').html(Math.round(lat*this.digits)/this.digits + ' — ' + Math.round(lng*this.digits)/this.digits);

    };

    this.getBounds = function(){

        var bounds = mapsWrapper.getBoundsAsLatLng();

        $('#position b').popover('destroy')
        $('#position b').popover({placement: 'top', trigger: 'click', title:"Actual bounds",
            content: 'NW : (' + Math.round(bounds.NW_lat*this.digits)/this.digits + ', ' + Math.round(bounds.NW_lng*this.digits)/this.digits + ')<br/>' +
                     'SE : (' + Math.round(bounds.SE_lat*this.digits)/this.digits + ', ' + Math.round(bounds.SE_lng*this.digits)/this.digits + ')<br/>'
        });

        return bounds;

    };

    this.getData = function(){

        /* Edges */
        databaseWrapper.getObjectsIn(this.getBounds(), 'edges', function(data){
            $('#objects span').html(data.count);
            mapsWrapper.setEdges(data.edges);
        });

        /* Plus tard : ...
        databaseWrapper.getOverlay(this.getBounds(),function(data){
            console.log(data);
        });
        */

    };

    this.displayOverlay = function(){

        mapsWrapper.displayOverlays();
    };

    this.removeOverlay = function(){

        mapsWrapper.removeOverlays();

    };

}

/* Instanciates
 *
 */
isocronMap = new isocronMap();