<?php
namespace Rindow\Math\Plot\System;

use RuntimeException;

class Scaling
{
    protected $plotArea;
    protected $dataLimit;
    protected $dataAreaMargin;
    protected $xscaleType;
    protected $yscaleType;

    protected $scaleX;
    protected $offsetX;
    protected $scaleY;
    protected $offsetY;
    protected $xTickLabelAngle;
    protected $yTickLabelAngle;
    protected $tickLabelStandardCount;
    protected $tickLabelWidth;
    protected $tickLabelHeight;

    public function setTickLabelInfo($info)
    {
        [
            $this->xTickLabelAngle,
            $this->yTickLabelAngle,
            $this->tickLabelStandardCount,
            $this->tickLabelWidth,
            $this->tickLabelHeight,
        ] = $info;
    }

    public function plotArea()
    {
        return $this->plotArea;
    }

    public function dataLimit()
    {
        return $this->dataLimit;
    }

    public function setXScaleType($type)
    {
        $this->xscaleType = $type;
    }

    public function setYScaleType($type)
    {
        $this->yscaleType = $type;
    }

    public function xscale()
    {
        return $this->xscaleType;
    }

    public function yscale()
    {
        return $this->yscaleType;
    }

    public function calcScaling(
        array $plotArea,
        array $dataLimit,
        $dataAreaMargin,
        $aspect)
    {
        // scaling
        $this->plotArea = $plotArea;
        $this->dataLimit = $dataLimit;
        $this->dataAreaMargin = $dataAreaMargin;

        [$minX, $minY, $maxX, $maxY] = $dataLimit;
        [$left, $bottom, $width, $height] = $plotArea;

        if($this->xscaleType == 'log') {
            $minX = log10($minX);
            $maxX = log10($maxX);
        }
        if($this->yscaleType == 'log') {
            $minY = log10($minY);
            $maxY = log10($maxY);
        }

        if($aspect=='equal') {
            if($width>$height) {
                $left += (int)(($width - $height)/2);
                $width = $height;
            } elseif($width<$height) {
                $bottom += (int)(($height-$width)/2);
                $height = $width;
            }
        }

        if($maxX==$minX) {
            $scaleX = 1;
            $offsetX = $left/2;
        } else {
            $margin = $width*$dataAreaMargin;
            $scaleX = ($width-$margin*2)/($maxX-$minX);
            $offsetX = $left+$margin-$minX*$scaleX;
        }
        if($maxY==$minY) {
            $scaleY = 1;
            $offsetY = $bottom/2;
        } else {
            $margin = $height*$dataAreaMargin;
            $scaleY = ($height-$margin*2)/($maxY-$minY);
            $offsetY = $bottom+$margin-$minY*$scaleY;
        }

        $this->scaleX = $scaleX;
        $this->offsetX = $offsetX;
        $this->scaleY = $scaleY;
        $this->offsetY = $offsetY;
        //return [$'scaleX,$offsetX,$scaleY,$offsetY];
    }

    public function px($x)
    {
        if($this->xscaleType == 'log') {
            $x = log10($x);
        }
        $px = $this->offsetX + $x * $this->scaleX;
        return (int)round($px);
    }

    public function py($y)
    {
        if($this->yscaleType == 'log') {
            $y =  log10($y);
        }
        $py =  $this->offsetY + $y * $this->scaleY;
        return (int)round($py);
    }

    public function pw($width)
    {
        if($this->xscaleType == 'log') {
            $width = log10($width);
        }
        $pwidth = $width * $this->scaleX;
        return (int)round($pwidth);
    }

    public function ph($height)
    {
        if ($this->yscaleType == 'log') {
            $height = log10($height);
        }
        $pheight = $height * $this->scaleY;
        return (int)round($pheight);
    }

    public function pixels(array $coordinate)
    {
        if(count($coordinate)==2) {
            [$x, $y] = $coordinate;
            return [$this->px($x), $this->py($y)];
        } elseif(count($coordinate)==4) {
            [$x, $y, $w, $h] = $coordinate;
            return [$this->px($x), $this->py($y), $this->pw($w), $this->ph($h)];
        } else {
            throw new InvalidArgumentException('illeagal Coordinate');
        }
    }

    public function calcAutoTicks($which,$font)
    {
        [$minX, $minY, $maxX, $maxY] = $this->dataLimit;
        if($this->xscaleType == 'log') {
            if($minX<0 || $maxX<0)
                throw new RuntimeException('"log" scale cannot be used for negative values.');
            $minX = log10($minX);
            $maxX = log10($maxX);
        }
        if($this->yscaleType == 'log') {
            if($minY<0 || $maxY<0)
                throw new RuntimeException('"log" scale cannot be used for negative values.');
            $minY = log10($minY);
            $maxY = log10($maxY);
        }

        [$left, $bottom, $width, $height] = $this->plotArea;
        if ($which == 'x') {
            $dataMax = $maxX;
            $dataMin = $minX;
            //$length = $width;
            //$labelWidth = $font->width()*5;
            //$wc = 12.4;
            $scaleType = $this->xscaleType;
            $pixScale = $this->scaleX;
            if($this->xTickLabelAngle==0) {
                $labelSpace = $this->tickLabelWidth;
                if($scaleType=='log') {
                    $labelSpace += 3;
                }
            } else {
                $labelSpace = $this->tickLabelHeight;
            }
        } elseif ($which == 'y') {
            $dataMax = $maxY;
            $dataMin = $minY;
            //$length = $height;
            //$labelWidth = $font->width()*5;
            //$wc = 9.125;
            $scaleType = $this->yscaleType;
            $pixScale = $this->scaleY;
            if($this->yTickLabelAngle==0) {
                $labelSpace = $this->tickLabelHeight;
            } else {
                $labelSpace = $this->tickLabelWidth;
                if($scaleType=='log') {
                    $labelSpace += 3;
                }
            }
        } else {
            throw new LogicException('CalcAutoTicks: Invalid usage ('.$which.')');
        }
        $labelWidth = $this->tickLabelStandardCount*$font->width()*$labelSpace;

        $delta = $dataMax - $dataMin;
        $scale = 10**floor(log10($delta));
        $deltaScale = $delta/$scale;

        if(($delta*$pixScale)<$labelWidth) {
            $deltaScale = $deltaScale*$labelWidth/($delta*$pixScale);
        }

        while (abs($deltaScale)<1.0) {
            $deltaScale *= 10.0;
            $scale /= 10.0;
        }
        while (abs($deltaScale)>10.0) {
            $deltaScale /= 10.0;
            $scale *= 10.0;
        }

        if($scaleType=='log') {
            if(abs($scale)<10 && $deltaScale<=6.1) {
                $miniScale=1.0;
            } elseif($deltaScale<=1.6) {
                $miniScale=0.2;
            } elseif($deltaScale>1.6 && $deltaScale<=2.1) {
                $miniScale=0.2;
            } elseif($deltaScale>2.1 && $deltaScale<=4.1) {
                $miniScale=0.5;
            } elseif($deltaScale>4.1 && $deltaScale<=8.1) {
                $miniScale=1.0;
            } else {
                $miniScale=2.0;
            }
        } else {
            if($deltaScale<=1.6) {
                $miniScale=0.2;
            } elseif($deltaScale>1.6 && $deltaScale<=2.1) {
                $miniScale=0.25;
            } elseif($deltaScale>2.1 && $deltaScale<=4.1) {
                $miniScale=0.5;
            } elseif($deltaScale>4.1 && $deltaScale<=8.1) {
                $miniScale=1.0;
            } else {
                $miniScale=2.0;
            }
        }

        $tickStep=$scale*$miniScale;

        // 40 = 0.25 step
        $tickStart = $tickStep*floor(floor(40*$dataMin/$scale)/floor(40*$tickStep/$scale));
        if($dataMin-($delta*$this->dataAreaMargin/2)>$tickStart) {
            $tickStart += $tickStep;
        }
        $tickEnd = $dataMax+($delta*$this->dataAreaMargin/2);
        return [$tickStart, $tickEnd, $tickStep];
    }
}
