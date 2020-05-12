<?php

namespace Imanghafoori\LaravelMicroscope\SpyClasses;

class ViewsData
{
    public $all = [];

    public $main = null;

    public function add($view)
    {
        $this->main ?: ($this->main = $view);
        $this->all[$view->getName()] = $view;
    }

    public function getMainVars()
    {
        $new = [];
        $mainVars = $this->main->getData();
        foreach ($mainVars as $i => $vars) {
            $new['$'.$i] = null;
        }

        return $new;
    }

    public function readTokenizedVars()
    {
        $allVars = [];
        foreach ($this->all as $view) {
            $vars = [];
            $tokens = token_get_all(app('blade.compiler')->compileString(file_get_contents($view->getPath())));
            foreach ($tokens as $token) {
                ($token[0] == T_VARIABLE) && $vars[$token[1]] = null;
            }
            $allVars = $allVars + $vars;
        }

        return $allVars;
    }
}
