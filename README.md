Panorame
=======

A one-pager that instanciates various maps API and tile providers

![Screenshot](https://github.com/tchapi/panorame/blob/master/screenshots/ui.png)

- - - -

## Usage ##

Various Layering API and Tile providers can be used to display map information.

## Fixtures ##

Panorame is now open-source — a fixtures database has been included in the repository. See the fixtures folder.

### Available Layering APIs ###

> _NB: Layering APIs are also called "frameworks" in the following text._

The following APIs are actually available in panorame :

* Google Maps (version 7)
* Bing Maps
* Open Layers
* MapQuest
* Nokia Maps (here.com)

### Available Tile providers ###

The following tile providers are available : 

* Google Tiles
 * Road
 * Terrain
 * Hybrid
* Microsoft Nav Tiles
 * Road
 * Hybrid
* Open Street Maps
 * Open Street Maps road tiles
* MapQuest
 * MapQuest road tiles
* Nokia (here.com)
 * Road
 * Terrain
 * Hybrid

### Available Database engine providers ###

Panorame data services are built upon two different DBMS : Mongo and MySQL, accessed via PHP::Mongo and mysqli.

### URL Construction ###

The application can be accessed via :

        http://panorame.tchap.me/?framework=[API]&provider=[PROVIDER]&engine=[DATABASE]

The parameters can be :

+ `framework` - _optional_ : The Layering API amongst :
 + gmaps
 + bing
 + openlayers
 + mapquest
 + nokia
+ `provider` - _optional_ : The alternative tile provider amongst :
 + gmail-road
 + gmail-hybrid
 + gmail-terrain
 + bing-road
 + bing-hybrid
 + nokia-road
 + nokia-hybrid
 + nokia-terrain
+ `engine` - _optional_ : The database engine provider amongst :
 + mysql
 + mongo

If no framework is provided, __gmaps__ will be used by default.
If no provider is provided, the default provider for each framework will be used by default.
If no engine is provided, __MySQL__ will be used by default.

> _NB : Not all tile providers are compatible with Layering APIs._

## Administration / Edition ##

The graph can be edited via the editing mode :

        http://panorame.tchap.me/?edit=1

This mode forces __gmaps__ and __gmap-road__ as API and tile provider.
In editing mode, all the edges are displayed regardless of the POI, the mean of transportation or the distance from the POI. No dijkstra algorithm is applied to the edges in the bounding box.

#### Adding edges ####

Edges can be added to the graph easily. First, choose the correct type for the edges you want to create in the `Type`select box.

Then, click on the "Add Edges" button in the admin panel. Edges are added in two steps : 

1. First, click on a point in the map to set the starting point. Existing vertices can be clicked
2. Second, click on a point to set the destination point. Existing vertices can be clicked

> _NB : If one of these vertices is in a radius of 5 m of an existing vertex, the latter vertex will be used (auto-merging)_.

When the edge is created, you can add another edge directly by re-clicking to setup a new start point. Clicking the "Finish" button in the admin panel will terminate your edge-adding session.

> _NB : When in continuous mode, the ending point of an edge is automatically deemed starting point of the to-be-created edge.

__Edge auto-reverse functionnality__

By default, only one direction is created (from start to destination). In the admin panel, this behaviour can be amended by choosing a different one.

* `None` : No reverse way will be created - this is the default behaviour
* `Same` : The same type of edge will be created
* `Cycle`: An edge accessible to cycles and pedestrians will be created for the reverse way
* `Walk` : An edge accessible to pedestrians only will be created for the reverse way

#### Editing edges ####

The edges are provided with three handles :
 - Two handles to change the start and end point
 - One handle to cut the edge in two and create two consequent edges

These handles appear when the mouse hovers an edge, and can be grabbed with a left-click.

> _NB : If you drag a vertex in a radius of 5 m of an existing vertex, the latter vertex will be used (auto-merging)_. This applies to edge cutting as well.

Edges edition support undoing up to one-level, with an appropriate icon that will appear near to the dragged vertex just after you have dragged it (under 1 sec).

#### Deleting edges ####

Edges can be deleted with a right-click. There is no undo for this action.

#### Consolidating ####

_Consolidation is now an automatic task._

What consolidating does :
* find orphan vertices in the graph and soft-delete them
* auto-merge vertices
* recalculate edges distances and grades if a bias is detected after a change in a vertex affecting one or more edge

> NB : Consolidating is a deterministic operation in the database that is automatically done after each editing action 


#### Keyboard shortcuts ####

Keyboard shortcuts are available to easily edit the edges :

These shortcuts are available in `edit` and `normal` modes :
* Pin my location : `w`
* Toggle drop pin mode : `e`

These shortcuts are available in `edit` mode only :
* Toggle add edge mode : `a`
* Toggle continuous mode : `z`
* Toggle overlay view : `spacebar`
* Stop add edge & drop pin : `escape`
* Autoreverse type : `q` for None, `s` for Same, `d` for Cycles and `f` for Pedestrian only


- - - -
