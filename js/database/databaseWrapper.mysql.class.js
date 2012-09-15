/* isocronMap Database Wrapper
 *
 */
var databaseWrapper = function() {

	/* ------------- Gobal paths -------------- */
	var path = "include/actions/";

	var actions = {
		getObjectsIn: 	path + "getObjectsIn.action.php",
		addVertex: 		path + "addVertex.action.php",
		addEdge:   		path + "addEdge.action.php",
	};
	/* ---------------------------------------- */

	this.getObjectsIn = function(bounds, type, callback){

		$.post(actions.getObjectsIn, {bounds: bounds, type: type}, callback);
	};

	this.addVertex = function(lat, lng){

	};

	this.addEdge = function(start_lat, start_lng, start_alt, dest_lat, dest_lng, dest_alt, type){

		var data = {
			start_lat: start_lat, 
			start_lng: start_lng, 
			start_alt: start_alt,
			dest_lat: dest_lat, 
			dest_lng: dest_lng, 
			dest_alt: dest_alt,
			type: type 
		};

		$.post(actions.addEdge, data,function(data){

			console.log(data);

		});
	};

}