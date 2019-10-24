<?php

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/ws/example/substract/{num1}/{num2}', ['uses' => 'ExampleController@substract']);


// Users
$router->post('/ws/user/all', ['uses' => 'UserController@all']);
$router->post('/ws/user/find/{guid}', ['uses' => 'UserController@find']);
$router->post('/ws/user/enrol', ['uses' => 'UserController@enrol']);
$router->post('/ws/user/updatephoto', ['uses' => 'UserController@updatePhoto']);
$router->put('/ws/user/update', ['uses' => 'UserController@update']);
$router->post('/ws/user/delete', ['uses' => 'UserController@delete']);
$router->post('/ws/user/getauthtoken', ['uses' => 'UserController@getAuthenticationToken']);

// Camera
$router->post('/ws/camera/all', ['uses' => 'CameraController@all']);

// Match
$router->post('/ws/match/getimage', ['uses' => 'UserController@getImageMatch']);
$router->post('/ws/match/compareimages', ['uses' => 'UserController@getMatchBetweenTwoImages']);
$router->post('/ws/match/getFeatureEnrolmentFromPhoto', ['uses' => 'UserController@getFeatureEnrolmentFromPhoto']);
$router->post('/ws/match/getSubjectByFeatureByWatchList', ['uses' => 'UserController@getSubjectByFeatureByWatchList']);
$router->post('/ws/match/getImageEnrolSubject', ['uses' => 'UserController@getImageEnrolSubject']);
$router->post('/ws/match/matching', ['uses' => 'UserController@matching']);

