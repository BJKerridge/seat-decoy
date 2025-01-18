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
];
