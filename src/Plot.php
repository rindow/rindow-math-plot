<?php
namespace Rindow\Math\Plot;

use ArrayObject;
use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Matrix\MatrixOperator;
use Rindow\Math\Plot\Renderer\GDDriver;
use Rindow\Math\Plot\System\Figure;
use Rindow\Math\Plot\System\Configure;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\CmapManager;
use Rindow\Math\Plot\Artist\Mappable;
use Rindow\Math\Plot\Artist\DataArtist;

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
        $this->currentFig++;
        $figure = new Figure(
            $this->currentFig,
            $this->config,
            $this->getRenderer(),
            $this->mo,
            $this->getCmapManager(),
            $figsize);
        $this->figures[$this->currentFig] = $figure;
        return $figure;
    }

    protected function clearFigures()
    {
        $this->figures = [];
        $this->currentFig = -1;
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

    public function currentFigure()
    {
        if(count($this->figures)==0) {
            throw new InvalidArgumentException('no figure');
        }
        return $this->figures[$this->currentFig];
    }

    public function subplot(int $nRows=null, int $nCols=null, int $idx=null)
    {
        $figure = $this->currentFigure();
        $axes = $figure->addSubPlot($nRows,$nCols,$idx);
        return $axes;
    }

    public function bar($x, NDArray $height,
        $width=null, $bottom=null, string $label=null, string $style=null) : array
    {
        return $this->getAxes()->bar($x,$height,$width,$bottom,$label,$style);
    }

    public function barh($y, NDArray $width,
        $height=null, $left=null, string $label=null, string $style=null) : array
    {
        return $this->getAxes()->barh($y,$width,$height,$left,$label,$style);
    }

    public function plot(NDArray $x, NDArray $y=null,
                        string $marker=null, string $label=null) : array
    {
        return $this->getAxes()->plot($x,$y,$marker,$label);
    }

    public function scatter(NDArray $x, NDArray $y, NDArray $size=null,
                    $color=null, string $marker=null, $label=null) : DataArtist
    {
        return $this->getAxes()->scatter($x,$y,$size,$color,$marker,$label);
    }

    public function pie(NDArray $x, array $labels=null,
                    float $startangle=null, $autopct=null, array $explodes=null) : array
    {
        return $this->getAxes()->pie($x,$labels,$startangle,$autopct,$explode);
    }

    public function imshow(
        NDArray $x,
        string $cmap=null,
        array $norm=null,
        array $extent=null,
        string $origin=null) : DataArtist
    {
        return $this->getAxes()->imshow($x,$cmap,$norm,$extent,$origin);
    }

    public function colorbar(Mappable $mappable,$ax=null,bool $absolute=null)
    {
        $figure = $this->currentFigure();
        if($ax===null) {
            $ax = $this->getAxes();
        }
        return $figure->colorbar($mappable,$ax,$absolute);
    }

    public function legend(array $artists=null,array $labels=null)
    {
        return $this->getAxes()->legend($artists,$labels);
    }

    public function axis($command)
    {
        if($command=='equal') {
            return $this->getAxes()->setAspect('equal');
        } else {
            throw new InvalidArgumentException('Unknown command.');
        }
    }

    public function xlabel($label)
    {
        return $this->getAxes()->setXLabel($label);
    }

    public function ylabel($label)
    {
        return $this->getAxes()->setYLabel($label);
    }

    public function title($title)
    {
        return $this->getAxes()->setTitle($title);
    }

    public function xticks(NDArray $ticks,array $labels)
    {
        $axes = $this->getAxes();
        $axes->setXticks($ticks);
        $axes->setXtickLabels($labels);
    }

    public function yticks(NDArray $ticks,array $labels)
    {
        $axes = $this->getAxes();
        $axes->setYticks($ticks);
        $axes->setYtickLabels($labels);
    }

    public function xscale($type)
    {
        return $this->getAxes()->setXScale($type);
    }

    public function yscale($type)
    {
        return $this->getAxes()->setYScale($type);
    }

    public function show(string $filename=null)
    {
        $renderer = $this->getRenderer();
        if(!$this->skipCleaning)
            $renderer->cleanUp();
        $figcount = count($this->figures);
        foreach ($this->figures as $n => $figure) {
            [$width,$height] = $figure->getFigSize();
            $renderer->open($width,$height);
            $figure->draw();
            if($filename===null || $figcount<2) {
                $renderer->show($filename);
            } else {
                $pathinfo = pathinfo($filename);
                $renderer->show($pathinfo['dirname'].'/'.
                        $pathinfo['filename'].$n.'.'.$pathinfo['extension']);
            }
            $renderer->close();
        }
        $this->clearFigures();
    }
}
