<?php
namespace Rindow\Math\Plot;

use ArrayObject;
use Rindow\Math\Matrix\MatrixOperator;
use Rindow\Math\Plot\Renderer\GDDriver;
use Rindow\Math\Plot\System\Figure;
use Rindow\Math\Plot\System\Configure;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\CmapManager;

use InvalidArgumentException;

class Plot
{
    use Configured;

    protected $mo;
    protected $figures = [];
    protected $config;
    protected $renderer;
    protected $cmapManager;
    protected $currentFig = -1;
    protected $skipCleaning = false;
    protected $skipRunViewer;

    public function __construct(array $config=null,$matrixOperator=null,$renderer=null,$cmapManager=null)
    {
        $this->setConfig($config);
        $this->setMatrixOperator($matrixOperator);
        if($renderer)
            $this->setRenderer($renderer);
        if($cmapManager)
            $this->setCmapManager($cmapManager);
    }

    public function setConfig(array $config=null)
    {
        if($config)
            $this->config = new Configure($config);
        else {
            $this->config = new Configure();
        }
        $this->loadConfigure($this->config,
            ['renderer.skipCleaning','renderer.skipRunViewer']);
    }

    public function setMatrixOperator($matrixOperator=null)
    {
        if($matrixOperator===null) {
            $matrixOperator = new MatrixOperator();
        }
        $this->mo = $matrixOperator;
    }

    public function setRenderer($renderer)
    {
        $this->renderer = $renderer;
    }

    public function getRenderer()
    {
        if($this->renderer==null) {
            $this->renderer = new GDDriver(true,null,null,
                                    $this->skipCleaning,$this->skipRunViewer);
        }
        return $this->renderer;
    }

    public function setCmapManager($cmapManager)
    {
        $this->cmapManager = $cmapManager;
    }

    public function getCmapManager()
    {
        if($this->cmapManager==null) {
            $this->cmapManager = new CmapManager();
        }
        return $this->cmapManager;
    }

    protected function newFigure($figsize)
    {
        $figure = new Figure(
            $this->config,
            $this->getRenderer(),
            $this->mo,
            $this->getCmapManager(),
            $figsize);
        $this->figures[] = $figure;
        $this->currentFig++;
        return $figure;
    }

    public function figure($num=null,array $figsize=null)
    {
        if(is_int($num)) {
            if(isset($this->figures[$num])) {
                throw new InvalidArgumentException('Figure #'.$num.' is not found');
            }
            $figure = $this->figures[$num];
        } elseif($num==null||is_string($num)) {
            $figure = $this->newFigure($figsize);
        } else {
            throw new InvalidArgumentException('First argument must be integer or string');
        }
        return $figure;
    }

    public function subplots(int $nRows=1,int $nCols=1,array $figsize=null) : array
    {
        $figure = $this->newFigure($figsize);
        $n = $nRows*$nCols;
        $axes = [];
        for($i=0;$i<$n;$i++) {
            $axes[] = $figure->addSubPlot($nRows,$nCols,$i);
        }
        return [$figure, $axes];
    }

    public function getAxes($figId=null,$axesId=null)
    {
        if(count($this->figures)==0)
            $this->figure();
        if($figId===null)
            $figId = $this->currentFig;
        $figure = $this->figures[$figId];
        $axes = $figure->getAxes();
        if(count($axes)==0)
            $axes[] = $figure->addSubPlot();
        if($axesId===null)
            $axesId=0;
        return $axes[$axesId];
    }

    public function subplot($nRows=null,$nCols=null,$idx=null)
    {
        if(count($this->figures)==0)
            throw new InvalidArgumentException('no figure');
            $figure = $this->figures[$this->currentFig];
        $axes = $figure->addSubPlot($nRows,$nCols,$idx);
        return $axes;
    }

    public function bar($x,$height,$width=null,$bottom=null,$label=null,$style=null)
    {
        return $this->getAxes()->bar($x,$height,$width,$bottom,$label,$style);
    }

    public function barh($y,$width,$height=null,$left=null,$label=null,$style=null)
    {
        return $this->getAxes()->barh($y,$width,$height,$left,$label,$style);
    }

    public function plot($x,$y=null,$marker=null,$label=null)
    {
        return $this->getAxes()->plot($x,$y,$marker,$label);
    }

    public function scatter($x,$y,$size=null,$color=null,$marker=null,$label=null)
    {
        return $this->getAxes()->scatter($x,$y,$size,$color,$marker,$label);
    }

    public function pie($x,$labels=null,$startangle=null,$autopct=null,$explode=null)
    {
        return $this->getAxes()->pie($x,$labels,$startangle,$autopct,$explode);
    }

    public function legend(array $artists=null,array $titles=null)
    {
        return $this->getAxes()->legend($artists,$titles);
    }

    public function axis($axis)
    {
        return $this->getAxes()->axis($axis);
    }

    public function xLabel($label)
    {
        return $this->getAxes()->setXLabel($label);
    }

    public function yLabel($label)
    {
        return $this->getAxes()->setYLabel($label);
    }

    public function title($title)
    {
        return $this->getAxes()->setTitle($title);
    }

    public function xticks($ticks,$labels)
    {
        $axes = $this->getAxes();
        $axes->setXticks($ticks);
        $axes->setXtickLabels($labels);
    }

    public function xscale($type)
    {
        return $this->getAxes()->setXScale($type);
    }

    public function yscale($type)
    {
        return $this->getAxes()->setYScale($type);
    }

    public function imshow($x,$cmap=null)
    {
        return $this->getAxes()->imshow($x,$cmap);
    }

    public function show(string $filename=null)
    {
        $renderer = $this->getRenderer();
        if(!$this->skipCleaning)
            $renderer->cleanUp();
        foreach ($this->figures as $figure) {
            [$width,$height] = $figure->getFigSize();
            $renderer->open($width,$height);
            $figure->draw();
            $renderer->show($filename);
            $renderer->close();
        }
    }
}
