<?php
namespace Rindow\Math\Plot\System;

use ArrayObject;
use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\Artist\Line2D;
use Rindow\Math\Plot\Artist\Marker;
use Rindow\Math\Plot\Artist\Bar;
use Rindow\Math\Plot\Artist\Wedge;
use Rindow\Math\Plot\Artist\Legend;
use Rindow\Math\Plot\Artist\BoxFrame;
use Rindow\Math\Plot\Artist\XAxisLabel;
use Rindow\Math\Plot\Artist\YAxisLabel;
use Rindow\Math\Plot\Artist\Title;
use Rindow\Math\Plot\Artist\Image;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;

use LogicException;
use InvalidArgumentException;

class Axes
{
    use Configured;

    protected $config;
    protected $renderer;
    protected $mo;
    protected $plotArea; // [left, bottom, width, height]
    protected $scaling;
    protected $artists;
    protected $legend;
    protected $axis;
    protected $xTicks;
    protected $xTickLabels;
    protected $yTicks;
    protected $yTickLabels;
    protected $xLabel;
    protected $yLabel;
    protected $title;
    protected $currentPlotColorNumber = 0;

    protected $defaultColors = [       // The default colors for data and error bars
        'RoyalBlue', 'orange', 'SeaGreen', 'red', 'purple', 'brown', 'salmon', 'SlateGray',
        'YellowGreen', 'aquamarine1', 'SlateBlue', 'peru', 'PaleGreen', 'magenta', 'gold', 'violet'];
    protected $lineStyleCharactor = [
        '--' => '-',
        '-.' => '-.',
        ':' => '.',
    ];
    protected $markerCharactor = [
        ',' => 'smalldot',
        '@' => 'pixel',
        'o' => 'dot',
        '^' => 'yield',
        'v' => 'delta',
        '1' => 'down',
        '2' => 'up',
        '3' => 'triangle',
        '4' => 'trianglemid',
        's' => 'square',
        'h' => 'home',
        '*' => 'star',
        'u' => 'hourglass',/**/
        'e' => 'bowtie',/**/
        't' => 'target',/**/
        'H' => 'halfline',
        'B' => 'box',
        'O' => 'circle',
        '+' => 'plus',
        'x' => 'cross',
        'D' => 'diamond',
        '|' => 'vertical',
        '_' => 'line',
    ];
    protected $colorCharactor = [
        'b' => 'blue',
        'g' => 'green',
        'r' => 'red',
        'c' => 'cyan',
        'm' => 'cyan',
        'y' => 'yellow',
        'k' => 'black',
        'w' => 'white',
    ];

    // configure
    protected $frame = true;
    protected $barWidth = 0.8;
    protected $dataAreaMargin = 0.05;

    public function __construct(
        Configure $config,$renderer,$mo,$cmapManager,
        array $plotArea)
    {
        $this->loadConfigure($config,
            ['frame','bar.barWidth']);
        $this->config = $config;
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->cmapManager = $cmapManager;
        $this->plotArea = $plotArea;
        $this->artists = new ArrayObject();

        [$x,$y,$width,$height] = $plotArea;
        if($width<=0) {
            throw new InvalidArgumentException('Not enough the plot area width');
        }
        if($height<=0) {
            throw new InvalidArgumentException('Not enough the plot area height');
        }
        $this->scaling = $this->newScaling();
    }

    protected function newScaling()
    {
        return new Scaling();
    }

    protected function newBar($left,$bottom,$width,$height,$color,$label)
    {
        return new Bar($this->config,$this->renderer,$this->mo,$this->scaling,
            $left,$bottom,$width,$height,$color,$label);
    }

    protected function newLegend($handles,$labels)
    {
        return new Legend($this->config,$this->renderer,$this->plotArea,
            $handles,$labels);
    }

    protected function newLine2D($x,$y,$markerString,$lineStyleString,$label,$color)
    {
        return new Line2D($this->config,$this->renderer,$this->mo,$this->scaling,
            $x,$y,$markerString,$lineStyleString,$label,$color);
    }

    protected function newMarker($x,$y,$size,$color,$marker,$label)
    {
        return new Marker($this->config,$this->renderer,$this->mo,$this->scaling,
            $x,$y,$size,$color,$marker,$label);
    }

    protected function newWedge($center, $radius, $start, $end,$color,$label,$pctText,$explode)
    {
        return new Wedge($this->config,$this->renderer,$this->mo,$this->scaling,
            $center, $radius, $start, $end,$color,$label,$pctText,$explode);
    }

    protected function newXAxisLabel($label)
    {
        return new XAxisLabel($this->config,$this->renderer,
            $this->plotArea,$label);
    }

    protected function newYAxisLabel($label)
    {
        return new YAxisLabel($this->config,$this->renderer,
            $this->plotArea,$label);
    }

    protected function newTitle($label)
    {
        return new Title($this->config,$this->renderer,
            $this->plotArea,$label);
    }

    public function newBoxFrame()
    {
        return new BoxFrame($this->config,$this->renderer,
            $this->plotArea,$this->scaling);
    }

    protected function newImage($data,$cmap)
    {
        return new Image($this->config,$this->renderer,$this->mo,
            $this->scaling,$data,$cmap);
    }

    public function bar(
        $x,
        NDArray $height,
        $width=null,
        $bottom=null,
        string $label=null,
        string $style=null)
    {
        if(is_array($x)) {
            $this->setXTickLabels(array_values($x));
            $x = $this->mo->arange(count($x));
            $this->setXticks($x);
        } elseif(!($x instanceof NDArray)) {
            throw new InvalidArgumentException('first argument must be array or NDArray.');
        }
        if($x->ndim()!=1) {
            throw new InvalidArgumentException('first argument must be shape of 1-D.');
        }
        if($height->ndim()>2) {
            throw new InvalidArgumentException('secound argument must be shape of 1-D or 2-D');
        }
        if($x->shape()[0]!=$height->shape()[0]) {
            throw new InvalidArgumentException('Shape of x and height must be same');
        }
        if($height->ndim()==1) {
            $height = $height->reshape([1,$height->size()]);
        } else {
            $height = $this->mo->transpose($height);
        }

        if($width===null) {
            $width = $this->barWidth;
        } elseif(!is_numeric($width)&&!($width instanceof NDArray)) {
            throw new InvalidArgumentException('the width must be numeric or 1-D NDArray.');
        } elseif(($width instanceof NDArray) && $width->ndim()!=1) {
            throw new InvalidArgumentException('the width must be numeric or 1-D NDArray.');
        }
        if(!($width instanceof NDArray)) {
            $width = $this->mo->full($x->shape(),$width,NDArray::float32);
        }
        if($bottom===null) {
            $bottom = 0;
        } elseif(!is_numeric($bottom)&&!($bottom instanceof NDArray)) {
            throw new InvalidArgumentException('the bottom must be numeric or 1-D NDArray.');
        } elseif(($bottom instanceof NDArray) && $bottom->ndim()!=1) {
            throw new InvalidArgumentException('the bottom must be numeric or 1-D NDArray.');
        }
        $count = $height->shape()[0];
        if($bottom instanceof NDArray) {
            if($count>=2) {
                $bottom = $this->mo->copy($bottom);
            }
        } else {
            $bottom = $this->mo->full($x->shape(),$bottom,NDArray::float32);
        }
        if($style===null) {
            $style = 'stacked';
        } elseif ($style!='stacked' && $style!='sideBySide') {
            throw new InvalidArgumentException('style must be "stacked" or "sideBySide".');
        }
        $left = $this->mo->op($x,'-',$this->mo->op($width,'/',2.0));
        if($count>=2 && $style=='sideBySide') {
            $width = $this->mo->op($width,'/',$count);
        }

        $artists = [];
        for($i=0;$i<$count;$i++) {
            $color = $this->defaultColors[$this->currentPlotColorNumber];
            $barContainer = $this->newBar(
                $left,$bottom,$width,$height[$i],$color,$label);
            $this->artists->append($barContainer);
            $artists[] = $barContainer;
            $this->currentPlotColorNumber++;
            if($this->currentPlotColorNumber>=16) {
                $this->currentPlotColorNumber = 0;
            }
            if($i+1<$count) {
                if($style == 'stacked') {
                    $bottom = $this->mo->op($bottom,'+',$height[$i]);
                } else {
                    $left = $this->mo->op($left,'+',$width);
                }
            }
        }
        return $artists;
    }

    public function barh(
        $y,
        NDArray $width,
        $height=null,
        $left=null,
        string $label=null,
        string $style=null)
    {
        if(is_array($y)) {
            $this->setYTickLabels(array_values($y));
            $y = $this->mo->arange(count($y));
            $this->setYticks($y);
        } elseif(!($y instanceof NDArray)) {
            throw new InvalidArgumentException('first argument must be array or NDArray.');
        }
        if($y->ndim()!=1) {
            throw new InvalidArgumentException('first argument must be shape of 1-D.');
        }
        if($width->ndim()>2) {
            throw new InvalidArgumentException('secound argument must be shape of 1-D or 2-D');
        }
        if($y->shape()[0]!=$width->shape()[0]) {
            throw new InvalidArgumentException('Shape of y and width must be same');
        }
        if($width->ndim()==1) {
            $width = $width->reshape([1,$width->size()]);
        } else {
            $width = $this->mo->transpose($width);
        }

        if($height===null) {
            $height = $this->barWidth;
        } elseif(!is_numeric($height)&&!($height instanceof NDArray)) {
            throw new InvalidArgumentException('the height must be numeric or 1-D NDArray.');
        } elseif(($height instanceof NDArray) && $height->ndim()!=1) {
            throw new InvalidArgumentException('the height must be numeric or 1-D NDArray.');
        }
        if(!($height instanceof NDArray)) {
            $height = $this->mo->full($y->shape(),$height,NDArray::float32);
        }

        if($left===null) {
            $left = 0;
        } elseif(!is_numeric($left)&&!($left instanceof NDArray)) {
            throw new InvalidArgumentException('the left must be numeric or 1-D NDArray.');
        } elseif(($left instanceof NDArray) && $left->ndim()!=1) {
            throw new InvalidArgumentException('the left must be numeric or 1-D NDArray.');
        }
        $count = $width->shape()[0];
        if($left instanceof NDArray) {
            if($count>=2) {
                $left = $this->mo->copy($left);
            }
        } else {
            $left = $this->mo->full($y->shape(),$left,NDArray::float32);
        }
        if($style===null) {
            $style = 'stacked';
        } elseif ($style!='stacked' && $style!='sideBySide') {
            throw new InvalidArgumentException('style must be "stacked" or "sideBySide".');
        }

        $bottom = $this->mo->op($y,'-',$this->mo->op($height,'/',2.0));
        if($count>=2 && $style=='sideBySide') {
            $height = $this->mo->op($height,'/',$count);
        }

        $artists = [];
        for($i=0;$i<$count;$i++) {
            $color = $this->defaultColors[$this->currentPlotColorNumber];
            $barContainer = $this->newBar(
                $left,$bottom,$width[$i],$height,$color,$label);
            $this->artists->append($barContainer);
            $artists[] = $barContainer;
            $this->currentPlotColorNumber++;
            if($this->currentPlotColorNumber>=16) {
                $this->currentPlotColorNumber = 0;
            }
            if($i+1<$count) {
                if($style == 'stacked') {
                    $left = $this->mo->op($left,'+',$width[$i]);
                } else {
                    $bottom = $this->mo->op($bottom,'+',$height);
                }
            }
        }
        return $artists;
    }

    public function plot(
        NDArray $x, NDArray $y=null,
        string $marker=null, string $label=null) : array
    {
        if($y===null) {
            $y = $x;
            $x = $this->mo->arange($y->shape()[0]);
        }
        if($x->ndim()>2 || $y->ndim()>2) {
            throw new InvalidArgumentException('Type of plot data must be shape of 1-D or 2-D');
        }
        if($x->shape()[0]!=$y->shape()[0]) {
            throw new InvalidArgumentException('Shape of x and y must be same');
        }
        if($x->ndim()==1) {
            $x = $x->reshape([1,$x->size()]);
            $incX = 0;
        } else {
            $x = $this->mo->transpose($x);
            $incX = 1;
        }
        if($y->ndim()==1) {
            $y = $y->reshape([1,$y->size()]);
            $incY = 0;
        } else {
            $y = $this->mo->transpose($y);
            $incY = 1;
        }
        if($incX==0 && $incY==0) {
            $incX = $incY = 1;
        }

        $count = max($x->shape()[0],$y->shape()[0]);
        $artists = [];
        if($incX<=0&&$incY<=0) {
            throw new LogicException('internal error: $incX='.$incX.',$incY='.$incY);
        }
        $markerString = $this->generateMarkerString($marker);
        $lineStyleString = $this->generateLineStyleString($marker);
        for($i=0,$j=0; $i<$count && $j<$count; $i+=$incY,$j+=$incX) {
            $color = $this->defaultColors[$this->currentPlotColorNumber];
            $artist = $this->newLine2D($x[$j],$y[$i],$markerString,$lineStyleString,$label,$color);
            $this->artists->append($artist);
            $artists[] = $artist;
            $this->currentPlotColorNumber++;
            if($this->currentPlotColorNumber>=16) {
                $this->currentPlotColorNumber = 0;
            }
        }
        return $artists;
    }

    protected function generateMarkerString(string $marker=null)
    {
        if($marker===null)
            return null;
        $count = strlen($marker);
        for($i=0;$i<$count;$i++) {
            $c = $marker[$i];
            if(isset($this->markerCharactor[$c])) {
                return $this->markerCharactor[$c];
            }
        }
        return null;
    }

    protected function generateLineStyleString(string $marker=null)
    {
        if($marker===null)
            return null;
        foreach($this->lineStyleCharactor as $key => $value) {
            if(strpos($marker,$key)!==false) {
                return $value;
            }
        }
        return null;
    }

    public function scatter(
        NDArray $x, NDArray $y, NDArray $size=null,
        $color=null,string $marker=null,$label=null)
    {
        if($x->shape()!=$y->shape()) {
            throw new InvalidArgumentException('Shape of x and y must be same');
        }
        if($size!==null) {
            if($x->shape()!=$size->shape()) {
                throw new InvalidArgumentException('Shape of size must be same with x and y');
            }
            $size = $size->reshape([$size->size()]);
        }
        $marker = $this->generateMarkerString($marker);
        $x = $x->reshape([$x->size()]);
        $y = $y->reshape([$y->size()]);
        if($color===null) {
            $color = $this->defaultColors[$this->currentPlotColorNumber];
        }
        $artist = $this->newMarker($x,$y,$size,$color,$marker,$label);
        $this->artists->append($artist);
        $this->currentPlotColorNumber++;
        if($this->currentPlotColorNumber>=16) {
            $this->currentPlotColorNumber = 0;
        }
        return $artist;
    }

    public function pie(
        NDArray $x,
        array $labels=null,
        $startangle=null,
        $autopct=null,
        array $explodes=null)
    {
        $count = $x->size();
        if($labels)
            $labels = array_values($labels);
        if($explodes)
            $explodes = array_values($explodes);
        $sum = $this->mo->asum($x);
        if($startangle===null)
            $startangle = 0;
        $start = $startangle;
        $start = $start % 360;
        for($i=0;$i<$count;$i++) {
            $color = $this->defaultColors[$this->currentPlotColorNumber];
            $end = $start + $x[$i]/$sum*360;
            $label = isset($labels[$i]) ? $labels[$i] : null;
            $explode = isset($explodes[$i]) ? $explodes[$i] : null;
            $pctText = $autopct ? sprintf($autopct,$x[$i]/$sum*100) : null;
            $artist = $this->newWedge([0,0],1,$start,$end,$color,$label,$pctText,$explode);
            $start = $end;
            if($start>=360)
                $start -= 360;
            $this->artists->append($artist);
            $artists[] = $artist;
            $this->currentPlotColorNumber++;
            if($this->currentPlotColorNumber>=16) {
                $this->currentPlotColorNumber = 0;
            }
        }
        $this->setFrame(false);
        return $artists;
    }

    public function legend(array $handles=null,array $labels=null)
    {
        if($handles==null) {
            $handles = [];
            $labels = [];
            foreach ($this->artists as $handle) {
                $label = $handle->getLabel();
                if($label) {
                    $handles[] = $handle;
                    $labels[] = $label;
                }
            }
        } elseif($labels==null) {
            $labels = array_values($handles);
            $handles = $this->artists;
        }
        foreach ($labels as $label) {
            if(!is_string($label)) {
                throw new InvalidArgumentException('labels must be specified.');
            }
        }
        $this->legend = $this->newLegend($handles,$labels);
        return $this->legend;
    }

    public function imshow(NDArray $x, string $cmap=null)
    {
        if($cmap==null)
            $cmap = 'viridis';
        $cmap = $this->cmapManager->get($cmap);
        $artist = $this->newImage($x,$cmap);
        $this->artists->append($artist);
        //$this->setDataAreaMargin(0);
        return $artist;
    }


    public function axis($axis)
    {
        $this->axis = $axis;
    }

    public function setXLabel($label)
    {
        $this->xLabel = $label;
    }

    public function setYLabel($label)
    {
        $this->yLabel = $label;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function setXTicks(NDArray $ticks=null)
    {
        $this->xTicks = $ticks;
    }

    public function setXTickLabels(array $labels=null)
    {
        $this->xTickLabels = $labels;
    }

    public function setYTicks(NDArray $ticks=null)
    {
        $this->yTicks = $ticks;
    }

    public function setYTickLabels(array $labels=null)
    {
        $this->yTickLabels = $labels;
    }

    public function setFrame(bool $frame)
    {
        $this->frame = $frame;
    }

    public function setXScale($type)
    {
        $this->scaling->setXScaleType($type);
    }

    public function setYScale($type)
    {
        $this->scaling->setYScaleType($type);
    }

    public function setDataAreaMargin($dataAreaMargin)
    {
        $this->dataAreaMargin = $dataAreaMargin;
    }

    public function calcDataLimit()
    {
        $minX = $minY = $maxX = $maxY = null;
        foreach ($this->artists as $artist) {
            if($minX===null) {
                [$minX,$minY,$maxX,$maxY] = $artist->calcDataLimit();
                continue;
            }
            [$minX1,$minY1,$maxX1,$maxY1] = $artist->calcDataLimit();
            $minX = min($minX,$minX1);
            $minY = min($minY,$minY1);
            $maxX = max($maxX,$maxX1);
            $maxY = max($maxY,$maxY1);
        }
        if($minX==$maxX) {
            if($maxX==0) {
                $minX = 0.0;
                $maxX = 1.0;
            } else {
                $minX = $maxX-$maxX*0.05;
                $maxX = $maxX+$maxX*0.05;
            }
        }
        if($minY==$maxY) {
            if($maxY==0) {
                $minY = 0.0;
                $maxY = 1.0;
            } else {
                $minY = $maxY-$maxY*0.05;
                $maxY = $maxY+$maxY*0.05;
            }
        }
        return [$minX, $minY, $maxX, $maxY];
    }

    public function draw()
    {
        $dataLimit = $this->calcDataLimit();

        $this->scaling->calcScaling(
            $this->plotArea,$dataLimit,$this->dataAreaMargin,$this->axis);

        // plot frame
        if($this->frame) {
            $frame = $this->newBoxFrame();
            if($this->xTicks)
                $frame->setXTicks($this->xTicks);
            if($this->xTickLabels)
                $frame->setXTickLabels($this->xTickLabels);
            if($this->yTicks)
                $frame->setYTicks($this->yTicks);
            if($this->yTickLabels)
                $frame->setYTickLabels($this->yTickLabels);
            $frame->draw();
        }

        if($this->xLabel) {
            $xLabel = $this->newXAxisLabel($this->xLabel);
            $xLabel->draw();
        }
        if($this->yLabel) {
            $yLabel = $this->newYAxisLabel($this->yLabel);
            $yLabel->draw();
        }
        if($this->title) {
            $title = $this->newTitle($this->title);
            $title->draw();
        }

        if($this->legend) {
            $this->legend->calcAreas();
        }
        if($this->frame) {
            [$left,$bottom,$width,$height] = $this->plotArea;
            //$this->renderer->setClip($left+1,$bottom+1,$left+$width-1,$bottom+$height-1);
        }
        // plot data
        foreach ($this->artists as $artist) {
            $artist->draw($this->legend);
        }
        if($this->frame) {
            $this->renderer->setClip(-1,-1,-1,-1);
        }

        // plot legend
        if($this->legend) {
            $this->legend->draw();
        }
    }
}
