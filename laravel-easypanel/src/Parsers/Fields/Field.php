<?php


namespace EasyPanel\Parsers\Fields;


use EasyPanel\Concerns\Translatable;

class Field
{
    use Translatable;

    protected $title;
    protected $model;
    protected $key;
    protected $style;
    protected $alt;
    protected $badgeType = 'info';
    protected $dataStub = 'text.stub';
    protected $headStub = 'sortable.stub';
    protected $target = 'text.stub';
    protected $height = 50;
    protected $width = 50;
    protected $trueColor = 'success';
    protected $trueValue = 'True !';
    protected $falseColor = 'danger';
    protected $falseValue = 'False !';

    public function __construct($title)
    {
        $this->title = $title;
        $this->addText($title);
    }

    public static function title($title)
    {
        return new static($title);
    }

    public function style($style)
    {
        $this->style = $style;

        return $this;
    }

    public function asImage()
    {
        if($this->dataStub != 'linked-image.stub'){
            $this->dataStub = 'image.stub';
        }

        return $this;
    }

    public function clickableImage($target = '_blank')
    {
        $this->dataStub = 'linked-image.stub';
        $this->target = $target;

        return $this;
    }

    public function asBadge()
    {
        $this->dataStub = 'badge.stub';

        return $this;
    }

    public function asBooleanBadge($trueColor = 'success', $falseColor = 'danger')
    {
        $this->dataStub = 'bool-badge.stub';
        $this->trueColor($trueColor);
        $this->falseColor($falseColor);

        return $this;
    }

    public function trueColor($color)
    {
        $this->trueColor = $color;

        return $this;
    }

    public function falseColor($color)
    {
        $this->falseColor = $color;

        return $this;
    }

    public function trueValue($value)
    {
        $this->trueValue = $value;
        $this->addText($value);

        return $this;
    }

    public function falseValue($value)
    {
        $this->falseValue = $value;
        $this->addText($value);

        return $this;
    }

    public function roundedImage()
    {
        $this->style .= " rounded-circle ";

        return $this;
    }

    public function alt($alt)
    {
        $this->alt = $alt;
        $this->addText($alt);

        return $this;
    }

    public function height($height)
    {
        $this->height = $height;

        return $this;
    }

    public function width($width)
    {
        $this->height = $width;

        return $this;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function badgeType($type)
    {
        $this->badgeType = $type;

        return $this;
    }

    public function roundedBadge()
    {
        $this->style .= ' badge-pill ';

        return $this;
    }

    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    public function setModel($model)
    {
        $this->model = $model;

        return $this;
    }

    public function renderTitle()
    {
        $stubContent = $this->getTitleStubContent();

        $array = [
            '{{ key }}' => $this->key,
            '{{ title }}' => $this->title
        ];

        return str_replace(array_keys($array), array_values($array), $stubContent);
    }

    public function withoutSorting()
    {
        $this->headStub = 'not-sortable.stub';

        return $this;
    }

    public function renderData()
    {
        $stubContent = $this->getDataStubContent();

        $array = [
            '{{ key }}' => $this->parseRelationalKey($this->key),
            '{{ model }}' => $this->model,
            '{{ height }}' => $this->height,
            '{{ width }}' => $this->width,
            '{{ style }}' => $this->style,
            '{{ alt }}' => $this->alt,
            '{{ badgeType }}' => $this->badgeType,
            '{{ target }}' => $this->target,
            '{{ trueColor }}' => $this->trueColor,
            '{{ trueValue }}' => $this->trueValue,
            '{{ falseColor }}' => $this->falseColor,
            '{{ falseValue }}' => $this->falseValue,
        ];

        $this->translate();

        return str_replace(array_keys($array), array_values($array), $stubContent);
    }

    private function getDataStubContent()
    {
        return file_get_contents(__DIR__.'/stubs/'.$this->dataStub);
    }

    private function getTitleStubContent()
    {
        if ($this->isRelational()){
            return file_get_contents(__DIR__.'/stubs/titles/not-sortable.stub');
        }

        return file_get_contents(__DIR__.'/stubs/titles/'.$this->headStub);
    }

    private function isRelational()
    {
        return \Str::contains($this->key, '.');
    }

    private function parseRelationalKey($key){
        return str_replace('.', '->', $key);
    }

}
