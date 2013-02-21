/* Panorame POI Service
 *
 */
var poiService = function() {

    // Singleton Stuff
    if ( arguments.callee._singletonInstance )
    return arguments.callee._singletonInstance;
    arguments.callee._singletonInstance = this;

    /* ------------- Gobal paths -------------- */
    var path = "/action/";

    var actions = {
      getPOIProviders: path + "getPOIProviders",
      getResults:      path + "getPOIResultsIn"
    };
    /* ---------------------------------------- */

    this.getPOIProviders = function(callback){

      $.post(actions.getPOIProviders, callback);

    };

    this.getPOIResultsIn = function(provider, bounds, term, callback){

      $.post(actions.getPOIResultsIn, {provider: provider, bounds: bounds, term: term}, callback);

    };

}