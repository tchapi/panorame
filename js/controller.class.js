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
        this.mainLoader = document.getElementById('mainLoader');
        this.spinner = new Spinner(opts).spin(this.mainLoader);

        /* Menu for ajax loading */
        this.menus = $('#pages li a');
        this.menus.click(function(e){

          $('#content').empty();
          var urlPath = e.target.href;

          $.post(urlPath, {ajax: 1}, function(data){

            $('#content').html(data.html);
            document.title = data.title
            window.history.pushState({"html": data.html, "title": data.title},"", urlPath);

          });

          e.preventDefault();
          return false;

        });

        window.onpopstate = function(e){
          if(e.state){
            $('#content').html(e.state.html);
            document.title = e.state.title;
          }
        };

    };

}

/* Instanciates
 *
 */
controller = new Controller();
controller.init();
