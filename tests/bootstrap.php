<?php

/*
|--------------------------------------------------------------------------
| The bootstrap of the test suite
|--------------------------------------------------------------------------
|
| The suite needs the autoloader of the project and a key of the
| application. The key is generated for every run, because a key in the
| repository is a key that everybody has, and a scanner reads it as a
| committed secret.
|
*/

require __DIR__.'/../vendor/autoload.php';

if (! getenv('APP_KEY')) {
    $key = 'base64:'.base64_encode(random_bytes(32));

    putenv('APP_KEY='.$key);
    $_ENV['APP_KEY'] = $key;
    $_SERVER['APP_KEY'] = $key;
}
