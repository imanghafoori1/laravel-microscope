<?php

namespace Imanghafoori\LaravelSelfTest;

class ErrorPrinter
{
    function badRelation(\ReflectionClass $ref, \ReflectionMethod $method, $p)
    {
        $this->print('- Wrong model is passed in relation:');
        $this->print($ref->getName().'@'.$method->getShortName());
        $this->print($p[0].' does not exist');
    }

    /**
     * @param  string  $err
     * @param $imp
     */
    function wrongImport(string $err, $imp)
    {
        $this->print(' - Wrong import');
        $this->print($err);
        $this->print('line: '.$imp[1].'     use '.$imp[0].';');
        $this->print('/********************************************/');
    }

    function wrongUsedClassError($absFilePath, $nonImportedClass)
    {
        $this->print('Used class does not exist.');
        $this->print(str_replace(base_path(), '', $absFilePath));
        $this->print('line: '.$nonImportedClass['line'].'    '.$nonImportedClass['class']);
        $this->print('---------------------');
    }

    /**
     * @param  string  $classPath
     * @param  string  $correctNamespace
     *
     * @return void
     */
    function badNamespace(string $classPath, string $correctNamespace, $incorrectNamespace)
    {
        $this->print('- Incorrect namespace: '.$incorrectNamespace);
        $this->print('At: '.$classPath);
        $this->print('It should be:   namespace '.$correctNamespace.';  ');
    }

    public function print($msg)
    {
        dump($msg);
    }
}
