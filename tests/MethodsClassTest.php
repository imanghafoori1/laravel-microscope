<?php

namespace Imanghafoori\LaravelMicroscope\Tests;

use function file_get_contents;
use Imanghafoori\LaravelMicroscope\Analyzers\ClassMethods;
use Imanghafoori\LaravelMicroscope\LaravelMicroscopeServiceProvider;
use Orchestra\Testbench\TestCase;
use function token_get_all;

class MethodsClassTest extends TestCase
{
    /** @var array */
    private $classToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->classToken = ClassMethods::read($this->getTokens());
    }

    protected function getPackageProviders($app)
    {
        return [LaravelMicroscopeServiceProvider::class];
    }

    /** @test */
    public function true_is_true()
    {
        $class = $this->classToken;

        $this->assertFalse($class['is_abstract']);
        $this->assertEquals($class['type'], T_CLASS);
        $this->assertCount(8, $class['methods']);
        $this->assertFalse($class['methods'][0]['is_abstract']);

        $this->assertNull($class['methods'][0]['returnType']);
        $this->assertNull($class['methods'][0]['nullable_return_type']);
        $this->assertEquals($class['methods'][0]['signature'], []);
        $this->assertEquals($class['methods'][0]['name'], [311, 'hello1', 5]);
    }

    /** @test */
    private function check_is_static_method()
    {
        $class = $this->classToken;

        $this->assertFalse($class['methods'][0]['is_static']);
        foreach ([4, 5, 6, 7] as $index) {
            $this->assertTrue($class['methods'][$index]['is_static']);
        }
    }

    /** @test
     * @dataProvider checkVisibility
     */
    private function check_visibility($index, $visibility)
    {
        $class = $this->classToken;

        $this->assertEquals($class['methods'][$index]['visibility'][1], $visibility);
    }

    public function checkVisibility(): array
    {
        return [
            [0, 'public'],
            [1, 'protected'],
            [2, 'private'],
            [3, 'public'],
            [4, 'public'],
            [5, 'protected'],
            [6, 'private'],
            [7, 'public'],
        ];
    }

    /**
     * get tokens of stubs classes.
     *
     * @return array
     */
    private function getTokens()
    {
        return token_get_all(file_get_contents(__DIR__.'/stubs/sample_class.php'));
    }
}
