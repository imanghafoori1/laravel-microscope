<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\Analyzers\FilePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;

class ErrorPrinter
{
    public $errorsList = [
        'total' => 0,
    ];

    public $printer;

    public $logErrors = true;

    public $pended = [];

    public function view($absPath, $lineContent, $lineNumber, $fileName)
    {
        if (LaravelPaths::isIgnored($absPath)) {
            return;
        }

        ($this->errorsList['view'][] = (new PendingError('view'))
            ->header(\trim($lineContent))
            ->errorData($this->yellow($fileName.'.blade.php').' does not exist')
            ->link($absPath, $lineNumber));
    }

    public function printFixation($absPath, $wrongClass, $lineNumber, $correct)
    {
        $header = $wrongClass.'  <===  Did not exist';
        $msg = 'Auto-corrected to:   '.substr($correct[0], 0, 55);

        $this->simplePendError($absPath, $lineNumber, $msg, 'ns_replacement', $header);
    }

    public function route($path, $errorIt, $errorTxt, $absPath = null, $lineNumber = 0)
    {
        if (LaravelPaths::isIgnored($absPath)) {
            return;
        }

        ($this->errorsList['route'][] = (new PendingError('route'))
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
        if (LaravelPaths::isIgnored($path)) {
            return;
        }

        ($this->errorsList[$key][] = (new PendingError($key))
            ->header($header)
            ->errorData($this->yellow($absent).'   <==== does not exist')
            ->link($path, $lineNumber));
    }

    public function routelessAction($absPath, $lineNumber, $action)
    {
        if (LaravelPaths::isIgnored($absPath)) {
            return;
        }

        $key = 'routelessCtrl';
        ($this->errorsList[$key][] = (new PendingError($key))
            ->header('No route is defined for controller action:')
            ->errorData($this->yellow($action))
            ->link($absPath, $lineNumber));
    }

    public function simplePendError($path, $lineNumber, $absent, $key, $header)
    {
        if (LaravelPaths::isIgnored($path)) {
            return;
        }

        ($this->errorsList[$key][] = (new PendingError($key))
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
        if (LaravelPaths::isIgnored($path)) {
            return;
        }

        ($this->errorsList[$key][] = (new PendingError($key))
            ->header($header)
            ->errorData($this->yellow(\implode(', ', array_keys($absent))).' does not exist')
            ->link($path, $lineNumber));
    }

    public function routeDefinitionConflict($route1, $route2, $info)
    {
        if (LaravelPaths::isIgnored($info[0]['file'] ?? 'unknown')) {
            return;
        }

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

        $msg .= "\n".' at '.($info[0]['file'] ?? 'unknown').':'.($info[0]['line'] ?? 2);
        $msg .= "\n".' is overridden by ';

        $routeName = $route2->getName();
        if ($routeName) {
            $routeName = $this->yellow($routeName);
            $msg .= 'route name: '.$routeName;
        } else {
            $msg .= 'an other route with same uri.';
        }

        $msg .= "\n".' at '.($info[1]['file'] ?? ' ').':'.$info[1]['line']."\n";

        $methods = \implode(',', $route1->methods());

        $this->errorsList[$key][$methods] = (new PendingError($key))
            ->header('Route with uri: '.$this->yellow($methods.': /'.$route1->uri()).' is overridden.')
            ->errorData($msg);
    }

    public function wrongUsedClassError($absPath, $class, $lineNumber)
    {
        $this->pendError($absPath, $lineNumber, $class, 'wrongUsedClassError', 'Class does not exist:');
    }

    public function queryInBlade($absPath, $class, $lineNumber)
    {
        if (LaravelPaths::isIgnored($absPath)) {
            return;
        }

        $key = 'queryInBlade';
        ($this->errorsList[$key][] = (new PendingError($key))
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
        if (LaravelPaths::isIgnored($absPath)) {
            return;
        }

        ($this->errorsList['badNamespace'][] = (new PendingError('badNamespace'))
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
        $number = ++$this->errorsList['total'];
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
            $filePath = FilePath::normalize(\trim(\str_replace(base_path(), '', $path), '\\/'));
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
        $errorsCollection = collect($this->errorsList);

        return $errorsCollection->flatten()->filter(function ($action) {
            return $action instanceof PendingError;
        })->count();
    }

    public function logErrors()
    {
        collect($this->errorsList)->except('total')->flatten()->each(function ($error) {
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

    private static function possibleFixMsg($pieces)
    {
        $fixes = \implode("\n - ", $pieces);
        $fixes && $fixes = "\n Possible fixes:\n - ".$fixes;

        return $fixes;
    }

    public function wrongImportPossibleFixes($absPath, $class, $line, $fixes)
    {
        $fixes = self::possibleFixMsg($fixes);
        $this->wrongUsedClassError($absPath, $class.'   <===  \(-_-)/  '.$fixes, $line);
    }

    public function getCount($key)
    {
        return \count($this->errorsList[$key] ?? []);
    }

    public function printTime()
    {
        $this->logErrors && $this->printer->writeln('time: '.round(microtime(true) - microscope_start, 3).' (sec)', 2);
    }

    public static function thanks($command)
    {
        $command->line(PHP_EOL.'<fg=blue>|-------------------------------------------------|</>');
        $command->line('<fg=blue>|-----------     Star Me On Github     -----------|</>');
        $command->line('<fg=blue>|-------------------------------------------------|</>');
        $command->line('<fg=blue>|  Hey man, if you have found microscope useful   |</>');
        $command->line('<fg=blue>|  Please consider giving it an star on github.   |</>');
        $command->line('<fg=blue>|  \(^_^)/    Regards, Iman Ghafoori    \(^_^)/   |</>');
        $command->line('<fg=blue>|-------------------------------------------------|</>');
        $command->line('https://github.com/imanghafoori1/microscope');
    }
}
