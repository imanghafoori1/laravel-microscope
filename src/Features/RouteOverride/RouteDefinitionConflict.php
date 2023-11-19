<?php

namespace Imanghafoori\LaravelMicroscope\Features\RouteOverride;

class RouteDefinitionConflict
{
    public $data;

    public function __construct($poorRoute, $bullyRoute, $info)
    {
        $this->data = [
            'poorRoute' => $poorRoute,
            'bullyRoute' => $bullyRoute,
            'info' => $info,
        ];
    }
}
