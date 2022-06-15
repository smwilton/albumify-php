<?php

    require __DIR__ . "/config/config.php";

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header('Access-Control-Allow-Origin: swilton01.webhosting6.eeecs.qub.ac.uk');
        header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: token, Content-Type');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 1728000');
        header('Content-Length: 0');
        header('Content-Type: text/plain');
        die();
    }

    header("Access-Control-Allow-Origin: swilton01.webhosting6.eeecs.qub.ac.uk");
    header("Content-Type: application/json; charset=UTF-8");
    header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
    header("Access-Control-Max-Age: 3600");
    header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
    header('Access-Control-Allow-Credentials: true');

    require_once __DIR__ . "/controllers/AlbumController.php";
    require_once __DIR__ . "/controllers/UserController.php";
    require_once __DIR__ . "/controllers/AdminController.php";
    require_once __DIR__ . "/controllers/SearchController.php";
    require_once __DIR__ . "/utils/RequestResponseUtils.php";
    

    $albumController = new AlbumController();
    $userController = new UserController();
    $adminController = new AdminController();
    $searchController = new SearchController();
    $requestResponseUtils = new RequestResponseUtils();

    $request = $_SERVER['REQUEST_URI'];
    $urlSections = $requestResponseUtils->getRequestPathSections($request);

    if(sizeof($urlSections) < 2) {
        $requestResponseUtils->return404NotFound();
    }

    if(sizeof($urlSections) == 2 || (sizeof($urlSections) == 3 && $urlSections[2] == '')) {
        header("Content-Type: text/html; charset=UTF-8");
        echo '<!doctype html><html lang="en"><head><meta charset="utf-8"/><link rel="icon" href="/favicon.ico"/><meta name="viewport" content="width=device-width,initial-scale=1"/><meta name="theme-color" content="#3993b4"/><meta name="description" content="Web site created using create-react-app"/><link rel="manifest" href="/manifest.json"/><link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Lobster&display=swap" rel="stylesheet"><link href="https://use.fontawesome.com/releases/v5.15.1/css/all.css" rel="stylesheet"/><link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet"/><link href="https://fonts.googleapis.com/css2?family=Roboto+Serif:wght@600&display=swap" rel="stylesheet"><title>Albumify</title><script defer="defer" src="/static/js/main.80b3cb99.js"></script><link href="/static/css/main.0e9e52ca.css" rel="stylesheet"></head><body><noscript>You need to enable JavaScript to run this app.</noscript><div id="root"></div></body></html>';
        die();
    }

    // Parent controller: Checking what's after /api/
    // https://www.tutorialspoint.com/design_pattern/mvc_pattern.htm
    switch ($urlSections[2]) {
        case 'album' :
            $albumController->route($request, $_SERVER['REQUEST_METHOD']);
            break;
        case 'user' :
            $userController->route($request, $_SERVER['REQUEST_METHOD']);
            break;
        case 'admin' :
            $adminController->route($request, $_SERVER['REQUEST_METHOD']);
            break;
        case 'search' :
            $searchController->route($request, $_SERVER['REQUEST_METHOD']);
            break;
        default:
            $requestResponseUtils->return404NotFound();
    }
?>