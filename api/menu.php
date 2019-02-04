<?php

spl_autoload_register(function ($class) {
    include '../phpClasses/' . $class . '.php';
});

$api = new MenuApiManager();
$api->requestController($_GET, $_POST);
