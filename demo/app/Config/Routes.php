<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// This project only hosts the demo. Its routes (/kpcrudy-demo) are registered
// by the kpcrudy library itself after "php spark kpcrudy:demo install".
$routes->addRedirect('/', 'kpcrudy-demo');
