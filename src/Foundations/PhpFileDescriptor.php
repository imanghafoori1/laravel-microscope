<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Imanghafoori\Filesystem\FileManipulator;
use Imanghafoori\Filesystem\Filesystem;
use Imanghafoori\LaravelMicroscope\Analyzers\ComposerJson;
use Imanghafoori\SearchReplace\Searcher;

class PhpFileDescriptor
{
    /**
     * @var Path
     */
    private $path;

    /**
     * @var array
     */
    private $tokens = [];

    /**
     * @var \Closure
     */
    private $tokenizer = null;

    public static function make($absolutePath)
    {
        $obj = new self();

        $obj->path = Path::make($absolutePath);

        return $obj;
    }

    /**
     * @return false|string
     */
    public function getMd5()
    {
        return md5_file($this->path->getWithUnixDirectorySeprator());
    }

    public function setTokenizer($tokenizer)
    {
        $this->tokenizer = $tokenizer;
    }

    public function getTokens($reload = false)
    {
        if (! $this->tokens || $reload) {
            $this->tokens = $this->tokenizer ? ($this->tokenizer)($this->path) : $this->tokenize();
        }

        return $this->tokens;
    }

    /**
     * @return string
     */
    public function getAbsolutePath()
    {
        return $this->path->__toString();
    }

    /**
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Path
     */
    public function relativePath()
    {
        return $this->path->relativePath();
    }

    public function getLine(int $lineNumber)
    {
        return file($this->getAbsolutePath())[$lineNumber - 1] ?? '';
    }

    public function getNamespace()
    {
        return ComposerJson::make()->getNamespacedClassFromPath($this->getAbsolutePath());
    }

    /**
     * @return false|string
     */
    public function getContent()
    {
        return file_get_contents($this->getAbsolutePath());
    }

    /**
     * @return array
     */
    private function tokenize()
    {
        return token_get_all($this->getContent());
    }

    /**
     * @param  list  $tokens
     * @return void
     */
    public function setTokens($tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @return \Imanghafoori\LaravelMicroscope\Foundations\Path
     */
    public function path()
    {
        return $this->path;
    }

    public function putContents($newVersion)
    {
        $this->tokens = [];

        return Filesystem::$fileSystem::file_put_contents($this->getAbsolutePath(), $newVersion);
    }

    public function replaceFirst($search, $replace)
    {
        return $this->replaceFirstAtLine($search, $replace, null);
    }

    public function replaceFirstAtLine($search, $replace, $line)
    {
        return FileManipulator::replaceFirst($this->getAbsolutePath(), $search, $replace, $line);
    }

    public function replaceAtLine($search, $replace, $lineNum)
    {
        $this->tokens = [];

        return FileManipulator::replaceFirst($this->getAbsolutePath(), $search, $replace, $lineNum);
    }

    public function searchReplacePatterns($search, $replace)
    {
        [$newVersion, $lines] = self::searchReplace($search, $replace, $this->tokens);

        $this->putContents($newVersion);

        return $lines;
    }

    private static function pattern($search, $replace): array
    {
        return [
            'fix' => [
                'search' => $search,
                'replace' => $replace,
            ],
        ];
    }

    public static function searchReplace($search, $replace, $tokens): array
    {
        return Searcher::searchReplace(self::pattern($search, $replace), $tokens);
    }

    public function insertNewLine($newLine, $atLine)
    {
        return FileManipulator::insertNewLine($this->getAbsolutePath(), $newLine, $atLine);
    }

    public function getFileName()
    {
        return basename($this->getAbsolutePath());
    }
}
