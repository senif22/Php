<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// Authentication Routes
$routes->get('/', 'Auth::login');
$routes->get('/login', 'Auth::login');
$routes->post('/authenticate', 'Auth::authenticate');
$routes->get('/logout', 'Auth::logout');

// Protected Routes (require login)
$routes->group('', ['filter' => 'auth'], function($routes) {
    // Dashboard
    $routes->get('/dashboard', 'Dashboard::index');
    $routes->get('/dashboard/refresh', 'Dashboard::refresh');

    // Customers
    $routes->get('/customers', 'Customers::index');
    $routes->get('/customers/create', 'Customers::create');
    $routes->post('/customers/store', 'Customers::store');
    $routes->get('/customers/view/(:num)', 'Customers::view/$1');
    $routes->get('/customers/edit/(:num)', 'Customers::edit/$1');
    $routes->post('/customers/update/(:num)', 'Customers::update/$1');
    $routes->get('/customers/delete/(:num)', 'Customers::delete/$1', ['filter' => 'role:admin']);
    $routes->get('/customers/export', 'Customers::export');
});

// API Routes
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    $routes->post('login', 'AuthController::login');

    $routes->group('', ['filter' => 'jwt'], function ($routes) {
        $routes->get('me', 'AuthController::me');
        $routes->get('customers', 'CustomerController::index');
        $routes->get('customers/(:num)', 'CustomerController::show/$1');
        $routes->post('customers', 'CustomerController::create');
        $routes->put('customers/(:num)', 'CustomerController::update/$1');
        $routes->patch('customers/(:num)', 'CustomerController::update/$1');
        $routes->delete('customers/(:num)', 'CustomerController::delete/$1');
    });
});
