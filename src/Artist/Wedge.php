<?php
namespace Rindow\Math\Plot\Artist;

use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;

class Wedge implements DataArtist
{
    use Configured;

    protected $renderer;
    protected $mo;
    protected $scaling;
    protected $center;
    protected $radius;
    protected $angle1;
    protected $angle2;
    protected $color;
    protected $label;
    protected $pctText;
    protected $explode;

    // configure
    protected $labelDistance = 1.1;
    protected $labelColor = 'black';
    protected $pctDistance = 0.6;
    protected $pctColor = 'black';
    protected $fontSize = 4;
    protected $legendLineWidth = 8;

    public function __construct(
        Configure $config, $renderer, $mo, $scaling,
        $center, $radius, $start, $end,
        $color=null,
        string $label=null, string $pctText=null,
        $explode=null)
    {
        $this->loadConfigure($config,
            ['labelDistance','labelColor','pctDistance','pctColor',
             'fontSize','legendLineWidth'],
            'wedge');
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->scaling = $scaling;
        $this->center = $center;
        $this->radius = $radius;
        $this->angle1 = $start;
        $this->angle2 = $end;
        $this->color = $color;
        $this->label = $label;
        $this->pctText = $pctText;
        $this->explode = $explode;
    }

    public function calcDataLimit() : array
    {
        return [-1.0,-1.0,1.0,1.0];
    }

    public function draw(OverlapChecker $checkOverlap=null)
    {
        [$x,$y] = $this->center;
        if($this->explode) {
            $theta=($this->angle2+$this->angle1)*pi()/360;
            $x += $this->explode*cos($theta);
            $y += $this->explode*sin($theta);
        }
        [$px,$py,$width,$height] = $this->scaling->pixels(
            [$x,$y,$this->radius*2,$this->radius*2]);
        $angle1 = (int)(360-$this->angle2);
        $angle2 = (int)(360-$this->angle1);
        $color = $this->renderer->allocateColor($this->color);
        $this->renderer->filledArc($px, $py, $width, $height, $angle1, $angle2, $color);

        $explode = ($this->explode) ?: 0;
        if($this->label) {
            $this->drawText(
                $this->fontSize,$this->labelColor,
                $this->angle1,$this->angle2,$this->labelDistance+$explode,
                $this->label
            );
        }
        if($this->pctText) {
            $this->drawText(
                $this->fontSize,$this->pctColor,
                $this->angle1,$this->angle2,$this->pctDistance+$explode,
                $this->pctText,'center','center'
            );
        }
    }

    protected function drawText(
        $fontSize,$textColor,
        $angle1,$angle2,$distance,$text,$halign=null,$valign=null)
    {
        $font = $this->renderer->allocateFont($fontSize);
        $color = $this->renderer->allocateColor($textColor);
        $theta=($angle2+$angle1)*pi()/360;
        $tx = $distance*cos($theta);
        $ty = $distance*sin($theta);
        if($halign==null) {
            if($tx<-0.1) {
                $halign = 'right';
            } elseif($tx>=0.1) {
                $halign = 'left';
            } else {
                $halign = 'center';
            }
        }
        if($valign==null) {
            if($ty<-0.1) {
                $valign = 'top';
            } elseif($ty>=0.1) {
                $valign = 'bottom';
            } else {
                $valign = 'center';
            }
        }
        [$px,$py] = $this->scaling->pixels([$tx,$ty]);
        $this->renderer->text($font,$px,$py,$text,
                        $color,null,$halign,$valign);
    }

    public function drawLegend($x,$y,$length)
    {
        $color = $this->renderer->allocateColor($this->color);
        $y1 = $y - (int)($this->legendLineWidth/2);
        $this->renderer->filledRectangle($x,$y1,$x+$length-1,$y1+$this->legendLineWidth-1,$color);
    }

    public function getLabel() : ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label)
    {
        $this->label = $label;
    }
}
