<?php

namespace Imanghafoori\LaravelMicroscope\PendingObjects;

class PendingError
{
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
        $this->errorData = $data;

        return $this;
    }

    /**
     * Sets the link to the source error.
     * @param $path
     * @param  int  $linenumber
     *
     * @return $this
     */
    public function link($path, $linenumber = 4)
    {
        $this->linkPath = $path;
        $this->linkLineNumber = $linenumber;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @return mixed
     */
    public function getErrorData()
    {
        return $this->errorData;
    }

    /**
     * @return mixed
     */
    public function getLinkPath()
    {
        return $this->linkPath;
    }

    /**
     * @return int
     */
    public function getLinkLineNumber(): int
    {
        return $this->linkLineNumber;
    }
}
