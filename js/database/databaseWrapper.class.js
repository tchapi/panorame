/* isocronMap Database Wrapper
 *
 */
var databaseWrapper = function() {

	/* ------------- Gobal paths -------------- */
	var path = "include/actions/";

	var actions = {
		getObjectsIn: 		path + "getObjectsIn.action.php",
		getTypes: 			path + "getTypes.action.php",
		getClosestVertex: 	path + "getClosestVertex.action.php",
		addEdge:   			path + "addEdge.action.php",
	};
	/* ---------------------------------------- */

	this.getObjectsIn = function(bounds, type, poi, callback){

		$.post(actions.getObjectsIn, {bounds: bounds, type: type, poi: poi}, callback);
	};

	this.getTypes = function(callback){

		$.post(actions.getTypes, callback);

	};

	this.addEdge = function(start_lat, start_lng, start_alt, dest_lat, dest_lng, dest_alt, type, callback){

		var data = {
			start_lat: start_lat, 
			start_lng: start_lng, 
			start_alt: start_alt,
			dest_lat: dest_lat, 
			dest_lng: dest_lng, 
			dest_alt: dest_alt,
			type: type 
		};

		$.post(actions.addEdge, data, callback);
	};

	this.getClosestVertex = function(lat, lng, radius, callback){

		var data = {
			lat: lat, 
			lng: lng,
			radius: radius 
		};

		$.post(actions.getClosestVertex, data,callback);
	};

}