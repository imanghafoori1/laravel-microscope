<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

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

    public $logErrors = true;

    public function view($absPath, $lineContent, $lineNumber, $fileName)
    {
        array_push($this->counts['view'], (new PendingError('view'))
            ->header($this->yellow($fileName.'.blade.php').' does not exist')
            ->errorData(trim($lineContent))
            ->link($absPath, $lineNumber));
    }

    public function route($path, $errorIt, $errorTxt, $absPath = null, $lineNum = 0)
    {
        array_push($this->counts['route'], (new PendingError('route'))
            ->header($errorIt)
            ->errorData($errorTxt.$this->yellow($path))
            ->link($absPath, $lineNum));
    }

    public function bladeImport($class, $absPath, $lineNum)
    {
        array_push($this->counts['bladeImport'], (new PendingError('bladeImport'))
            ->header('Class does not exist:')
            ->errorData($this->yellow($class).' <==== does not exist')
            ->link($absPath, $lineNum));
    }

    public function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    public function badRelation($path, $lineNumber, $relatedModel)
    {
        array_push($this->counts['badRelation'], (new PendingError('badRelation'))
            ->header('Wrong model is passed in relation:')
            ->errorData($this->yellow($relatedModel).'   <==== does not exist')
            ->link($path, $lineNumber));
    }

    public function wrongImport($absPath, $class, $line)
    {
        array_push($this->counts['wrongImport'], (new PendingError('wrongImport'))
            ->header('Wrong import:')
            ->errorData($this->yellow("use $class;").'   <==== does not exist. ')
            ->link($absPath, $line));
    }

    public function wrongUsedClassError($absFilePath, $class, $lineNum)
    {
        array_push($this->counts['wrongUsedClassError'], (new PendingError('wrongUsedClassError'))
            ->header('Class does not exist:')
            ->errorData($this->yellow($class).'  <==== does not exist.')
            ->link($absFilePath, $lineNum));
    }

    public function wrongMethodError($absFilePath, $class, $lineNum)
    {
        array_push($this->counts['wrongUsedClassError'], (new PendingError('wrongUsedClassError'))
            ->header('Method does not exist:')
            ->errorData($this->yellow($class).' <=== does not exist.')
            ->link($absFilePath, $lineNum));
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
    public function badNamespace($classPath, $correctNamespace, $incorrectNamespace, $linkLineNum = 4)
    {
        array_push($this->counts['badNamespace'], (new PendingError('badNamespace'))
            ->header('Incorrect namespace: '.$this->yellow("namespace $incorrectNamespace;"))
            ->errorData('namespace fixed to: '.$this->yellow("namespace $correctNamespace;"))
            ->link($classPath, $linkLineNum));
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
        collect($this->counts)
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
