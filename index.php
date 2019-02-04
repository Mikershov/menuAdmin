<?php
spl_autoload_register(function ($class) {
    include 'phpClasses/' . $class . '.php';
});
?>

<!doctype html>
<html class="no-js" lang="en" dir="ltr">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Menu Public</title>
        <link rel="stylesheet" href="css/foundation.css">
        <link rel="stylesheet" href="css/app.css">
    </head>
    <body>
        <div id="mainMenuBlock">
          <?php
            include 'public_html/'.Settings::getWorkMode().'Menu.html';
          ?>
        </div>

        <script src="js/vendor/jquery.js"></script>
        <script src="js/vendor/what-input.js"></script>
        <script src="js/vendor/foundation.js"></script>
        <script src="js/app.js"></script>
        <script src="js/menu.js"></script>
    </body>
</html>
