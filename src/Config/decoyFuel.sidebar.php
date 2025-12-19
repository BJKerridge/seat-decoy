<?php

return [
    'decoyFuel' => [
        'permission' => 'decoy.decoyFuelView',
        'name' => 'Fuel',
        'icon' => 'fas fa-battery-full',
        'route_segment' => 'decoyFuel',
        'route' => 'decoy::decoyFuel',
    ],
    'decoyCombat' => [
        'permission' => 'decoy.decoyCombatView',
        'name' => 'Combat',
        'icon' => 'fas fa-skull-crossbones',
        'route_segment' => 'decoyCombat',
        'route' => 'decoy::decoyCombat',
    ],
    'decoyFleets' => [
        'permission' => 'decoy.decoyFleetView',
        'name' => 'Fleets',
        'icon' => 'fa fa-space-shuttle',
        'route_segment' => 'decoyFleets',
        'route' => 'decoy::decoyFleets',
    ],
    'decoyHome' => [
        'permission' => 'decoy.decoyHomeView',
        'name' => 'Home',
        'icon' => 'fa fa-space-shuttle',
        'route_segment' => 'decoyHome',
        'route' => 'decoy::decoyHome',
    ],
    'decoyMumble' => [
        'permission' => 'decoy.decoyMumbleView',
        'name' => 'Mumble',
        'icon' => 'fa fa-headset',
        'route_segment' => 'mumble',
        'route' => 'decoy::decoyMumble',
    ],
];
