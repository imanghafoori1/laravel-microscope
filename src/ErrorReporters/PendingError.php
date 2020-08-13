<?php

namespace Imanghafoori\LaravelMicroscope\ErrorReporters;

class PendingError
{
    public static $maxLength = 60;

    private $type;

    private $header;

    private $errorData;

    private $linkPath;

    private $linkLineNumber = 4;

    /**
     * PendingError constructor.
     *
     * @param $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Sets the content of the error header.
     *
     * @param  string  $header
     *
     * @return $this
     */
    public function header(string $header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Sets the data to give out as error.
     *
     * @param $data
     *
     * @return $this
     */
    public function errorData($data)
    {
        $this->setMaxLength(strlen($data));
        $this->errorData = $data;

        return $this;
    }

    /**
     * Sets the link to the source error.
     *
     * @param $path
     * @param  int  $lineNumber
     *
     * @return $this
     */
    public function link($path = null, $lineNumber = 4)
    {
        $this->setMaxLength(strlen($path) - 13);
        $this->linkPath = $path;
        $this->linkLineNumber = $lineNumber;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getErrorData()
    {
        return $this->errorData;
    }

    public function getLinkPath()
    {
        return $this->linkPath;
    }

    public function getLinkLineNumber()
    {
        return $this->linkLineNumber;
    }

    private function setMaxLength($len)
    {
        (self::$maxLength < $len) && self::$maxLength = $len;
        self::$maxLength > 100 && self::$maxLength = 100;
    }
}
