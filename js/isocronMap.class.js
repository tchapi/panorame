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

        // Options
        this.options={
            canvas: this.canvas,      
            center:{lat:48.8566667, lng:2.3509871}, // PARIS
            searchInput: this.searchInput,
            addPinButton: this.addPinButton,
            apiKeys: this.apiKeys,
            positionCallback: $.proxy(this.updateCurrentPosition, this),
            updateOverlayCallback: $.proxy(this.getData, this),
            colors: ['#FF0000','#0000FF','#000000'], // 0, 1, 2
            thicknesses: [4,4,4], // 0, 1, 2
            mapReadyCallback: $.proxy(this.mapIsReady, this),
            addEdgeCallback: $.proxy(this.addEdge, this)
        };

        mapsWrapper.createMap(this.options);

    };

    this.mapIsReady = function(){

        mapsWrapper.setupEvents(this.options);

        // We setup the events
        $('#self').click($.proxy(function(){
            this.setToUserPositionIfAvailable();
        }, this));

        // Finally, we center the map at the user's position
        this.setToUserPositionIfAvailable();

    };

    this.setupVisual = function(){

        var addPinButton = $('#addPin');
        var addEdgeButton = $('#addEdge button');

        $('.radiusType').tooltip({placement: 'bottom'});

        addPinButton.popover({placement: 'bottom'});
        addPinButton.click($.proxy(function(event){

            if (addPinButton.hasClass('active')){
                mapsWrapper.setAddPin(false);
                addPinButton.html('<b class="icon-map-marker icon-white"></b> Drop Pin');
                addPinButton.removeClass('active');
            } else {
                mapsWrapper.setAddPin(true);
                addPinButton.html('<b class="icon-ok icon-white"></b> Finish');
                addPinButton.addClass('active');
            }

        },this));

        addEdgeButton.popover({placement: 'bottom'});
        addEdgeButton.click($.proxy(function(event){

            if (addEdgeButton.hasClass('active')){
                mapsWrapper.setAddEdge(false);
                addEdgeButton.html('<b class="icon-plus-sign icon-white"></b> Add edges');
                addEdgeButton.removeClass('active');
            } else {
                mapsWrapper.setAddEdge(true);
                addEdgeButton.html('<b class="icon-ok icon-white"></b> Finish');
                addEdgeButton.addClass('active');
            }

        },this));

    };

    this.setToUserPositionIfAvailable = function(){

        this.userPosition.update(mapsWrapper.setPosition, mapsWrapper);

    };

    this.updateCurrentPosition = function(lat, lng){

        this.position = {lat: lat, lng: lng};
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

        /* Vertices
        databaseWrapper.getObjectsIn(this.getBounds(), 'vertices', this.position, function(data){
            $('#objects span').html(data.count);
            mapsWrapper.setVertices(data.vertices, data.count);
        });
        */

        /* Edges */
        databaseWrapper.getObjectsIn(this.getBounds(), 'edges', this.position, function(data){
            $('#objects span').html(data.count);
            mapsWrapper.setEdgesAndDisplay(data.edges, data.count);
        });

        /* Vertices with Children
        databaseWrapper.getObjectsIn(this.getBounds(), 'tree', this.position, function(data){
            $('#objects span').html(data.count);
            
        });
        */
    };

    this.addEdge = function(start_lat, start_lng, start_alt, dest_lat, dest_lng, dest_alt, type){

        var result = databaseWrapper.addEdge(start_lat, start_lng, start_alt, dest_lat, dest_lng, dest_alt, $('#addEdge_type').val(), $.proxy(this.getData,this));

    };

    this.displayOverlay = function(){

        mapsWrapper.displayOverlays();
    };

    this.removeOverlay = function(){

        mapsWrapper.removeOverlays();

    };

    this.dijkstra = function(){

    };

}

/* Instanciates
 *
 */
isocronMap = new isocronMap();