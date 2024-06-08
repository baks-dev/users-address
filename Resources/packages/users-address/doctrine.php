<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use BaksDev\Users\Address\BaksDevUsersAddressBundle;
use BaksDev\Users\Address\Type\AddressField\AddressField;
use BaksDev\Users\Address\Type\AddressField\AddressFieldType;
use BaksDev\Users\Address\Type\Geocode\GeocodeAddressUid;
use BaksDev\Users\Address\Type\Geocode\GeocodeAddressUidType;
use BaksDev\Users\Address\Type\Id\UsersAddressUid;
use BaksDev\Users\Address\Type\Id\UsersAddressUidType;
use Symfony\Config\DoctrineConfig;

return static function(DoctrineConfig $doctrine) {
	
	$doctrine->dbal()->type(GeocodeAddressUid::TYPE)->class(GeocodeAddressUidType::class);
	$doctrine->dbal()->type(UsersAddressUid::TYPE)->class(UsersAddressUidType::class);
    $doctrine->dbal()->type(AddressField::TYPE)->class(AddressFieldType::class);

    $emDefault = $doctrine->orm()->entityManager('default')->autoMapping(true);


    $emDefault->mapping('users-address')
        ->type('attribute')
        ->dir(BaksDevUsersAddressBundle::PATH.'Entity')
        ->isBundle(false)
        ->prefix('BaksDev\Users\Address\Entity')
        ->alias('users-address')
    ;
};