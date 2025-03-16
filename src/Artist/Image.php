<?php
namespace Rindow\Math\Plot\Artist;

use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;
use InvalidArgumentException;
use RuntimeException;

class Image implements DataArtist,Mappable
{
    protected $config;
    protected $renderer;
    protected $mo;
    protected $scaling;
    protected $data;
    protected $cmap;
    protected $norm;
    protected $extent;
    protected $originUpper;

    public function __construct(
        Configure $config, $renderer, $mo, $scaling,
        NDArray $data,$cmap,
        ?array $norm=null,
        ?array $extent=null,
        ?string $origin=null)
    {
        if($data->ndim()<2 || $data->ndim()>3)
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
        $this->norm = $norm;
        $this->extent = $extent;
        $this->originUpper = ($origin=='upper')? true : false ;
    }

    public function calcDataLimit() : array
    {
        if($this->extent===null) {
            $shape = $this->data->shape();
            $minX = -0.5;
            $minY = -0.5;
            $maxX = $shape[1]-0.5;
            $maxY = $shape[0]-0.5;
        } else {
            if(count($this->extent)!=4) {
                throw new RuntimeException('Extent must be [xmin,xmax,ymin,ymax].');
            }
            // *** CAUTION ***
            // matplotlib compatible
            [$minX,$maxX,$minY,$maxY] = $this->extent;
        }
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

    public function colormap()
    {
        return $this->cmap;
    }

    public function colorRange() : array
    {
        if($this->data->ndim()!=2) {
            throw new RuntimeException('Does not support color maps.');
        }
        if($this->norm===null) {
            $minC = $this->mo->min($this->data);
            $maxC = $this->mo->max($this->data);
        } else {
            if(count($this->norm)!=2) {
                throw new RuntimeException('Normalize must be [minimum,maximum].');
            }
            [$minC,$maxC] = $this->norm;
            if($minC>$maxC) {
                [$minC,$maxC] = [$maxC,$minC];
            }
        }
        return [$minC,$maxC];
    }

    public function draw(?OverlapChecker $checkOverlap=null)
    {
        $shape = $this->data->shape();
        $yCount = $shape[0];
        $xCount = $shape[1];
        $dtype = $this->stringType($this->data->dtype());
        if(isset($shape[2])) {
            $colorMode = 'rgb';
            if($shape[2]==4)
                $colorMode = 'rgba';
        } else {
            $colorMode = 'cmap';
            [$minC,$maxC] = $this->colorRange();
            if($minC==$maxC) {
                $scaleC = 0;
            } else {
                $scaleC = 1/($maxC-$minC);
            }
            $offsetC = - $minC;
        }
        if($this->extent===null) {
            $deltaX = 1;
            $deltaY = 1;
            $minY = -0.5;
            $minX = -0.5;
            $maxY = $yCount+0.5;
            $maxX = $xCount+0.5;
        } else {
            [$minX,$minY,$maxX,$maxY] = $this->calcDataLimit();
            $deltaX = ($maxX-$minX)/$xCount;
            $deltaY = ($maxY-$minY)/$yCount;
        }
        for($m=0;$m<$yCount;$m++) {
            if(!$this->originUpper){
                $py1 = $this->scaling->py($minY+$m*$deltaY);
                $py2 = $this->scaling->py($minY+($m+1)*$deltaY);
            } else {
                $py1 = $this->scaling->py($maxY-($m+1)*$deltaY);
                $py2 = $this->scaling->py($maxY-($m+2)*$deltaY);
            }
            for($n=0;$n<$xCount;$n++) {
                $value = $this->data[$m][$n];
                if($colorMode=='cmap') {
                    $value = $scaleC*$value+$scaleC*$offsetC;
                    $color = $this->cmap->sRGB24Bit($this->cmap->interpolateOrClip($value));
                } else {
                    $r = $value[0];
                    $g = $value[1];
                    $b = $value[2];
                    $a = ($colorMode=='rgba') ? $value[3] : 0;
                    if($dtype=='float') {
                        $color = $this->cmap->sRGB24Bit([$r,$g,$b]);
                        if($colorMode=='rgba') {
                            $color[] = (int)floor($a*255);
                        }
                    } else {
                        $color = ($colorMode=='rgba') ? [$r,$g,$b,$a] : [$r,$g,$b];
                    }
                }
                $color = $this->renderer->allocateColor($color);
                #$px1 = $this->scaling->px($n-0.5);
                #$px2 = $this->scaling->px($n+0.5);
                $px1 = $this->scaling->px($minX+$n*$deltaX);
                $px2 = $this->scaling->px($minX+($n+1)*$deltaX);
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
