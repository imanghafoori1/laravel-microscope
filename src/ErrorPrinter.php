<?php

namespace Imanghafoori\LaravelMicroscope;

class ErrorPrinter
{
    public function view($file, $line, $lineNumber, $name)
    {
        $this->print($file.', line: '.$lineNumber);
        $this->print(trim($line));
        $this->print($name.'.blade.php" does not exist');
        $this->end();
    }

    public function bladeImport($class, $blade)
    {
        $this->print('Class does not exist:');
        $this->print(trim(str_replace(base_path(), '', $blade->getPathname()), '\\/').'      line: '.$class['line']);
        $this->print('"'.$class['class'].'" does not exist');
        $this->end();
    }

    public function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    public function badRelation(\ReflectionClass $ref, \ReflectionMethod $method, $p)
    {
        $this->print('Wrong model is passed in relation: ');
        $this->print('file: '.$ref->getName().'@'.$method->getShortName());
        $this->print(''.$p[0].' does not exist');
        $this->end();
    }

    /**
     * @param  string  $err
     * @param $imp
     */
    public function wrongImport(string $err, $imp)
    {
        $this->print('Wrong import');
        $this->print($err.'  line: '.$imp[1]);
        $this->print('use '.$imp[0].';     <==== does not exist. ');
        $this->end();
    }

    public function wrongUsedClassError($absFilePath, $nonImportedClass)
    {
        $this->print('Class does not exist: ');
        $this->print(trim(str_replace(base_path(), '', $absFilePath), '\\/').'  line: '.$nonImportedClass['line']);
        $this->print($nonImportedClass['class'].'  <==== does not exist.');
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
        dump('  |    '.$msg.str_repeat(' ', $len).'|  ');
    }

    public function end()
    {
        dump('  |'.str_repeat('*', 85).'|  ');
    }
}
