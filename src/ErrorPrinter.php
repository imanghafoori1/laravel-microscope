<?php

namespace Imanghafoori\LaravelMicroscope;

class ErrorPrinter
{
    public $printer;

    public function view($file, $line, $lineNumber, $name)
    {
        $this->printLink($file, $lineNumber);
        $this->print(trim($line));
        $this->print($name.'.blade.php" does not exist');
        $this->end();
    }

    public function bladeImport($class, $blade)
    {
        $this->print('Class does not exist:');
        $this->print('"'.$class['class'].'" does not exist');
        $path = $blade->getPathname();
        $this->printLink($path, $class['line']);
        $this->end();
    }

    public function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    public function badRelation(\ReflectionClass $ref, \ReflectionMethod $method, $p)
    {
        $this->print('Wrong model is passed in relation: ');
        $this->print(''.$p[0].' does not exist');
        $this->printLink($ref->getFileName(), 1);
        $this->end();
    }

    /**
     * @param  string  $err
     * @param $imp
     */
    public function wrongImport($classReflection, $imp)
    {
        $this->print('Wrong import');
        $this->print('use '.$imp[0].';     <==== does not exist. ');
        $this->printLink($classReflection->getFileName(), $imp[1]);
        $this->end();
    }

    public function wrongUsedClassError($absFilePath, $nonImportedClass)
    {
        $this->print('Class does not exist: ');
        $this->print($nonImportedClass['class'].'  <==== does not exist.');
        $this->printLink($absFilePath, $nonImportedClass['line']);
        $this->end();
    }

    /**
     * @param  string  $classPath
     * @param  string  $correctNamespace
     *
     * @return void
     */
    public function badNamespace(string $classPath, string $correctNamespace, $incorrectNamespace)
    {
        $this->print('Incorrect namespace: '.$incorrectNamespace);
        $this->print('At: '.$classPath);
        $this->print('It should be:   namespace '.$correctNamespace.';  ');
        $this->end();
    }

    public function print($msg)
    {
        $len = 81 - strlen($msg);
        if ($len < 0) {
            $len = 0;
        }

        $this->printer->writeln('  |    '.$msg.str_repeat(' ', $len).'|  ');
    }

    public function end()
    {
        $this->printer->writeln('  |'.str_repeat('*', 85).'|  ');
    }

    private function printLink($path, $lineNumber)
    {
        $filePath = trim(str_replace(base_path(), '', $path), '\\/');
        $this->printer->writeln('at <fg=green>'.$filePath.'</>'.':<fg=green>'.$lineNumber.'</>');
    }
}
