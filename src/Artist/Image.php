<?php
namespace Rindow\Math\Plot\Artist;

use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;
use InvalidArgumentException;

class Image implements DataArtist
{
    protected $config;
    protected $renderer;
    protected $mo;
    protected $scaling;
    protected $data;
    protected $norm = true;

    public function __construct(
        Configure $config, $renderer, $mo, $scaling,
        NDArray $data,$cmap)
    {
        if($data->ndim()<2 && $data->ndim()>3)
            throw new InvalidArgumentException('image data must be 2-D or 3-D shape NDArray.');
        if($data->ndim()==3) {
            $shape = $data->shape();
            if($shape[2]!=3 && $shape[2]!=4)
                throw new InvalidArgumentException('color code must be cmap value or rgb or rgba NDArray.');
        }

        $this->config = $config;
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->scaling = $scaling;
        $this->data = $data;
        $this->cmap = $cmap;
    }

    public function calcDataLimit() : array
    {
        $shape = $this->data->shape();
        $minX = -0.5;
        $minY = -0.5;
        $maxX = $shape[1]-0.5;
        $maxY = $shape[0]-0.5;
        return [$minX,$minY,$maxX,$maxY];
    }

    protected function stringType($dtype)
    {
        switch($dtype) {
            case NDArray::bool:
                return 'bool';
            case NDArray::int8:
            case NDArray::int16:
            case NDArray::int32:
            case NDArray::int64:
            case NDArray::uint8:
            case NDArray::uint16:
            case NDArray::uint32:
            case NDArray::uint64:
                return 'int';
            case NDArray::float8:
            case NDArray::float16:
            case NDArray::float32:
            case NDArray::float64:
                return 'float';
            default:
                return 'unknown';
        }
    }

    public function draw(OverlapChecker $checkOverlap=null)
    {
        $shape = $this->data->shape();
        $yCount = $shape[0];
        $xCount = $shape[1];
        $dtype = $this->stringType($this->data->dtype());
        if(isset($shape[2])) {
            $colorMode = 'rgb';
            if($shape[3]==4)
                $colorMode = 'rgba';
        } else {
            $colorMode = 'cmap';
            $minC = - $this->mo->min($this->data);
            $maxC = $this->mo->max($this->data);
            if($minC==$maxC) {
                $scaleC = 0;
            } else {
                $scaleC = 1/($maxC-$minC);
            }
            $offsetC = - $minC;
        }
        for($m=0;$m<$yCount;$m++) {
            $py1 = $this->scaling->py($m-0.5);
            $py2 = $this->scaling->py($m+0.5);
            for($n=0;$n<$yCount;$n++) {
                $value = $this->data[$m][$n];
                if($colorMode=='cmap') {
                    if($this->norm) {
                        $value = $scaleC*($value+$offsetC);
//echo '('.$value.')';
                    }
                    $color = $this->cmap->sRGB24Bit($this->cmap->interpolateOrClip($value));
                } else {
                    $r = $value[0];
                    $g = $value[1];
                    $b = $value[2];
                    $a = ($colorMode=='rgba') ? $value[4] : 0;
                    if($dtype=='float') {
                        $color = $this->cmap->sRGB24Bit([$r,$g,$b]);
                        if($colorMode=='rgba') {
                            $color[] = (int)floor($a*255);
                        }
                    } else {
                        $color = ($colorMode=='rgba') ? [$r,$g,$b,$a] : [$r,$g,$b];
                    }
                }
//echo '('.implode(',',$color).')';
                $color = $this->renderer->allocateColor($color);
                $px1 = $this->scaling->px($n-0.5);
                $px2 = $this->scaling->px($n+0.5);
                $this->renderer->filledrectangle($px1,$py1,$px2,$py2,$color);
            }
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
