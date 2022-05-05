<?php

namespace EasyPanel\Concerns;

use EasyPanel\Support\Contract\LangManager;

trait Translatable
{
    protected $texts = [];

    public function addText($text, $key = null)
    {
        if (array_key_exists($key, $this->texts)){
            return;
        }

        $this->texts[$key ?? $text] = $text;
    }

    public function translate()
    {
        LangManager::updateAll($this->texts);
    }

    public function getTexts()
    {
        return $this->texts;
    }
}
