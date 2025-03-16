<?php
namespace Rindow\Math\Plot\System;

use InvalidArgumentException;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;
use Rindow\Math\Plot\Artist\Mappable;

class Figure
{
    use Configured;

    protected $config;  //
    protected $renderer;
    protected $mo;
    protected $axes=[];
    protected $window;
    protected $num;
    protected $cmapManager;

    // configure
    protected $figsize = [640,480]; //  [width, height] in inches.
    protected $leftMargin = 80;
    protected $bottomMargin = 55;
    protected $rightMargin = 64;
    protected $topMargin = 60;
    protected $axesHSpacingRatio = 0.25;
    protected $axesVSpacingRatio = 0.25;
    protected $bgColor = 'LightGray';
    protected $colorbarWidth = 10;

    public function __construct(
        $num,
        Configure $config, $renderer, $mo, $cmapManager,
        ?array $figsize=null)
    {
        $this->loadConfigure($config,
            ['figsize',
             'leftMargin','bottomMargin','rightMargin','topMargin',
             'axesHSpacingRatio','axesVSpacingRatio','bgColor','colorbarWidth'],
            'figure');
        $this->num = $num;
        $this->config = $config;
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->cmapManager = $cmapManager;
        if($figsize) {
            $this->figsize = $figsize;
        }
    }

    public function num()
    {
        return $this->num;
    }

    protected function newAxes($plotArea)
    {
        return new Axes($this->config,$this->renderer,$this->mo,
            $this->cmapManager,$plotArea);
    }

    public function addAxes(Axes $axes) : void
    {
        $this->axes[] = $axes;
    }

    public function setAxes(array $axes) : void
    {
        $this->axes = $axes;
    }

    public function getAxes() : array
    {
        return $this->axes;
    }

    public function getFigSize() : array
    {
        return $this->figsize;
    }

    public function calcPlotArea()
    {
        [$width, $height] = $this->figsize;

        // plot area
        $this->plotAreaLeft   = $left + $this->leftMargin;
        $this->plotAreaBottom = $bottom + $this->bottomMargin;
        $this->plotAreaWidth  = $width - $this->leftMargin - $this->rightMargin;
        $this->plotAreaHeight = $height - $this->bottomMargin - $this->topMargin;
    }

    public function addSubPlot(
        ?int $nRows=null, ?int $nCols=null, ?int $index=null,
        ?int $rowspan=null, ?int $colspan=null)
    {
        if($nRows===null)
            $nRows=1;
        if($nCols===null)
            $nCols=1;
        if($index===null)
            $index=0;
        if($rowspan===null)
            $rowspan=1;
        if($colspan===null)
            $colspan=1;
        $n = $nRows*$nCols;
        [$width, $height] = $this->figsize;
        $left = $bottom = 0;

        // Calculate world plot area
        $left   = $left + $this->leftMargin;
        $bottom = $bottom + $this->bottomMargin;
        $width  = $width - $this->leftMargin - $this->rightMargin;
        $height = $height - $this->bottomMargin - $this->topMargin;

        $plotAreaWidth = (int)floor($width / (1+(1.0+$this->axesHSpacingRatio)*($nCols-1)))*$colspan;
        $plotAreaHeight = (int)floor($height / (1+(1.0+$this->axesVSpacingRatio)*($nRows-1)))*$rowspan;

        $m = (int)floor(($index) / $nCols);
        $n = ($index) % $nCols;
        $plotAreaLeft = (int)floor($n*$plotAreaWidth*(1.0+$this->axesHSpacingRatio))
            + $this->leftMargin;

        $plotAreaBottom = (int)floor(($nRows-1-$m)*$plotAreaHeight*(1.0+$this->axesVSpacingRatio))
            + $this->bottomMargin;
        $axes = $this->newAxes([$plotAreaLeft, $plotAreaBottom, $plotAreaWidth, $plotAreaHeight]);
        $this->addAxes($axes);
        return $axes;
    }

    public function colorbar(Mappable $mappable,Axes $ax,?bool $absolute=null)
    {
        foreach ($this->axes as $axes) {
            if($axes===$ax) {
                return $this->doColorbar($mappable,$ax,$absolute);
            }
        }
        throw new InvalidArgumentException('Target axes not found.');
    }

    protected function doColorbar(Mappable $mappable,Axes $ax,?bool $absolute=null)
    {
        if(!$absolute) {
            [$left, $bottom, $width, $height] = $ax->getPlotArea();
            $newLeft = (int)floor($left+$width-($width/5)+$this->axesHSpacingRatio*($width/5));
            $width = (int)floor($width*4/5);
            $ax->setPlotArea([$left, $bottom, $width, $height]);
            $ax = $this->newAxes([$newLeft, $bottom, $this->colorbarWidth, $height]);
            $this->addAxes($ax);
        }
        $cmap = $mappable->colormap();
        [$bottom,$top] = $mappable->colorRange();
        $ax->colorbar($cmap,$bottom,$top);
        $ax->hideXTicks(true);
        $ax->setYTickPosition('right');
        $ax->setDataAreaMargin(0);
        return $ax;
    }

    public function draw()
    {
        $bgColor = $this->renderer->allocateColor($this->bgColor);
        [$width, $height] = $this->figsize;
        $this->renderer->filledRectangle(0,0,$width-1,$height-1,$bgColor);

        foreach ($this->axes as $axes) {
            $axes->draw();
        }
    }
}
