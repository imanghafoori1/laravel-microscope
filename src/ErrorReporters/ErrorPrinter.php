<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

class ErrorPrinter
{
    public $counts = [
        'view' => [],
        'route' => [],
        'total' => 0,
        'bladeImport' => [],
        'badRelation' => [],
        'wrongImport' => [],
        'wrongUsedClassError' => [],
        'wrongMethodError' => [],
        'badNamespace' => [],
        'ddFound' => [],
        'CompactCall' => [],
        'routeDefinitionConflict' => [],
        'routelessCtrl' => [],
        'queryInBlade' => [],
        'envFound' => [],
        'ns_replacement' => [],
    ];

    public $printer;

    public $logErrors = true;

    public $pended = [];

    public function view($absPath, $lineContent, $lineNumber, $fileName)
    {
        array_push($this->counts['view'], (new PendingError('view'))
            ->header(trim($lineContent))
            ->errorData($this->yellow($fileName.'.blade.php').' does not exist')
            ->link($absPath, $lineNumber));
    }

    public function route($path, $errorIt, $errorTxt, $absPath = null, $lineNumber = 0)
    {
        array_push($this->counts['route'], (new PendingError('route'))
            ->header($errorIt)
            ->errorData($errorTxt.$this->yellow($path))
            ->link($absPath, $lineNumber));
    }

    public function bladeImport($class, $absPath, $lineNumber)
    {
        $this->pendError($absPath, $lineNumber, $class, 'bladeImport', 'Class does not exist:');
    }

    public function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    public function badRelation($absPath, $lineNumber, $relatedModel)
    {
        $header = 'Wrong model is passed in relation:';

        $this->pendError($absPath, $lineNumber, $relatedModel, 'badRelation', $header);
    }

    public function pendError($path, $lineNumber, $absent, $key, $header)
    {
        array_push($this->counts[$key], (new PendingError($key))
            ->header($header)
            ->errorData($this->yellow($absent).'   <==== does not exist')
            ->link($path, $lineNumber));
    }

    public function routelessAction($absPath, $lineNumber, $action)
    {
        $key = 'routelessCtrl';
        array_push($this->counts[$key], (new PendingError($key))
            ->header('No route is defined for controller action:')
            ->errorData($this->yellow($action))
            ->link($absPath, $lineNumber));
    }

    public function simplePendError($path, $lineNumber, $absent, $key, $header)
    {
        array_push($this->counts[$key], (new PendingError($key))
            ->header($header)
            ->errorData($this->yellow($absent))
            ->link($path, $lineNumber));
    }

    public function wrongImport($absPath, $class, $lineNumber)
    {
        $this->pendError($absPath, $lineNumber, "use $class;", 'wrongImport', 'Wrong import:');
    }

    public function compactError($path, $lineNumber, $absent, $key, $header)
    {
        array_push($this->counts[$key], (new PendingError($key))
            ->header($header)
            ->errorData($this->yellow(implode(', ',array_keys($absent))). ' does not exist')
            ->link($path, $lineNumber));
    }

    public function routeDefinitionConflict($route1 , $route2, $info)
    {
        $key = 'routeDefinitionConflict';
        $routeName = $route1->getName();
        if ($routeName) {
            $routeName = $this->yellow($routeName);
            $msg = 'Route name: '.$routeName;
        } else {
            $routeUri = $route1->uri();
            $routeUri = $this->yellow($routeUri);
            $msg = 'Route uri: '.$routeUri;
        }

        $msg .= "\n".' at '. $info[0]['file'].':'.$info[0]['line'];
        $msg .= "\n".' is overridden by ';

        $routeName = $route2->getName();
        if ($routeName) {
            $routeName = $this->yellow($routeName);
            $msg .= 'route name: '.$routeName;
        } else {
            $msg .= 'an other route with same uri.';
        }

        $msg .= "\n".' at '. $info[1]['file'].':'.$info[1]['line']. "\n";

        $methods = implode(',', $route1->methods());

        $this->counts[$key][$methods] = (new PendingError($key))
            ->header('Route with uri: '.$this->yellow($methods.': /'.$route1->uri()).' is overridden.')
            ->errorData($msg);
    }

    public function wrongUsedClassError($absPath, $class, $lineNumber)
    {
        $this->pendError($absPath, $lineNumber, $class, 'wrongUsedClassError', 'Class does not exist:');
    }

    public function queryInBlade($absPath, $class, $lineNumber)
    {
        $key = 'queryInBlade';
        array_push($this->counts[$key], (new PendingError($key))
            ->header('Query in blade file: ')
            ->errorData($this->yellow($class).'  <=== DB query in blade file')
            ->link($absPath, $lineNumber));
    }

    public function wrongMethodError($absPath, $class, $lineNumber)
    {
        $this->pendError($absPath, $lineNumber, $class, 'wrongMethodError', 'Method does not exist:');
    }

    public function yellow($msg)
    {
        return "<fg=yellow>$msg</>";
    }

    public function badNamespace($absPath, $correctNamespace, $incorrectNamespace, $lineNumber = 4)
    {
        array_push($this->counts['badNamespace'], (new PendingError('badNamespace'))
            ->header('Incorrect namespace: '.$this->yellow("namespace $incorrectNamespace;"))
            ->errorData('  namespace fixed to:  '.$this->yellow("namespace $correctNamespace;"))
            ->link($absPath, $lineNumber));
    }

    public function print($msg, $path = '|  ', $len = null)
    {
        ! $len && $len = PendingError::$maxLength + 1;
        $msgLen = strlen($msg);
        if (strpos($msg, 'yellow')) {
            $msgLen = $msgLen - 14;
        }
        $len = $len - $msgLen;
        if ($len < 0) {
            $len = 0;
        }

        $this->printer->writeln($path.$msg.str_repeat(' ', $len).'|');
    }

    public function printHeader($msg)
    {
        $number = ++$this->counts['total'];
        ($number < 10) && $number = " $number";

        $number = '<fg=yellow>'.$number.' </>';
        $path = "| $number";

        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg));
        $this->print('');
        $this->print($msg, $path, PendingError::$maxLength - 1);
    }

    public function end()
    {
        $this->print('');
        $this->printer->writeln('|'.str_repeat('*', 3 + PendingError::$maxLength).'|  ');
    }

    public function printLink($path, $lineNumber = 4)
    {
        if ($path) {
            $filePath = trim(str_replace(base_path(), '', $path), '\\/');
            $this->print('at <fg=green>'.$filePath.'</>'.':<fg=green>'.$lineNumber.'</>', '', PendingError::$maxLength + 30);
        }
    }

    /**
     * Checks for errors for the run command.
     *
     * @return int
     */
    public function hasErrors()
    {
        $errorsCollection = collect($this->counts);

        return $errorsCollection->flatten()->filter(function ($action) {
            return $action instanceof PendingError;
        })->count();
    }

    public function logErrors()
    {
        collect($this->counts)->except('total')->flatten()->each(function ($error) {
            if ($error instanceof PendingError) {
                $this->printHeader($error->getHeader());
                $this->print($error->getErrorData());
                $this->printLink($error->getLinkPath(), $error->getLinkLineNumber());
                $this->end();
            }
        });

        foreach ($this->pended as $pend) {
            $this->print($pend);
            $this->end();
        }
    }

    public function printTime()
    {
        $this->logErrors && $this->printer->writeln('Total elapsed time: '.round(microtime(true) - microscope_start, 2).' sec', 2);
    }
}
