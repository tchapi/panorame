var userPositionHelper = function() {

    // Default : None
    this.lat = 0;
    this.lng = 0;

    this.info = "";

    this.callbackFunction = null;

};

// Fonction de callback en cas de succès
userPositionHelper.prototype.success = function(position) {

  // Un nouvel objet LatLng pour Google Maps avec les paramètres de position
  this.lat = position.coords.latitude;
  this.lng = position.coords.longitude;

  this.info = "Géolocalisation Réussie: " + this.lat + ' ' + this.lng;

  this.notify();
  this.callbackFunction.call(this.callbackContext, this.lat, this.lng, "Ma position");

};

// Fonction de callback en cas d’erreur
userPositionHelper.prototype.failure = function(error) {

  this.info = "Erreur lors de la géolocalisation : ";

  switch(error.code) {
      case error.TIMEOUT:
        this.info += "Timeout !";
        break;
      case error.PERMISSION_DENIED:
        this.info += "Vous n’avez pas donné la permission";
        break;
      case error.POSITION_UNAVAILABLE:
        this.info += "La position n’a pu être déterminée";
        break;
      case error.UNKNOWN_ERROR:
        this.info += "Erreur inconnue";
        break;
      default:
        this.info += "Ce navigateur ne supporte pas la géolocalisation";
        break;
  }

  this.notify();
  this.callbackFunction.call(this.callbackContext, null, null, null);

};

userPositionHelper.prototype.update = function(callbackFunction, callbackContext){

    this.callbackFunction = callbackFunction;
    this.callbackContext = callbackContext;

    if(navigator.geolocation) {

      navigator.geolocation.getCurrentPosition($.proxy(this.success, this), $.proxy(this.failure, this));

    } else {

      this.failure({code: 0});

    }
};

userPositionHelper.prototype.getLat = function(){ return this.lat; };
userPositionHelper.prototype.getLng = function(){ return this.lng; };
userPositionHelper.prototype.getInfo = function(){ return this.info; };

userPositionHelper.prototype.notify = function(){
  console.log(this.info);
};