<?php

namespace EasyPanel\Http\Livewire\Translation;

use Livewire\Component;
use Illuminate\Support\Facades\File;
use EasyPanel\Support\Contract\LangManager;

class Manage extends Component
{
    public $selectedLang;
    public $texts;
    public $language;

    public function mount()
    {
        $this->selectedLang = (config('easy_panel.lang') ?? 'en').'_panel';
        $this->texts = LangManager::getTexts($this->selectedLang);
    }

    public function updatedSelectedLang($value)
    {
        $this->texts = LangManager::getTexts($value);
    }

    public function render()
    {
        return view('admin::livewire.translation.manage')
            ->layout('admin::layouts.app', ['title' => __('Translation')]);
    }

    protected function getRules()
    {
        return [
            'language' => 'required|min:2|max:10|string'
        ];
    }

    public function create()
    {
        $this->validate();
        try {
            $lang = strtolower($this->language) . '_panel';
            File::copy(LangManager::getPath('en_panel'), LangManager::getPath($lang));

            $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('CreatedMessage', ['name' => __('Translation') ])]);
        } catch (\Exception $exception){
            $this->dispatchBrowserEvent('show-message', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        $this->reset('language');
    }

    public function update()
    {
        LangManager::updateLanguage($this->selectedLang, $this->texts);

        $this->dispatchBrowserEvent('show-message', ['type' => 'success', 'message' => __('UpdatedMessage', ['name' => __('Translation') ])]);
    }
}
