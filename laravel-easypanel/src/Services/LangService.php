<?php

namespace EasyPanel\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LangService
{
    public static function getLanguages()
    {
        $files = collect(static::getFiles());

        return $files->mapWithKeys(function ($file, $key){
            preg_match('/(\w+)_panel\.json/i', $file, $m);
            $key = "$m[1]_panel";
            $value = Str::upper($m[1]);
            return [$key => $value];
        })->toArray();
    }

    public static function updateAll($texts)
    {
        foreach (static::getFiles() as $file) {
            $decodedFile = json_decode(File::get($file), 1);
            foreach ($texts as $key => $text) {
                if (array_key_exists($key, $decodedFile)){
                    unset($texts[$text]);
                }
            }
            $array = array_merge($decodedFile, $texts);
            File::put($file, json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    public static function getFiles()
    {
        return File::glob(app()->langPath().DIRECTORY_SEPARATOR.'*_panel.json');
    }

    public static function getTexts($lang)
    {
        return json_decode(static::getContent($lang), true);
    }

    public static function updateLanguage($lang, $texts)
    {
        $file = static::getContent($lang);

        $decodedFile = json_decode($file, 1);

        $array = array_merge($decodedFile, $texts);

        File::put(static::getPath($lang), json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function getContent($lang)
    {
        return File::get(static::getPath($lang));
    }

    public static function getPath($lang)
    {
        return app()->langPath().DIRECTORY_SEPARATOR."{$lang}.json";
    }
}
