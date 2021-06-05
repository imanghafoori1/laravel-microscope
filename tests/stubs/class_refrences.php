<?php

namespace Imanghafoori\LaravelMicroscope\FileReaders;

use Illuminate\Contracts\HalfImported;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Symfony\Component\Finder\Finder;

class Paths extends ParentClass implements InterF1, InterF2
{
    use Trait1, Trait2;

    use Trait3;



    public static function getAbsFilePaths(TypeHint1 $dirs, ?TypeHint2 $dir3, $arg = [1, 2])
    {
        if (! $dirs) {
            return [];
        }
        try {
            $files = Finder::create()->files()->name('*.php')->in($dirs);

            $paths = [];
            foreach ($files as $f) {
                $paths[] = $f->getRealPath();
            }

            return $paths;
        } catch (DirectoryNotFoundException $e) {
            return [];
        }

        try {
            $files = Finder::create()->in($dirs);
        } catch (\Exception | \ErrorException $e) {
            return [];
        }

        MyAmIClass::con;
        \YetAnotherclass::koo();  $goo::koo();
        HalfImported\TheRest::class;
    }

    public function returny_Method(string $string): int
    {
        new $tt;
    }

    public function returny_nullable(TypeHint1 $dirs): ?ReturnyType2
    {
        new class {};
    }

    public function returny_string(?string $string): self
    {
        $tt = '';
        new Newed();
        new A\Newed;
        new $tt;
    }
}
