<?php
namespace Rindow\Math\Plot\Artist;

use ArrayObject;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;
use DomainException;

class Legend implements OverlapChecker
{
    use Configured;

    protected $renderer;
    protected $labels;
    protected $handles;
    protected $areas;
    protected $overlapedCounter;
    protected $font;

    // configure
    protected $lineSpacing = 3;
    protected $colorBoxWidth = 20;
    protected $legendMargin = 6;
    protected $fontSize = 4;

    public function __construct(Configure $config, $renderer, array $handles,array $labels)
    {
        $this->loadConfigure($config,
            ['lineSpacing','colorBoxWidth','legendMargin','fontSize'],
            'legend');
        $this->renderer = $renderer;
        $this->handles = $handles;
        $this->labels = $labels;
    }

    public function calcAreas($plotArea)
    {
        $maxLabelWidth = $maxLabelHeight = 0;
        $this->font = $this->renderer->allocateFont($this->fontSize);
        foreach($this->labels as $label) {
            $rectangle = $this->renderer->textSize($this->font,0,0,$label,0,'left','bottom');
            [$x,$y,$width,$height] = $rectangle;
            $maxLabelWidth  = max($maxLabelWidth,$width);
            $maxLabelHeight = max($maxLabelHeight,$height);
        }
        $count = count($this->labels);
        $width = $this->colorBoxWidth + $maxLabelWidth + $this->legendMargin*3;
        $height = $maxLabelHeight*$count+
            $this->lineSpacing*($count-1) + $this->legendMargin*2;

        [$plotAreaLeft,$plotAreaBottom,$plotAreaWidth,$plotAreaHeight] = $plotArea;
        $plotAreaRight = $plotAreaLeft+$plotAreaWidth-1-$this->legendMargin;
        $plotAreaTop = $plotAreaBottom+$plotAreaHeight-1-$this->legendMargin;
        $plotAreaLeft = $plotAreaLeft+$this->legendMargin;
        $plotAreaBottom = $plotAreaBottom+$this->legendMargin;
        // area 0 [right,top]
        $this->areas[] = [
            $plotAreaRight-$width,$plotAreaTop-$height,
            $plotAreaRight,$plotAreaTop
        ];
        // area 1 [left,top]
        $this->areas[] = [
            $plotAreaLeft,$plotAreaTop-$height,
            $plotAreaLeft+$width,$plotAreaTop
        ];
        // area 2 [right,bottom]
        $this->areas[] = [
            $plotAreaRight-$width,$plotAreaBottom,
            $plotAreaRight,$plotAreaBottom+$height
        ];
        // area 3 [left,bottom]
        $this->areas[] = [
            $plotAreaLeft,$plotAreaBottom,
            $plotAreaLeft+$width,$plotAreaBottom+$height
        ];
        // area 4 [center,top]
        $this->areas[] = [
            $plotAreaLeft+(int)(($plotAreaWidth-$width)/2),$plotAreaTop-$height,
            $plotAreaLeft+(int)(($plotAreaWidth+$width)/2),$plotAreaTop
        ];
        // area 5 [right,center]
        $this->areas[] = [
            $plotAreaRight-$width,$plotAreaBottom+(int)(($plotAreaHeight-$height)/2),
            $plotAreaRight,$plotAreaBottom+(int)(($plotAreaHeight+$height)/2)
        ];
        // area 6 [left,center]
        $this->areas[] = [
            $plotAreaLeft,$plotAreaBottom+(int)(($plotAreaHeight-$height)/2),
            $plotAreaLeft+$width,$plotAreaBottom+(int)(($plotAreaHeight+$height)/2)
        ];
        // area 7 [center,bottom]
        $this->areas[] = [
            $plotAreaLeft+(int)(($plotAreaWidth-$width)/2),$plotAreaBottom,
            $plotAreaLeft+(int)(($plotAreaWidth+$width)/2),$plotAreaBottom+$height
        ];

        $this->overlapedCounter = array_fill(0,count($this->areas),0);
    }

    public function newOverlapCheckHandle(callable $function) : object
    {
        $handle = new ArrayObject();
        $handle['fn'] = $function;
        foreach($this->areas as $key => $area) {
            $handle['a'.$key] = false;
        }
        return $handle;
    }

    public function checkOverlap($handle,$data) : void
    {
        $checker = $handle['fn'];
        foreach ($this->areas as $key => $area) {
            if($handle['a'.$key]) {
                continue;
            }
            [$rx1,$xy1,$rx2,$ry2] = $area;
            if($checker($rx1,$xy1,$rx2,$ry2,$data)) {
                $handle['a'.$key] = true;
            }
        }
    }

    public function commitOverlap($handle) : void
    {
        foreach($this->areas as $key => $area) {
            if($handle['a'.$key]) {
                $this->overlapedCounter[$key]++;
            }
        }
    }

    public function draw()
    {
        //foreach ($this->areas as $key => $area) {
        //    [$x1,$y1,$x2,$y2] = $area;
        //    $color = $renderer->allocateColor('gray');
        //    $renderer->rectangle($x1,$y1,$x2,$y2,$color);
        //    $renderer->rectangle($x1-1,$y1-1,$x1+1,$y1+1);
        //    $color = $renderer->allocateColor('black');
        //    $renderer->text($font,$x1+5,$y1+5,strval($key),$color);
        //}
        $min = min($this->overlapedCounter);
        foreach ($this->overlapedCounter as $key => $value) {
            if($value==$min)
                break;
        }
        [$txtx,$txty,$txtWidth,$txtHeight] = $this->renderer->textSize($this->font,0,0,'M');
        [$x1,$y1,$x2,$y2] = $this->areas[$key];
        $color = $this->renderer->allocateColor('gray');
        $this->renderer->rectangle($x1,$y1,$x2,$y2,$color);
        foreach($this->handles as $key => $handle) {
            if(!($handle instanceof DataArtist)) {
                throw new DomainException('handles must be array of DataArtist');
            }
            $ypos = $y2 - (int)(($txtHeight+$this->lineSpacing)*(0.5+$key))
                    - $this->legendMargin;
            $xpos = $x1+$this->legendMargin;
            $handle->drawLegend($xpos,$ypos,$this->colorBoxWidth);
            $label = $this->labels[$key];
            $this->renderer->text($this->font,
                $xpos+$this->colorBoxWidth+$this->legendMargin,
                $ypos,$label,0,null,'left','center');
        }
    }
}
