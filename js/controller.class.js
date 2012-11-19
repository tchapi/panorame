/* Controller
 *
 */
 var Controller = function() {

    // Singleton Stuff
    if ( arguments.callee._singletonInstance )
    return arguments.callee._singletonInstance;
    arguments.callee._singletonInstance = this;

    this.init = function() {

        /* Language selector */
        this.lang = $('#lang');
        function format(L) {
            if (!L.id) return L.text; // optgroup
            return "<img class='flag' src='/img/flags/" + L.id + ".png'/>";
        }
        this.lang.select2({
            formatResult: format,
            formatSelection: format
        });

        /* Loaders */
        var opts = {
          lines: 13, // The number of lines to draw
          length: 6, // The length of each line
          width: 2, // The line thickness
          radius: 17, // The radius of the inner circle
          rotate: 0, // The rotation offset
          color: '#FFF', // #rgb or #rrggbb
          hwaccel: true,
          zIndex: 0
        };
        this.spinner = new Spinner(opts).spin(document.getElementById('mainLoader'));

    };

}

/* Instanciates
 *
 */
controller = new Controller();
controller.init();
