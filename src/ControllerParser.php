<?php

namespace Imanghafoori\LaravelSelfTest;

use Illuminate\Http\Request;
use ReflectionClass;

class ControllerParser
{
    protected $ctrl;

    protected $method;

    /**
     * @var array
     */
    protected $content = [];

    public function parse($ctrl, $method)
    {
        /*if ($this->isViewController()) {
            return $this->resolveViewControllerInvokeMethod();
        }*/

        return $method = (new ReflectionClass($ctrl))->getMethod($method);

        return $this->readContent($method);
    }

    /**
     * @return bool
     */
    public function isViewController()
    {
        return strpos($this->getName(), 'ViewController') !== false;
    }

    /**
     * @return array
     */
    public function resolveViewControllerInvokeMethod()
    {
        $this->ctrl->bind(new Request());

        $params = $this->ctrl->parametersWithoutNulls();

        if (array_key_exists('view', $params)) {
            return [
                'view' => $params['view'],
            ];
        }

        return $this->ctrl->parametersWithoutNulls();
    }
}
