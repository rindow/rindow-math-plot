<?php
namespace Rindow\Math\Plot\System;

use ArrayObject;
use InvalidArgumentException;

class Colormap
{
    protected $colormap;

    public function __construct(string $name)
    {
        $filename = __DIR__.'/Colormap/'.$name.'.php';
        if(!file_exists($filename))
            throw new InvalidArgumentException('colormap file not found.');
        $this->colormap = require $filename;
    }

    public function interpolate($x)
    {
        $x = max(0.0, min(1.0, $x));
        $a = (int)floor($x*255);
        $b = min(255, $a + 1);
        $f = $x*255 - $a;
        return [
            $this->colormap[$a][0] + ($this->colormap[$b][0] - $this->colormap[$a][0]) * $f,
            $this->colormap[$a][1] + ($this->colormap[$b][1] - $this->colormap[$a][1]) * $f,
            $this->colormap[$a][2] + ($this->colormap[$b][2] - $this->colormap[$a][2]) * $f];
    }

    public function interpolateOrClip($x)
    {
        if($x < 0.0)
            return [0.0, 0.0, 0.0];
        elseif($x > 1.0)
            return [1.0, 1.0, 1.0];
        else
            return $this->interpolate($x);
    }

    public function sRGB24Bit($color)
    {
        [$r,$g,$b] = $color;
        $r = (int)floor($r*255);
        $g = (int)floor($g*255);
        $b = (int)floor($b*255);
        return [$r,$g,$b];
    }

    public function getMapData()
    {
        return $this->colormap;
    }
}
