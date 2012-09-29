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
            updateOverlayCallback: $.proxy(this.updateOverlay, this),
            colorsForType: ['#FF0000','#0000FF','#000000'], // 0, 1, 2
            thicknessesForType: [4,4,4], // 0, 1, 2
            standardPinImage: '/img/pins/Blue/8.png',
            closestPointPinImage: '/img/pins/Red/8.png',
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

        var addPinButton  = $('#addPin');
        var addEdgeButton = $('#addEdge button');
        this.limitSlider  = $('#limitSlider');
        this.limitValue   = $('#limitValue');

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

        this.limitSlider.noUiSlider('init', {
            knobs: 1,
            connect: "lower",
            scale: [0, 1000],
            start: 300,
            change:$.proxy(function(){
                this.limitValue.html(this.limitSlider.noUiSlider('value')[1] + 'm');
//            }, this),
//            end: $.proxy(function(){
                this.updateOverlay();
            }, this)
        });

        this.limitValue.html(this.limitSlider.noUiSlider('value')[1] + 'm');
    };

    this.setToUserPositionIfAvailable = function(){

        this.userPosition.update(mapsWrapper.setPosition, mapsWrapper);

    };

    this.updateCurrentPosition = function(lat, lng){

        this.position = {lat: lat, lng: lng};
        $('#position span').html(Math.round(lat*this.digits)/this.digits + ' — ' + Math.round(lng*this.digits)/this.digits);

    };

    this.updateOverlay = function() {

        this.getData(this.limitSlider.noUiSlider('value')[1]);

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

    this.getData = function(limit){

        /* Vertices
        databaseWrapper.getObjectsIn(this.getBounds(), 'vertices', this.position, function(data){
            $('#objects span').html(data.count);
            mapsWrapper.setVertices(data.vertices, data.count);
        });
        */

        /* Edges
        databaseWrapper.getObjectsIn(this.getBounds(), 'edges', this.position, function(data){
            $('#objects span').html(data.count);
            if (data.count !=0 && data.poi != null) mapsWrapper.setDataOverlay(data.edges, data.closest.point, null);
        });
        */

        /* Vertices with Children */
        databaseWrapper.getObjectsIn(this.getBounds(), 'tree', this.position, $.proxy(function(data){
            
            $('#objects span').html(data.count);
            
            if (data.count != 0 && data.closest != null) {
                var edges = this.dijkstra(data.tree, this.position, data.closest)
                mapsWrapper.setDataOverlay(edges, data.closest.point, limit);
            }

        }, this));
        
    };

    this.addEdge = function(start_lat, start_lng, start_alt, dest_lat, dest_lng, dest_alt, type){

        var result = databaseWrapper.addEdge(start_lat, start_lng, start_alt, dest_lat, dest_lng, dest_alt, $('#addEdge_type').val(), $.proxy(this.getData,this));

    };

    this.findMinimumCost = function(array){

        var min = null;
        var min_index = null;

        for(i in array){
            if (array[i] != null && array[i].out !== true && (array[i].cost < min || min == null) ) {
                min = array[i].cost;
                min_index = i;
            }
        }

        return min_index;
    };

    this.dijkstra = function(tree, poi, closestPoint){

        // init
        // closest is in the tree <-- pas forcément vrai si le viewport ne contient pas notre poi !!!
        
        var nodeId = closestPoint.id;
        var node = tree[nodeId];

        
        node.cost = closestPoint.distance;
        node.path = [];

        // Array of computed edges with 
        var edges = [];
        // The first edge is POI -> root node
        edges.push({
            //id: 0,
            distance: closestPoint.distance,
            // grade: closestPoint.grade,
            type: -1,
            start:{
                //id: 0,
                point:{
                    lat: poi.lat,
                    lng: poi.lng,
                 //   alt: node.point.alt
                },
                cost: 0,
            },
            dest:{
                id: nodeId,
                point:{
                    lat: closestPoint.point.lat,
                    lng: closestPoint.point.lng,
                    alt: closestPoint.point.alt
                },
                cost: closestPoint.distance
            }
        });

        var idInTree = null;
        var child = null;

        // recur

        while (node != null) {
            
            // node has no cost : it will never be reached 
            if (node.cost == null) {

                console.log('- found unreachable node : ' + nodeId);

            } else {

                console.log('- checking reached node : ' + nodeId);

                // node has a cost : it has been reached, check his children
                for(childIndex in node.children) {

                    child = node.children[childIndex];
                    idInTree = child.id;

                    if (tree[idInTree] != null && typeof(tree[idInTree]) !== 'undefined'){

                        childInTree = tree[idInTree];

                        // We replace the cost if we reach him at a lesser cost
                        if ( childInTree.out !== true && 
                            (typeof(childInTree.cost) === 'undefined' || childInTree.cost == null || childInTree.cost > (node.cost + child.distance + 10*child.grade) )
                        ){

                            childInTree.cost = node.cost + child.distance + 10*child.grade;
                            childInTree.path = node.path.slice(); // to make a copy
                            childInTree.path.push(idInTree);

                        }

                        // now we construct the edge we're going to need for display between node and childInTree
                        console.log('  + found edge : ' + nodeId + ' => ' + idInTree + '(cost=' + childInTree.cost + ')')
                        
                        edges.push({
                            id: child.path_id,
                            distance: child.distance,
                            grade: child.grade,
                            type: child.type,
                            start:{
                                id: nodeId,
                                point:{
                                    lat: node.point.lat,
                                    lng: node.point.lng,
                                    alt: node.point.alt
                                },
                                cost: node.cost,
                            },
                            dest:{
                                id: idInTree,
                                point:{
                                    lat: childInTree.point.lat,
                                    lng: childInTree.point.lng,
                                    alt: childInTree.point.alt
                                },
                                cost: childInTree.cost
                            }
                        });

                    }

                }
            }

            tree[nodeId].out = true; // node is out

            //finding the min from the rest :
            nodeId = this.findMinimumCost(tree);
            if (nodeId != null){
                node = tree[nodeId];
                if (node.path == null) node.path = [];
            } else {
                node = null; //exit
            }

        }

        console.log('-------------- end --------------');
        return edges;
    };

}

/* Instanciates
 *
 */
isocronMap = new isocronMap();