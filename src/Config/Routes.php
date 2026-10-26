<?php

/**
 * Rutes del mòdul Kpcrudy. CodeIgniter descobreix aquest fitxer sol
 * (Config\Modules::$aliases inclou 'routes').
 *
 * Només registra les rutes de la demo si s'ha instal·lat amb
 * "php spark kpcrudy:demo install" i no és producció (llevat que s'hagi
 * instal·lat amb --allow-production); si no, no afegeix res al projecte.
 * La comprovació la fa Demo::routes() mateix.
 *
 * @var CodeIgniter\Router\RouteCollection $routes
 */
Kpcrudy\Demo\Demo::routes($routes);
