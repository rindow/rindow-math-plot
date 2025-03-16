<?php
namespace Rindow\Math\Plot\Artist;

use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;

class Line2D implements DataArtist
{
    use Configured;

    protected $renderer;
    protected $mo;
    protected $scaling;
    protected $x;
    protected $y;
    protected $count;
    protected $marker;
    protected $style;
    protected $label;
    protected $color;
    protected $markerRenderer;

    // configure
    protected $thickness = 2;
    protected $markerSize = 8;

    public function __construct(
        Configure $config, $renderer, $mo, $scaling,
        NDArray $x, NDArray $y,
        ?string $marker=null, ?string $style=null,
        ?string $label=null,$color=null)
    {
        $this->loadConfigure($config,
            ['thickness','markerSize'],
            'line2d');
        if($x->shape()!=$y->shape()||$x->ndim()!=1||$y->ndim()!=1) {
            throw new InvalidArgumentException('x and y must be same count of 1-D');
        }
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->scaling = $scaling;
        $this->x = $x;
        $this->y = $y;
        $this->count = $x->shape()[0];
        $this->marker = $marker;
        $this->style = $style;
        $this->label = $label;
        $this->color = $color;
        $this->markerRenderer = new Marker(null,$renderer);
    }

    public function calcDataLimit() : array
    {
        $minX = $this->mo->min($this->x);
        $minY = $this->mo->min($this->y);
        $maxX = $this->mo->max($this->x);
        $maxY = $this->mo->max($this->y);
        return [$minX,$minY,$maxX,$maxY];
    }

    public function getDataX()
    {
        return $this->x;
    }

    public function getDataY()
    {
        return $this->y;
    }

    public function getLabel() : ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label)
    {
        $this->label = $label;
    }

    public function draw(?OverlapChecker $checkOverlap=null)
    {
        //[$scaleX,$offsetX,$scaleY,$offsetY] = $scaling;
        $origThickness = $this->renderer->getThickness();

        [$px1,$py1] = $this->scaling->pixels([$this->x[0],$this->y[0]]);
        $color = $this->renderer->allocateColor($this->color);
        if($this->marker) {
            $this->renderer->setThickness(1);
            $this->markerRenderer->doDrawDot(
                $px1, $py1, $this->marker, $this->markerSize, $color);
        }
        if($checkOverlap) {
            $handle = $checkOverlap->newOverlapCheckHandle([$this,'checkOverlap']);
        }
        for($i=1;$i<$this->count;$i++) {
            [$px2,$py2] = $this->scaling->pixels([$this->x[$i],$this->y[$i]]);
            $this->renderer->setThickness($this->thickness);
            $this->renderer->line($px1,$py1,$px2,$py2,$color,$this->style);
            if($checkOverlap) {
                $checkOverlap->checkOverlap($handle,[$px1,$py1,$px2,$py2]);
            }
            if($this->marker) {
                $this->renderer->setThickness(1);
                $this->markerRenderer->doDrawDot(
                    $px2, $py2, $this->marker, $this->markerSize, $color);
            }
            $px1 = $px2; $py1 = $py2;
        }
        if($checkOverlap) {
            $checkOverlap->commitOverlap($handle);
        }
        $this->renderer->setThickness($origThickness);
    }

    public function checkOverlap($rx1,$ry1,$rx2,$ry2,$data)
    {
        [$px1,$py1,$px2,$py2] = $data;
        if($rx1<=$px1 && $px1<=$rx2 && $ry1<=$py1 && $py1<=$ry2) {
            return true;
        }
        if($rx1<=$px2 && $px2<=$rx2 && $ry1<=$py2 && $py2<=$ry2) {
            return true;
        }
        [$tx1,$tx2] = ($px1<=$px2) ? [$px1,$px2] : [$px2,$px1];
        [$ty1,$ty2] = ($py1<=$py2) ? [$py1,$py2] : [$py2,$py1];
        if($rx1<=$px1 && $px1<=$rx2 && $rx1<=$px2 && $px2<=$rx2 &&
            $ty1<=$ry1 && $ry2<=$ty2) {
            return true;
        }
        if($ry1<=$py1 && $py1<=$ry2 && $ry1<=$py2 && $py2<=$ry2 &&
            $tx1<=$rx1 && $rx2<=$tx2) {
            return true;
        }
        return false;
    }

    public function drawLegend($x,$y,$length)
    {
        $color = $this->renderer->allocateColor($this->color);
        $origThickness = $this->renderer->getThickness();
        $this->renderer->setThickness($this->thickness);
        $this->renderer->line($x,$y,$x+$length-1,$y,$color,$this->style);
        if($this->marker) {
            $this->renderer->setThickness(1);
            $this->markerRenderer->doDrawDot(
                $x+(int)($length/2), $y,
                $this->marker, $this->markerSize, $color);
        }
        $this->renderer->setThickness($origThickness);
    }
}
