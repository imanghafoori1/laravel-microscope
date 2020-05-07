<?php

namespace Imanghafoori\LaravelMicroscope\ErrorTypes;

class RouteDefinitionConflict
{
    public $data;

    public function __construct($poorRoute, $bullyRoute)
    {
        $this->data = [
            'poorRoute' => $poorRoute,
            'bullyRoute' => $bullyRoute
        ];
    }
}
