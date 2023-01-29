<?php
namespace Rindow\Math\Plot\Artist;

use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;
use InvalidArgumentException;

class Colorbar implements DataArtist
{
    protected $config;
    protected $renderer;
    protected $mo;
    protected $scaling;
    protected $bottom;
    protected $top;
    protected $cmap;

    public function __construct(
        Configure $config, $renderer, $mo, $scaling,
        $cmap, float $bottom, float $top)
    {
        $this->config = $config;
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->scaling = $scaling;
        $this->bottom = $bottom;
        $this->top = $top;
        $this->cmap = $cmap;
    }

    public function calcDataLimit() : array
    {
        $minX = 0.0;
        $maxX = 1.0;
        $minY = $this->bottom;
        $maxY = $this->top;
        if($minY==$maxY) {
            if($maxY==0) {
                $minY = 0.0;
                $maxY = 1.0;
            } else {
                $minY = $maxY-$maxY*0.05;
                $maxY = $maxY+$maxY*0.05;
            }
        }
        return [$minX,$minY,$maxX,$maxY];
    }

    public function draw(OverlapChecker $checkOverlap=null)
    {
        $n = 256;
        $px1 = $this->scaling->px(0.0);
        $px2 = $this->scaling->px(1.0);
        [$minX,$minY,$maxX,$maxY] = $this->calcDataLimit();
        if($minY==$maxY) {
            $scaleY = 0;
        } else {
            $scaleY = ($maxY-$minY)/$n;
        }
        for($i=0;$i<255;$i++) {
            $color = $this->cmap->sRGB24Bit($this->cmap->interpolateOrClip($i/256));
            $py1 = $this->scaling->py(($i)*$scaleY+$minY);
            $py2 = $this->scaling->py(($i+1)*$scaleY+$minY);
            $color = $this->renderer->allocateColor($color);
            $this->renderer->filledrectangle($px1,$py1,$px2,$py2,$color);
        }
    }

    public function drawLegend($x,$y,$length)
    {
    }
    public function getLabel() : ?string
    {
        return null;
    }
    public function setLabel(?string $label)
    {
    }
}
