<?php

namespace EasyPanelTest\Unit;

use App\Models\Article;
use EasyPanelTest\Dependencies\User;
use Illuminate\Support\Str;
use EasyPanel\Parsers\HTMLInputs\Text;
use EasyPanel\Parsers\HTMLInputs\Select;
use EasyPanel\Parsers\Fields\Field;
use EasyPanel\Parsers\HTMLInputs\Email;

class StubParserTest extends \EasyPanelTest\TestCase
{

    /** @test * */
    public function make_tab_works(){
        $expected = "\n    ";
        $this->assertEquals($expected, $this->parser->makeTab(1));

        $expected = "    ";
        $this->assertEquals($expected, $this->parser->makeTab(1, false));

        $expected = "        ";
        $this->assertEquals($expected, $this->parser->makeTab(2, false));
    }

    /** @test * */
    public function get_model_name_gives_true_name(){
        $this->assertEquals("Article", $this->parser->getModelName(Article::class));
        $this->assertEquals("User", $this->parser->getModelName(User::class));
    }

    /** @test * */
    public function properties_will_be_parsed(){
        $this->parser->setInputs(['title' => 'text', 'content' => 'textarea']);
        $expected1 = 'public $title;';
        $expected2 = '    public $content;';
        $properties = $this->parser->parseProperties();
        $this->assertStringContainsString($expected1, $properties);
        $this->assertStringContainsString($expected2, $properties);
    }

    /** @test * */
    public function properties_value_will_filled_in_actions(){
        $this->parser->setInputs(['title' => 'text', 'content' => 'textarea']);
        $expected1 = '\'title\' => $this->title,';
        $expected2 = '\'content\' => $this->content,';
        $this->assertStringContainsString($expected1, $this->parser->parseActionInComponent());
        $this->assertStringContainsString($expected2, $this->parser->parseActionInComponent());

        $this->parser->setAuthType(true);
        $expected1 = '\'user_id\' => auth()->id(),';
        $this->assertStringContainsString($expected2, $this->parser->parseActionInComponent());

        $this->parser->setAuthType(false);
        $expected1 = '\'user_id\' => auth()->id(),';
        $this->assertStringNotContainsString($expected1, $this->parser->parseActionInComponent());
    }

    /** @test * */
    public function validation_will_be_parsed(){
        $this->parser->setValidationRules([
            'title' => 'required',
            'content' => 'min:10'
        ]);
        $expected1 = "'title' => 'required'";
        $expected2 = "        'content' => 'min:10'";
        $parsedString = $this->parser->parseValidationRules();
        $this->assertStringContainsString($expected1, $parsedString);
        $this->assertStringContainsString($expected2, $parsedString);
    }

    /** @test * */
    public function data_string_will_be_normalized(){
        $this->parser->setFields(['title']);
        $expected = '<td class="">{{ $article->title }}</td>';
        $this->assertStringContainsString($expected, $this->parser->parseDataInBlade());

        $this->parser->setFields(['user.name']);
        $expected = '<td class="">{{ $article->user->name }}</td>';
        $this->assertStringContainsString($expected, $this->parser->parseDataInBlade());
    }

    /** @test * */
    public function titles_of_table_will_be_parsed(){
        $this->parser->setFields(['title']);
        $expected = "wire:click=\"sort('title')\"";
        $expected2 = "{{ __('Title') }}";
        $this->assertStringContainsString($expected, $this->parser->parseTitlesInBlade());
        $this->assertStringContainsString($expected2, $this->parser->parseTitlesInBlade());

        $this->parser->setFields(['user.name']);
        $notExpected = "wire:click=\"sort('title')\"";
        $expected = "{{ __('User Name') }}";
        $this->assertStringNotContainsString($notExpected, $this->parser->parseTitlesInBlade());
        $this->assertStringContainsString($expected, $this->parser->parseTitlesInBlade());
    }

    /** @test * */
    public function properties_will_be_filled_for_actions(){
        $this->parser->setInputs([
            'title' => 'text',
            'body' => 'ckeditor',
        ]);

        $expected1 = '$this->title = $this->article->title;'."\n";
        $expected2 = '$this->body = $this->article->body;';
        $parsedString = $this->parser->parseSetPropertiesValue();
        $this->assertStringContainsString($expected1, $parsedString);
        $this->assertStringContainsString($expected2, $parsedString);
    }

    /** @test * */
    public function input_class_will_returned_based_on_name(){
        $result = $this->parser->getInputClassNamespace('text');
        $expected = Text::class;
        $this->assertEquals($result, $expected);

        $result = $this->parser->getInputClassNamespace(['select' => []]);
        $expected = Select::class;
        $this->assertEquals($result, $expected);
    }

    /** @test * */
    public function if_the_input_name_is_incorrect_it_will_throw_exception(){
        $this->expectException(\Exception::class);
        $this->parser->getInputClassNamespace('dasdsadas');
    }

    /** @test * */
    public function fields_will_be_normalized(){
        $normalizedField = $this->parser->normalizeField('email');
        $this->assertInstanceOf(Field::class, $normalizedField);

        $this->assertEquals($normalizedField->getTitle(), 'Email');
    }

    /** @test * */
    public function inputs_will_be_normalized(){
        $normalizedField = $this->parser->normalizeInput('email', 'email');
        $this->assertInstanceOf(Email::class, $normalizedField);
        $this->assertEquals($normalizedField->getTitle(), 'Email');

        $normalizedField = $this->parser->normalizeInput('name', 'text');
        $this->assertInstanceOf(Text::class, $normalizedField);
        $this->assertEquals($normalizedField->getTitle(), 'Name');

        $normalizedField = $this->parser->normalizeInput('name', Text::label('Name'));
        $this->assertInstanceOf(Text::class, $normalizedField);
        $this->assertEquals($normalizedField->getTitle(), 'Name');

        $normalizedField = $this->parser->normalizeInput('category', ['select' => []]);
        $this->assertInstanceOf(Select::class, $normalizedField);
    }

}
