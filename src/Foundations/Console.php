<?php

namespace Imanghafoori\LaravelMicroscope\Foundations;

use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class Console
{
    public static $warned = [];

    public static $askedConfirmations = [];

    public static $writes = [];

    public static $fakeAnswers = [];

    public static $forcedAnswer;

    /**
     * @var SymfonyStyle
     */
    public static $instance;

    public static function confirm(string $question, bool $default = true)
    {
        if (self::$forcedAnswer !== null) {
            self::$askedConfirmations[] = $question;

            return self::$forcedAnswer;
        }

        if (isset(self::$fakeAnswers[$question])) {
            self::$askedConfirmations[] = $question;

            return self::$fakeAnswers[$question];
        }

        return self::getInstance()->confirm($question, $default);
    }

    public static function warn($msg)
    {
        self::$warned[]  = $msg;

        (self::$instance)->warning($msg);
    }

    public static function fakeAnswer(string $question, bool $default = true)
    {
        self::$fakeAnswers[$question] = $default;
    }

    public static function enforceTrue()
    {
        self::$forcedAnswer = true;
    }

    private static function askUserObject(): SymfonyStyle
    {
        return new SymfonyStyle(new ArgvInput, new ConsoleOutput);
    }

    public static function reset()
    {
        self::$warned = [];
        self::$askedConfirmations = [];
        self::$fakeAnswers = [];
        self::$instance = self::$forcedAnswer = null;
    }

    public static function writeln($messages)
    {
        if (! self::$instance) {
            self::$instance = self::askUserObject();
        }


        (self::$instance)->writeln($messages);
    }

    /**
     * @return \Symfony\Component\Console\Style\SymfonyStyle
     */
    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = self::askUserObject();
        }

        return self::$instance;
    }
}