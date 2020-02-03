<?php
namespace Rindow\Math\Plot\Renderer;

class GDFont
{
    protected $fontSize;
    protected $ttf;
    protected $height;
    protected $width;

    public function __construct($fontSize,$ttf=null)
    {
        $this->fontSize = $fontSize;
        $this->ttf = $ttf;
    }

    public function height()
    {
        if($this->height!==null) {
            return $this->height;
        }
        if(!$this->ttf) {
            $this->height = imagefontheight($this->fontSize);
        }
        return $this->height;
    }

    public function width()
    {
        if($this->width!==null) {
            return $this->width;
        }
        if(!$this->ttf) {
            $this->width = imagefontwidth($this->fontSize);
        }
        return $this->width;
    }

    public function getFontNumber()
    {
        return $this->fontSize;
    }
}
