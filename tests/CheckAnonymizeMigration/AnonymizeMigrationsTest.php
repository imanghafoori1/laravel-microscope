<?php

namespace CheckAnonymizeMigration;

use Imanghafoori\LaravelMicroscope\Commands\AnonymizeMigrations;
use Imanghafoori\LaravelMicroscope\ErrorReporters\ErrorPrinter;
use Imanghafoori\LaravelMicroscope\FileReaders\BasePath;
use Imanghafoori\LaravelMicroscope\LaravelPaths\LaravelPaths;
use PHPUnit\Framework\TestCase;

class AnonymizeMigrationsTest extends TestCase
{
    public function setUp(): void
    {
        $_SERVER['writeln'] = [];

        BasePath::$path = __DIR__;

        mkdir(__DIR__.'/migrations');
        mkdir(__DIR__.'/vendor/migrations', 0777, true);
        mkdir(__DIR__.'/my/migs', 0777, true);

        copy(__DIR__.'/stubs/CreateUsersTable.stub', __DIR__.'/migrations/CreateUsersTable.php');
        copy(__DIR__.'/stubs/CreateProductsTable.stub', __DIR__.'/migrations/CreateProductsTable.php');
    }

    public function tearDown(): void
    {
        unset($_SERVER['writeln']);

        unlink(__DIR__.'/migrations/CreateUsersTable.php');
        unlink(__DIR__.'/migrations/CreateProductsTable.php');

        rmdir(__DIR__.'/migrations');
        rmdir(__DIR__.'/vendor/migrations');
        rmdir(__DIR__.'/vendor');
        rmdir(__DIR__.'/my/migs');
        rmdir(__DIR__.'/my');
    }

    public function testAnonymizeMigration()
    {
        ErrorPrinter::singleton()->printer = new class
        {
            public function writeln($msg)
            {
                $_SERVER['writeln'][] = $msg;
            }

            public function confirm()
            {
                return true;
            }
        };
        LaravelPaths::$defaultPath = __DIR__.'/migrations';
        LaravelPaths::$migrationDirs = [
            __DIR__.DIRECTORY_SEPARATOR.'migrations_absent',
            __DIR__.DIRECTORY_SEPARATOR.'vendor/migrations',
            __DIR__.DIRECTORY_SEPARATOR.'my/migs',
        ];
        AnonymizeMigrations::$laravelVersion = '10.0.0';
        $migration = new AnonymizeMigrations();
        $migration->input(new class
        {
            public function getOption()
            {
                return '';
            }
        });
        $migration->handleCommand(new class {}, new class {});

        $content = file_get_contents(__DIR__.'/migrations/CreateUsersTable.php');
        $this->assertStringContainsString('return new class extends', $content);
        $this->assertStringContainsString('};', $content);

        $content = file_get_contents(__DIR__.'/migrations/CreateProductsTable.php');
        $this->assertStringNotContainsString('return new class extends', $content);
        $this->assertStringNotContainsString('};', $content);

        $this->assertStringContainsString('CreateUsersTable', $_SERVER['writeln'][0]);
        $this->assertStringContainsString('return new class extends', $_SERVER['writeln'][1]);
        $this->assertStringContainsString('Replacement will occur at:', $_SERVER['writeln'][2]);
        $this->assertStringContainsString('5', $_SERVER['writeln'][3]);
        $this->assertStringContainsString('CreateUsersTable.php', $_SERVER['writeln'][3]);

        $this->assertCount(4, $_SERVER['writeln']);

        AnonymizeMigrations::$laravelVersion = '8.36.0';
        $migration = new AnonymizeMigrations();

        $_SERVER['writeln'] = [];
        $migration->handleCommand(new class {
        }, new class
        {
            public function info($msg)
            {
                $_SERVER['writeln'][] = $msg;
            }
        });

        $this->assertCount(2, $_SERVER['writeln']);
        $this->assertIsArray(LaravelPaths::getMigrationConfig());
    }
}
