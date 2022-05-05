<?php


namespace EasyPanelTest\Unit;


use EasyPanelTest\TestCase;
use EasyPanel\Parsers\HTMLInputs\InputList;
use EasyPanel\Parsers\HTMLInputs\Text;
use EasyPanel\Parsers\HTMLInputs\Textarea;
use EasyPanel\Parsers\HTMLInputs\Ckeditor;

class InputListClassTest extends TestCase
{
    /** @test * */
    public function it_returns_the_true_namespace(){
        $result = InputList::get('text');
        $expected = Text::class;

        $this->assertEquals($result, $expected);

        $result = InputList::get('textarea');
        $expected = Textarea::class;
        $this->assertEquals($result, $expected);


        $result = InputList::get('ckeditor');
        $expected = Ckeditor::class;
        $this->assertEquals($result, $expected);
    }

    /** @test * */
    public function it_throws_an_exception_with_a_wrong_name(){
        $this->expectException(\Exception::class);

        InputList::get('undefined');
    }

}
