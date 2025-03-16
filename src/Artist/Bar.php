<?php
namespace Rindow\Math\Plot\Artist;

use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;

class Bar implements DataArtist
{
    use Configured;

    protected $renderer;
    protected $mo;
    protected $scaling;
    protected $left;
    protected $bottom;
    protected $width;
    protected $height;
    protected $color;
    protected $label;

    // configure
    protected $legendLineWidth = 8;

    public function __construct(
        Configure $config, $renderer, $mo, $scaling,
        NDArray $left, NDArray $bottom, NDArray $width, NDArray $height,
        $color=null,?string $label=null)
    {
        $this->loadConfigure($config,
            ['legendLineWidth'],
            'bar');
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->scaling = $scaling;
        $this->left = $left;
        $this->bottom = $bottom;
        $this->width = $width;
        $this->height = $height;
        $this->color = $color;
        $this->label = $label;
    }

    public function calcDataLimit() : array
    {
        $right = $this->mo->op($this->left,'+',$this->width);
        $top = $this->mo->op($this->bottom,'+',$this->height);
        $minX = min($this->mo->min($this->left),$this->mo->min($right));
        $minY = min($this->mo->min($this->bottom),$this->mo->min($top));
        $maxX = max($this->mo->max($this->left),$this->mo->max($right));
        $maxY = max($this->mo->max($this->bottom),$this->mo->max($top));
        return [$minX,$minY,$maxX,$maxY];
    }

    public function draw(?OverlapChecker $checkOverlap=null)
    {

        $color = $this->renderer->allocateColor($this->color);
        if($checkOverlap) {
            $handle = $checkOverlap->newOverlapCheckHandle([$this,'checkOverlap']);
        }
        $count = $this->left->size();
        for($i=0;$i<$count;$i++) {
            [$px1,$py1] = $this->scaling->pixels([
                $this->left[$i],
                $this->bottom[$i]
            ]);
            [$px2,$py2] = $this->scaling->pixels([
                $this->left[$i]+$this->width[$i],
                $this->bottom[$i]+$this->height[$i]
            ]);

            if($px1>$px2)
                [$px1,$px2] = [$px2,$px1];
            if($py1>$py2)
                [$py1,$py2] = [$py2,$py1];
            if($px1!=$px2)
                $px2--;
            if($py1!=$py2)
                $py2--;
            $this->renderer->filledRectangle($px1,$py1,$px2,$py2,$color);
            if($checkOverlap) {
                $checkOverlap->checkOverlap($handle,[$px1,$py1,$px2,$py2]);
            }
        }
        if($checkOverlap) {
            $checkOverlap->commitOverlap($handle);
        }
    }

    public function checkOverlap($rx1,$ry1,$rx2,$ry2,$data)
    {
        [$px1,$py1,$px2,$py2] = $data;
        if($px2<$rx1 || $rx2<$px1 || $py2<$ry1 || $ry2<$py1) {
            return false;
        }
        return true;
    }

    public function setLabel(?string $label)
    {
        $this->label = $label;
    }

    public function getLabel() : ?string
    {
        return $this->label;
    }

    public function drawLegend($x,$y,$length)
    {
        $color = $this->renderer->allocateColor($this->color);
        $y1 = $y - (int)($this->legendLineWidth/2);
        $this->renderer->filledRectangle($x,$y1,$x+$length-1,$y1+$this->legendLineWidth-1,$color);
    }
}
