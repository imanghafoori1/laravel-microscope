<?php


namespace EasyPanelTest\Feature\E2E;


use EasyPanelTest\TestCase;
use Livewire\Livewire;
use EasyPanel\Http\Livewire\Translation\Manage;
use EasyPanel\Support\Contract\LangManager;

class TranslationTest extends TestCase
{

    /** @test * */
    public function validations_works_properly(){
        LangManager::shouldReceive('getTexts')->andReturn([]);

        Livewire::test(Manage::class)
            ->set('language', 'fa')
            ->call('create')
            ->assertDispatchedBrowserEvent('show-message')
            ->assertHasNoErrors();
    }

    /** @test * */
    public function create_a_new_lang_requires_a_name(){
        LangManager::shouldReceive('getTexts')->andReturn([]);

        Livewire::test(Manage::class)
            ->set('language', '')
            ->call('create')
            ->assertHasErrors();
    }

    /** @test * */
    public function name_must_be_at_least_two_character_to_create_a_new_lang(){
        LangManager::shouldReceive('getTexts')->andReturn([]);

        Livewire::test(Manage::class)
            ->set('language', 'a')
            ->call('create')
            ->assertHasErrors();
    }

    /** @test * */
    public function lang_must_be_a_string(){
        LangManager::shouldReceive('getTexts')->andReturn([]);

        Livewire::test(Manage::class)
            ->set('language', true)
            ->call('create')
            ->assertHasErrors()
            ->set('language', 10)
            ->assertHasErrors();
    }

    /** @test * */
    public function language_must_be_ten_character_maximum(){
        LangManager::shouldReceive('getTexts')->andReturn([]);

        Livewire::test(Manage::class)
            ->set('language', 'ABCDEFGHIJKLMNO')
            ->call('create')
            ->assertHasErrors();
    }

    /** @test * */
    public function lang_updates_properly(){
        $array = [
            'TEST' => '::test::'
        ];

        LangManager::shouldReceive('getTexts')->andReturn($array);
        LangManager::shouldReceive('updateLanguage')->with('test', $array)->andReturn($array);

        Livewire::test(Manage::class)
            ->set('selectedLang', 'test')
            ->call('update')
            ->assertDispatchedBrowserEvent('show-message');
    }

    /** @test * */
    public function selected_lang_is_read_from_config(){
        LangManager::shouldReceive('getTexts')->andReturn([]);

        config()->set('easy_panel.lang', 'fa');

        $lang = Livewire::test(Manage::class)
            ->get('selectedLang');

        $this->assertEquals('fa_panel', $lang);
    }

    /** @test * */
    public function when_lang_is_null_in_config_a_default_value_will_be_used(){
        LangManager::shouldReceive('getTexts')->andReturn([]);

        $lang = Livewire::test(Manage::class)
            ->get('selectedLang');

        $this->assertEquals('en_panel', $lang);
    }

    /** @test * */
    public function when_language_changes_the_texts_will_be_reread(){
        $array = [
            'TEST' => '::test::'
        ];

        LangManager::shouldReceive('getTexts')->andReturn($array);
        LangManager::shouldReceive('updateLanguage')->with('fa_panel', $array)->andReturn($array);

        $func = function ($array){
            $this->assertArrayHasKey('TEST', $array);
        };

        tap(Livewire::test(Manage::class)
            ->set('selectedLang', 'fa_panel')
            ->get('texts'), $func);

    }

    /** @test * */
    public function language_will_be_reset_after_creation(){
        LangManager::shouldReceive('getTexts')->andReturn([]);

        $func = function ($lang){
            $this->assertEmpty($lang);
        };

        tap(Livewire::test(Manage::class)
            ->set('language', 'fa')
            ->call('create')
            ->get('language'), $func);
    }

}
