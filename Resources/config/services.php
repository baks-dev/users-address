<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Users\Address\Services\GeocodeAddressParser;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()      // Automatically injects dependencies in your services.
        ->autoconfigure() // Automatically registers your services as commands, event subscribers, etc.
;

    $namespace = 'BaksDev\Users\Address';


    $services->load($namespace.'\\', __DIR__.'/../../')
        ->exclude(__DIR__.'/../../{Controller,Entity,Resources,Type,Tests,*DTO.php,*Message.php}');

    $services->load($namespace.'\Controller\\', __DIR__.'/../../Controller')
        ->tag('controller.service_arguments')
        ->exclude(__DIR__.'/../../Controller/**/*Test.php')
    ;


    $services->set(GeocodeAddressParser::class)
        ->arg('$apikey', env('MAPS_YANDEX_API'));


};
