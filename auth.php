<?php

  $password = "panorame";

 if ( (!isset($_POST['name']) || !isset($_POST['password']) || $_POST['password'] != $password || $_POST['name'] == "") && (!isset($_COOKIE['panorame_auth']) || $_COOKIE['panorame_auth'] != md5($password)) ){
?>
<!DOCTYPE html>
<html>
  <head>
    <title>Panorame</title>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no">
    <meta charset="utf-8">
    <link href="bootstrap/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body>
    <div class="container">
      <div class="row">
        <div class="span4 offset4 well">
          <legend>Please Sign In</legend>
<?php if (isset($_POST['password'])): ?>
            <div class="alert alert-error">
                <a class="close" data-dismiss="alert" href="#">×</a>Incorrect Username or Password!
            </div>
<?php endif ?>
          <form method="POST" action="" accept-charset="UTF-8">
          <input type="text" id="name" class="span4" name="name" placeholder="Username">
          <input type="password" id="password" class="span4" name="password" placeholder="Password">
          <button type="submit" name="submit" class="btn btn-info btn-block">Sign in</button>
          </form>    
        </div>
      </div>
    </div>
  </body>
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.0/jquery.min.js" type="text/javascript"></script>
  <script src="bootstrap/js/bootstrap.min.js"></script>
</html>
<?php 

  exit(1);
  } 

  setcookie( "panorame_auth", md5($password), strtotime( '+30 days' ) ); 

?>