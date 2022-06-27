<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

use Imanghafoori\LaravelMicroscope\FileReaders\FilePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use Symfony\Component\Console\Terminal;

class ErrorPrinter
{
    public $errorsList = [
        'total' => 0,
    ];

    public $printer;

    public $logErrors = true;

    public $pended = [];

    public $count = 0;

    public function view($absPath, $message, $lineNumber, $fileName)
    {
        $this->simplePendError($fileName.'.blade.php', $absPath, $lineNumber, 'view', \trim($message), ' does not exist');
    }

    public function printFixation($absPath, $wrongClass, $lineNumber, $correct)
    {
        $header = $wrongClass.'  <===  Did not exist';
        $msg = 'Fixed to:   '.substr($correct[0], 0, 55);

        $this->simplePendError($msg, $absPath, $lineNumber, 'ns_replacement', $header);
    }

    public function route($path, $errorIt, $errorTxt, $absPath = null, $lineNumber = 0)
    {
        $this->simplePendError($path, $absPath, $lineNumber, 'route', $errorIt, $errorTxt);
    }

    public function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    public function badRelation($absPath, $lineNumber, $relatedModel)
    {
        $header = 'Wrong model is passed in relation:';

        $this->doesNotExist($relatedModel, $absPath, $lineNumber, 'badRelation', $header);
    }

    public function doesNotExist($yellowText, $absPath, $lineNumber, $key, $header)
    {
        $this->simplePendError($yellowText, $absPath, $lineNumber, $key, $header);
    }

    public function routelessAction($absPath, $lineNumber, $msg)
    {
        $this->simplePendError($msg, $absPath, $lineNumber, 'routelessCtrl', 'No route is defined for controller action:');
    }

    public function wrongImport($absPath, $class, $lineNumber)
    {
        $this->doesNotExist("use $class;", $absPath, $lineNumber, 'wrongImport', 'Wrong import:');
    }

    private function addPendingError($path, $lineNumber, $key, $header, $errorData)
    {
        if (LaravelPaths::isIgnored($path)) {
            return;
        }
        $this->count++;
        ($this->errorsList[$key][] = (new PendingError($key))
            ->header($header)
            ->errorData($errorData)
            ->link($path, $lineNumber));
    }

    public function simplePendError($yellowText, $absPath, $lineNumber, $key, $header, $rest = '', $pre = '')
    {
        $errorData = $pre.$this->yellow($yellowText).$rest;

        $this->addPendingError($absPath, $lineNumber, $key, $header, $errorData);
    }

    public function compactError($path, $lineNumber, $absent, $key, $header)
    {
        $errorData = $this->yellow(\implode(', ', array_keys($absent))).' does not exist';

        $this->addPendingError($path, $lineNumber, $key, $header, $errorData);
    }

    public function queryInBlade($absPath, $class, $lineNumber)
    {
        $key = 'queryInBlade';
        $errorData = $this->yellow($class).'  <=== DB query in blade file';
        $header = 'Query in blade file: ';

        $this->addPendingError($absPath, $lineNumber, $key, $header, $errorData);
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
        $this->doesNotExist($class, $absPath, $lineNumber, 'wrongUsedClassError', 'Class does not exist:');
    }

    public function extraImport($absPath, $class, $lineNumber)
    {
        $this->doesNotExist($class, $absPath, $lineNumber, 'extraImport', 'Import is not used:');
    }

    public function wrongMethodError($absPath, $class, $lineNumber)
    {
        $this->doesNotExist($class, $absPath, $lineNumber, 'wrongMethodError', 'Method does not exist:');
    }

    public function yellow($msg)
    {
        return "<fg=blue>$msg</>";
    }

    public function fixedNamespace($absPath, $correctNamespace, $incorrectNamespace, $lineNumber = 4)
    {
        $key = 'badNamespace';
        $header = 'Incorrect namespace: '.$this->yellow("namespace $incorrectNamespace;");
        $errorData = '  namespace fixed to:  '.$this->yellow("namespace $correctNamespace;");

        $this->addPendingError($absPath, $lineNumber, $key, $header, $errorData);
    }

    public function wrongFileName($absPath, $class, $file)
    {
        $key = 'badFileName';
        $header = 'The file name and the class name are different.';
        $errorData = 'Class name: <fg=blue>"'.$class.'"</> - File name: <fg=blue>"'.$file.'"</>';

        $this->addPendingError($absPath, 1, $key, $header, $errorData);
    }

    public function print($msg, $path = '   ')
    {
        $this->printer->writeln($path.$msg);
    }

    public function printHeader($msg)
    {
        $number = ++$this->errorsList['total'];
        ($number < 10) && $number = " $number";

        $number = '<fg=bright-cyan>'.$number.' </>';
        $path = "  $number";

        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg), (new Terminal)->getWidth() - 6);
        PendingError::$maxLength = min(PendingError::$maxLength, (new Terminal)->getWidth() - 6);
        $this->print('<fg=red>'.$msg.'</>', $path, PendingError::$maxLength - 1, strlen($msg));
    }

    public function end()
    {
        $this->printer->writeln(' '.'<fg=gray>'.str_repeat('_', 3 + PendingError::$maxLength).'</>'.' ');
    }

    public function printLink($path, $lineNumber = 4, $len = null)
    {
        $len === null && ($len = PendingError::$maxLength + 30);

        if ($path) {
            $relativePath = FilePath::normalize(\trim(\str_replace(base_path(), '', $path), '\\/'));
            $this->print('at <fg=green>'.$relativePath.'</>'.':<fg=green>'.$lineNumber.'</>', '', $len);
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
        $this->wrongUsedClassError($absPath, $class.' '.$fixes, $line);
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

    public static function warnIncorrectNamespace($currentNamespace, $relativePath, $class)
    {
        /**
         * @var $p ErrorPrinter
         */
        $p = app(ErrorPrinter::class);
        $msg = 'Incorrect namespace: '.$p->yellow("namespace $currentNamespace;");
        PendingError::$maxLength = max(PendingError::$maxLength, strlen($msg));
        $p->end();
        $currentNamespace && $p->printHeader('Incorrect namespace: '.$p->yellow("namespace $currentNamespace;"));
        ! $currentNamespace && $p->printHeader('Namespace Not Found: '.$class);
        $p->printLink($relativePath, 3);
    }

    public static function ask($command, $correctNamespace)
    {
        if ($command->option('force')) {
            return true;
        }

        return $command->getOutput()->confirm('Do you want to change it to: '.$correctNamespace, true);
    }
}
