<?php
namespace Rindow\Math\Plot\System;

use InvalidArgumentException;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;

class Figure
{
    use Configured;

    protected $config;  //
    protected $renderer;
    protected $mo;
    protected $axes=[];
    protected $window;

    // configure
    protected $figsize = [640,480]; //  [width, height] in inches.
    protected $leftMargin = 80;
    protected $bottomMargin = 55;
    protected $rightMargin = 64;
    protected $topMargin = 60;
    protected $axesHSpacingRatio = 0.2;
    protected $axesVSpacingRatio = 0.2;
    protected $bgColor = 'LightGray';

    public function __construct(
        Configure $config, $renderer, $mo, $cmapManager,
        array $figsize=null)
    {
        $this->loadConfigure($config,
            ['figsize',
             'leftMargin','bottomMargin','rightMargin','topMargin',
             'axesHSpacingRatio','axesVSpacingRatio','bgColor'],
            'figure');
        $this->config = $config;
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->cmapManager = $cmapManager;
        if($figsize) {
            $this->figsize = $figsize;
        }
    }

    protected function newAxes($plotArea)
    {
        return new Axes($this->config,$this->renderer,$this->mo,
            $this->cmapManager,$plotArea);
    }

    public function addAxes(Axes $axes)
    {
        $this->axes[] = $axes;
    }

    public function setAxes(array $axes)
    {
        $this->axes = $axes;
    }

    public function getAxes() : array
    {
        return $this->axes;
    }

    public function getFigSize()
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
        int $nRows=null, int $nCols=null, int $index=null,
        int $rowspan=null, int $colspan=null)
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
