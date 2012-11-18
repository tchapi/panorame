var states = {
  unknown: "unknown",
  waiting: "waiting",
  success: "success",
  error: {
    timeout: "timeout",
    permission_denied: "permission_denied",
    position_unavailable: "position_unavailable",
    unknow_error: "unknow_error",
    unsupported: "unsupported"
  }
};

var userPositionHelper = function() {

    // Default : None
    this.lat = 0;
    this.lng = 0;

    this.callbackFunction = null;

    this.setState(states.unknown);

};

// Success callback function
userPositionHelper.prototype.success = function(position) {

  // We create a new object with lat and lng values
  this.lat = position.coords.latitude;
  this.lng = position.coords.longitude;

  this.setState(states.success);
  this.callbackFunction.call(this.callbackContext, this.lat, this.lng, "Ma position");

};

// Failure callback function
userPositionHelper.prototype.failure = function(error) {

  switch(error.code) {
    case error.TIMEOUT:
      this.setState(states.error.timeout);
      break;
    case error.PERMISSION_DENIED:
      this.setState(states.error.permission_denied);
      break;
    case error.POSITION_UNAVAILABLE:
      this.setState(states.error.position_unavailable);
      break;
    case error.UNKNOWN_ERROR:
      this.setState(states.error.unknow_error);
      break;
    default:
      this.setState(states.error.unsupported);
      break;
  }

  this.callbackFunction.call(this.callbackContext, null, null, null);

};

userPositionHelper.prototype.update = function(callbackFunction, callbackContext, notifyFunction){

    this.callbackFunction = callbackFunction;
    this.notifyFunction = notifyFunction;
    this.callbackContext = callbackContext;

    // For now on, we're waiting
    this.setState(states.waiting);

    if(navigator.geolocation) {

      navigator.geolocation.getCurrentPosition($.proxy(this.success, this), $.proxy(this.failure, this));

    } else {

      this.failure({code: 0});

    }
};

userPositionHelper.prototype.setState = function(state){ 
  
  this.state = state;
  var timeout = 0 + (state=="waiting"?500:0); // So the waiting popup doesn't show straight away

  if (this.notifyFunction !== null && this.notifyFunction != undefined) window.setTimeout(this.notifyFunction, timeout); 

};

userPositionHelper.prototype.getState = function(){ return this.state; };
userPositionHelper.prototype.getLat = function(){ return this.lat; };
userPositionHelper.prototype.getLng = function(){ return this.lng; };
