/* isocronMap Database Wrapper
 *
 */
var databaseWrapper = function() {

  /* ------------- Gobal paths -------------- */
  var path = "/action/";

  var actions = {
    getObjectsIn:      path + "getObjectsIn",
    getClosestVertex:  path + "getClosestVertex",
    getMeansAndSpeeds: path + "getMeansAndSpeeds",
    getPOIs:           path + "getPOIs",
    /* ADMIN */
    getTypes:          path + "edit/getTypes",
    addEdge:           path + "edit/addEdge",
    deleteEdge:        path + "edit/deleteEdge",
    updateVertex:      path + "edit/updateVertexCouple",
    cutEdge:           path + "edit/cutEdge",
    consolidate:       path + "edit/consolidate"
  };
  /* ---------------------------------------- */

  this.getObjectsIn = function(bounds, type, restrictToType, poi, callback){

    $.post(actions.getObjectsIn, {bounds: bounds, type: type, restrictToType: restrictToType, poi: poi}, callback);

  };

  this.getMeansAndSpeeds = function(callback){

    $.post(actions.getMeansAndSpeeds, callback);

  };

  this.getPOIs = function(callback){

    $.post(actions.getPOIs, callback);

  };

  this.getClosestVertex = function(lat, lng, radius, callback){

    var data = {
      lat: lat, 
      lng: lng,
      radius: radius 
    };

    $.post(actions.getClosestVertex, data, callback);

  };


  // ADMIN ------------------------------------------

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

  this.deleteEdge = function(edge_id, callback){

    $.post(actions.deleteEdge, {edge_id: edge_id}, callback);

  };

  this.updateVertexCouple = function(start_id, start_lat, start_lng, start_alt, dest_id, dest_lat, dest_lng, dest_alt, edge_id, callback){

    var data = {
      start_id: start_id,
      start_lat: start_lat, 
      start_lng: start_lng, 
      start_alt: start_alt,
      dest_id: dest_id,
      dest_lat: dest_lat, 
      dest_lng: dest_lng, 
      dest_alt: dest_alt,
      edge_id: edge_id
    };

    $.post(actions.updateVertex, data, callback)

  };

  this.cutEdge = function(start_id, dest_id, new_lat, new_lng, new_alt, edge_id, callback){

    var data = {
      start_id: start_id,
      dest_id: dest_id,
      new_lat: new_lat, 
      new_lng: new_lng, 
      new_alt: new_alt,
      edge_id: edge_id
    };

    $.post(actions.cutEdge, data, callback)

  };

  this.consolidate = function(callback){

    $.post(actions.consolidate, null, callback);

  };
}
