<?php

namespace Imanghafoori\LaravelMicroscope\Features\CheckExtraFQCN;

use Imanghafoori\LaravelMicroscope\Foundations\PhpFileDescriptor;

class FqcnDeleter
{
    public static function delete(PhpFileDescriptor $file, $classRef)
    {
        $line = $classRef['line'];
        $classRef = $classRef['class'];
        $lines = file($file->getAbsolutePath());
        $count = 0;

        $new = str_replace([$classRef], self::className($classRef), $lines[$line - 1], $count);
        if ($count === 1) {
            $lines[$line - 1] = $new;
            $file->putContents(implode('', $lines));

            return true;
        }

        if ($count > 1) {
            [$count, $new] = self::replace($classRef, $lines[$line - 1]);
            if ($count > 0) {
                $lines[$line - 1] = $new;
                $file->putContents(implode('', $lines));

                return true;
            }
        }

        return false;
    }

    private static function className($class)
    {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);

        return basename($class);
    }

    private static function replace($classRef, $subject)
    {
        $className = self::className($classRef);
        $search = [$classRef.' ', $classRef.'(', $classRef.'::', $classRef.')', $classRef.';'];
        $replace = [$className.' ', $className.'(', $className.'::', $className.')', $className.';'];
        $count = 0;
        $new = str_replace($search, $replace, $subject, $count);

        return [$count, $new];
    }
}