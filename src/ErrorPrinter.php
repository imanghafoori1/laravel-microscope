<?php

namespace Imanghafoori\LaravelSelfTest;

class ErrorPrinter
{
    function bladeImport($class, $blade)
    {
        $this->print('Class does not exist:');
        $this->print(trim(str_replace(base_path(), '', $blade->getPathname()), '\\/').'      line: '.$class['line']);
        $this->print('"'.$class['class'].'" does not exist');
        $this->end();
    }

    function authConf()
    {
        $this->print('The model in the "config/auth.php" is not a valid class');
    }

    function badRelation(\ReflectionClass $ref, \ReflectionMethod $method, $p)
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
    function wrongImport(string $err, $imp)
    {
        $this->print('Wrong import');
        $this->print($err.'  line: '.$imp[1]);
        $this->print('use '.$imp[0].';     <==== does not exist. ');
        $this->end();
    }

    function wrongUsedClassError($absFilePath, $nonImportedClass)
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
    function badNamespace(string $classPath, string $correctNamespace, $incorrectNamespace)
    {
        $this->print('Incorrect namespace: '.$incorrectNamespace);
        $this->print('At: '.$classPath);
        $this->print('It should be:   namespace '.$correctNamespace.';  ');
        $this->end();
    }

    public function print($msg)
    {
        $len = 56 - strlen($msg);
        if ($len < 0) {
            $len = 0;
        }
        dump('  |    '.$msg.str_repeat(' ', $len).'|  ');
    }

    protected function end(): void
    {
        dump('  |'.str_repeat('*', 60).'|  ');
    }
}
