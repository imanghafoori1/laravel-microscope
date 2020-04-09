<?php

namespace Imanghafoori\LaravelMicroscope;

class ErrorPrinter
{
    public $counts
        = [
            'view'                => 0,
            'total'               => 0,
            'bladeImport'         => 0,
            'badRelation'         => 0,
            'wrongImport'         => 0,
            'wrongUsedClassError' => 0,
            'badNamespace'        => 0,
        ];

    public $printer;

    public function view($path, $lineContent, $lineNumber, $fileName)
    {
        $this->printHeader($this->yellow($fileName.'.blade.php').' does not exist');
        $this->print(trim($lineContent));
        $this->printLink($path, $lineNumber);
        $this->end();
        $this->counts['view']++;
    }

    public function bladeImport($class, $blade)
    {
        $this->printHeader('Class does not exist in blade file:');
        $this->print($this->yellow($class['class']).' <==== does not exist');
        $this->printLink($blade->getPathname(), $class['line']);

        $this->end();
        $this->counts['bladeImport']++;
    }

    public function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    public function badRelation(\ReflectionClass $ref, \ReflectionMethod $method, $relatedModel)
    {
        $this->printHeader('Wrong model is passed in relation:');
        $this->print($this->yellow($relatedModel).'   <==== does not exist');
        $this->printLink($ref->getFileName(), $method->getStartLine() + 1);
        $this->end();
        $this->counts['badRelation']++;
    }

    public function wrongImport($classReflection, $class, $line)
    {
        $this->printHeader('Wrong import:');
        $this->print($this->yellow("use $class;").'   <==== does not exist. ');
        $this->printLink($classReflection->getFileName(), $line);
        $this->end();
        $this->counts['wrongImport']++;
    }

    public function wrongUsedClassError($absFilePath, $nonImportedClass)
    {
        $this->printHeader('Class does not exist:');
        $this->print($this->yellow($nonImportedClass['class']).'  <==== does not exist.');
        $this->printLink($absFilePath, $nonImportedClass['line']);
        $this->end();
        $this->counts['wrongUsedClassError']++;
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
        $number = '<fg=yellow>'.$number.'  </>';
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
        $filePath = trim(str_replace(base_path(), '', $path), '\\/');
        $this->print('at <fg=green>'.$filePath.'</>'.':<fg=green>'.$lineNumber.'</>', '', 114);
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

        return $errorsCollection->filter(function ($action) {
            return $action > 0;
        })->count();
    }
}
