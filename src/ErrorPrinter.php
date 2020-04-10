<?php

namespace Imanghafoori\LaravelMicroscope;

use Imanghafoori\LaravelMicroscope\PendingObjects\PendingError;

class ErrorPrinter
{
    public $counts = [
        'view' => [],
        'route'=>[],
        'total' => 0,
        'bladeImport' => [],
        'badRelation' => [],
        'wrongImport' => [],
        'wrongUsedClassError' => [],
        'badNamespace' => [],
    ];

    public $printer;

    public function view($path, $lineContent, $lineNumber, $fileName)
    {
        array_push($this->counts['view'], (new PendingError('view'))
            ->header($this->yellow($fileName.'.blade.php').' does not exist')
            ->errorData(trim($lineContent))
            ->link($path, $lineNumber));
    }

    public function route($path, $errorIt, $errorTxt, $linkPath = null, $linkLineNum = 0)
    {
        array_push($this->counts['route'], (new PendingError('route'))
            ->header($errorIt)
            ->errorData($errorTxt.$this->yellow($path))
            ->link($linkPath, $linkLineNum));
    }

    public function bladeImport($class, $blade)
    {
        array_push($this->counts['bladeImport'], (new PendingError('bladeImport'))
            ->header('Class does not exist in blade file:')
            ->errorData($this->yellow($class['class']).' <==== does not exist')
            ->link($blade->getPathname(), $class['line']));
    }

    public function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    public function badRelation(\ReflectionClass $ref, \ReflectionMethod $method, $relatedModel)
    {
        array_push($this->counts['badRelation'], (new PendingError('badRelation'))
            ->header('Wrong model is passed in relation:')
            ->errorData($this->yellow($relatedModel).'   <==== does not exist')
            ->link($ref->getFileName(), $method->getStartLine() + 1));
    }

    public function wrongImport($absPath, $class, $line)
    {
        array_push($this->counts['wrongImport'], (new PendingError('wrongImport'))
            ->header('Wrong import:')
            ->errorData($this->yellow("use $class;").'   <==== does not exist. ')
            ->link($absPath, $line));
    }

    public function wrongUsedClassError($absFilePath, $nonImportedClass)
    {
        array_push($this->counts['wrongUsedClassError'], (new PendingError('wrongUsedClassError'))
            ->header('Class does not exist:')
            ->errorData($this->yellow($nonImportedClass['class']).'  <==== does not exist.')
            ->link($absFilePath, $nonImportedClass['line']));
    }

    public function yellow($msg)
    {
        return "<fg=yellow>$msg</>";
    }

    /**
     * @param  string  $classPath
     * @param  string  $correctNamespace
     * @param  string  $incorrectNamespace
     *
     * @return void
     */
    public function badNamespace($classPath, $correctNamespace, $incorrectNamespace)
    {
        $this->printHeader('Incorrect namespace: '.$this->yellow("namespace $incorrectNamespace;"));
        $this->print('Correct namespace:   '.$this->yellow("namespace $correctNamespace;"));
        $this->printLink($classPath);
        $this->end();
        $this->counts['badNamespace']++;
    }

    public function print($msg, $path = '  |    ', $len = 81)
    {
        $msgLen = strlen($msg);
        if (strpos($msg, 'yellow')) {
            $msgLen = $msgLen - 14;
        }
        $len = $len - $msgLen;
        if ($len < 0) {
            $len = 0;
        }

        $this->printer->writeln($path.$msg.str_repeat(' ', $len).'|  ');
    }

    public function printHeader($msg)
    {
        $this->print('');
        $number = ++$this->counts['total'];
        $number = '<fg=yellow>'.$number.' </>';
        $path = "  | $number";

        $this->print($msg, $path);
    }

    public function end()
    {
        $this->print('');
        $this->printer->writeln('  |'.str_repeat('*', 85).'|  ');
    }

    public function printLink($path, $lineNumber = 4)
    {
        if ($path) {
            $filePath = trim(str_replace(base_path(), '', $path), '\\/');
            $this->print('at <fg=green>'.$filePath.'</>'.':<fg=green>'.$lineNumber.'</>', '', 114);
        }
    }

    public function fixedNameSpace($correctNamespace)
    {
        $msg = $this->yellow("namespace $correctNamespace;");
        $this->print('namespace fixed to: '.$msg);
        $this->end();
    }

    /**
     * Checks for errors for the run command.
     *
     * @return int
     */
    public function hasErrors()
    {
        $errorsCollection = collect($this->counts);

        return $errorsCollection
            ->flatten()
            ->filter(function ($action) {
                return $action instanceof PendingError;
            })->count();
    }

    /**
     * Logs the errors to the console.
     */
    public function logErrors()
    {
        $errorsCollection = collect($this->counts)
            ->except('total')
            ->flatten()
            ->each(function ($error) {
                if ($error instanceof PendingError) {
                    $this->printHeader($error->getHeader());
                    $this->print($error->getErrorData());
                    $this->printLink($error->getLinkPath(), $error->getLinkLineNumber());
                    $this->end();
                }
            });
    }
}
