<?php
namespace RindowTest\NeuralNetworks\Model\ModelLoaderTest;

use PHPUnit\Framework\TestCase;
use Rindow\Math\Matrix\MatrixOperator;
use Rindow\Math\Plot\Plot;
use Rindow\Math\Plot\Renderer\GDDriver;
use Interop\Polite\Math\Matrix\NDArray;

class Test extends TestCase
{
    public function getConfig()
    {
        return [
            'renderer.skipCleaning' => true,
            'renderer.skipRunViewer' => getenv('TRAVIS_PHP_VERSION') ? true : false,
            //'title.fontSize' => 4,
            //'wedge.pctColor' => 'white',
            //'bar.barWidth' => 0.5,
            //'bar.legendLineWidth' => 4,
            //'figure.figsize' => [800,600],
            //'frame' => false,
            //'frame.xTickPosition' => 'up',
            //'frame.yTickPosition' => 'right',
            //'frame.frameColor' => 'red',
            //'frame.labelColor' => 'green',
        ];
    }

    public function testCleanUp()
    {
        $renderer = new GDDriver();
        $renderer->cleanUp();
        $this->assertTrue(true);
    }

    public function testColorMap()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);
        $cmap = new \Rindow\Math\Plot\System\Colormap('viridis');
        $x = $mo->array($cmap->getMapData());
        $plt->plot($x);
        $plt->show();

        $this->assertTrue(true);
    }

    public function testColormap2()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->array([[0,1],[2,3]]);
        $x = $mo->arange(256)->reshape([16,16]);
        $cmap = 'magma';
        [$fig,$ax] = $plt->subplots(1,3);
        $ax[0]->imshow($x,$cmap);
        $ax[0]->axis('equal');
        $ax[0]->setFrame(false);
        $ax[1]->plot($x->reshape([256]));
        $cmap = new \Rindow\Math\Plot\System\Colormap($cmap);
        $x = $mo->array($cmap->getMapData());
        $ax[2]->plot($x);
        $plt->show();

        $this->assertTrue(true);
    }

    public function testSimpleFigs()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);
        //$x = $mo->arange(21,-1.0,0.1);
        //$x = $mo->arange(22);
        //$x = $mo->arange(5);
        //$y = $mo->ones($x->shape(),$x->dtype());

        $x = $mo->arange(9,0,1000);
        $plt->figure('new',[465,480]);
        $plt->plot($x,$x);
        $plt->show();

        $this->assertTrue(true);
    }

    public function testSubplots()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(8,0,1000);
        [$fig,$ax] = $plt->subplots(3,3);
        $ax[0]->plot($x,$x);
        $ax[1]->plot($x,$x);
        $plt->show();

        $this->assertTrue(true);
    }

    public function testLogScale()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(101,null,null,NDArray::float64);
        $plt->figure('new',[465,480]);
        $plt->plot($mo->op(10,'**',$x),$x);
        $plt->xscale('log');
        $plt->show();

        $this->assertTrue(true);
    }

    /**
    * @expectedException        RuntimeException
    * @expectedExceptionMessage "log" scale cannot be used for negative values.
     */
    public function testInvalidLogScale()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->array([-1000,-100,-10,-1,1,10,100,1000]);
        $plt->figure('new',[465,480]);
        $plt->plot($x,$x);
        $plt->xscale('log');
        $plt->show();

        $this->assertTrue(true);
    }

    public function testScaleWithSubplots()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(10,-1,10,NDArray::float64);
        [$fig,$ax] = $plt->subplots(1,2);
        $ax[0]->plot($x,$mo->op(10,'**',$x));
        $ax[0]->setYScale('log');
        $ax[1]->plot($mo->op(10,'**',$x),$x);
        $ax[1]->setXScale('log');
        $plt->show();

        $this->assertTrue(true);
    }

    public function testPie()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->array([15, 30, 45, 5]);
        $labels=['Frogs', 'Hogs', 'Dogs', 'Logs'];
        $explodes = [0, 0.1, 0, 0];
        [$fig,$ax] = $plt->subplots(1,2);
        $ax[0]->pie($x,$labels,$startangle=90,$autopct='%1.1f%%',$explodes);
        $ax[0]->axis('equal');
        $ax[0]->setTitle('labels sample');
        $pies = $ax[1]->pie($x,null,$startangle=90,$autopct='%1.1f%%',$explodes);
        $ax[1]->legend($pies,$labels);
        $ax[1]->axis('equal');
        $ax[1]->setTitle('legend sample');
        $plt->show();

        $this->assertTrue(true);
    }

    public function testBarh()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->array([[1,2,3],[3,2,1],[1,1,1],[1,2,1],[2,1,2]]);
        [$fig,$ax] = $plt->subplots(2);
        $bars = $ax[0]->bar(['a','b','c','d','e'],$x,$width=null,$bottom=null,$label=null,$style='sideBySide');
        $ax[0]->legend($bars,['one','two','three']);
        $bars = $ax[1]->barh(['a','b','c','d','e'],$x);
        $ax[1]->legend($bars,['one','two','three']);
        $ax[0]->setTitle('sample');
        $ax[0]->setYLabel('y-label');
        $ax[1]->setXLabel('x-label');
        $plt->show();

        $this->assertTrue(true);
    }

    public function testBarSimple()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(5);
        //$plt->figure(null,[150,480]);
        $plt->bar($x,$x);
        $plt->bar($x,$x,$width=null,$bottom=$x);
        $plt->xTicks($x,['a','b','c','d','e']);
        $plt->show();

        $this->assertTrue(true);
    }

    public function testComplexBarAndPlotAndScatter()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(5);
        $width = 0.35;
        $plt->figure();
        $plt->bar($mo->op($x,'-',$width/2),$x,$width,$bottom=null,$label='women');
        $plt->bar($mo->op($x,'+',$width/2),$mo->op($x,'*',1.5),$width,$bottom=null,$label='men');
        $plt->scatter($x,$mo->op($x,'*',1.6),$mo->op($x,'*',10),$color=null,$marker=null,$label='maru');
        $plt->plot($x,$mo->op($x,'*',2),'o-.',$label='x*2');
        $plt->xTicks($x,['a','b','c','d','e']);
        $plt->legend();
        $plt->xlabel('x-label');
        $plt->ylabel('y-label');
        $plt->title('sample');
        $plt->show();

        $this->assertTrue(true);
    }

    public function testComplexScatterAndPlot()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(5);
        $a = $plt->scatter($mo->op($x,'*',2),$x,$mo->op($x,'*',2));
        $b = $plt->scatter($mo->op($x,'*',2),$mo->op($x,'+',2),$mo->op($x,'*',2),null,'s');
        $plt->plot($mo->op($x,'*', 1.0),$mo->op($x,'**',3),'-.o','x**3');
        $plt->plot($x,$mo->op($x,'*',2),'-.','x*2');
        $plt->plot($mo->op($x,'-',1.0),$x,'-.','x-1');
        $plt->plot($mo->op($x,'-',0.8),$x,'--','x-0.8');
        $plt->plot($mo->op($x,'-',1.0),$mo->op($x,'*',-2.0),':','-2.0*x');
        $plt->plot($mo->op($x,'-',0.0),$mo->op($x,'*',-2.0),':','-0.0*x');
        $plt->plot($mo->op($x,'-',1.0),$mo->op($x,'*',2.0),':','-2.0*x');
        //$plt->legend([$a,$b],['a','b']);
        $plt->legend();
        $plt->show();

        $this->assertTrue(true);
    }

    public function testSimplePlotWithMultifigure()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(5);
        $plt->plot($x,$mo->op($x,'**',3),'-.o','x**3');
        $plt->plot($mo->op($x,'-', 1.0),$mo->op($x,'**',3),'-.o','x**3');

        $plt->figure();
        $plt->plot($x,$mo->op($x,'**',3),'-.o','x**3');
        $plt->plot($mo->op($x,'-', 1.0),$mo->op($x,'**',3),'-.o','x**3');
        $plt->show();

        $this->assertTrue(true);
    }

    public function testSubplotsWithTitleAndLegendAndLabels()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(5);
        [$fig,$ax] = $plt->subplots(2,3);
        $ax[0]->plot($x,$mo->op($x,'**',3),'-.o','x**3');
        $ax[0]->setTitle('sample');
        $ax[1]->plot($x,$mo->op($x,'**',2),'-.o','x*2');
        $ax[2]->plot($x,$mo->op($x,'*',2),'-.o','x*2');
        $ax[0]->legend();
        $ax[1]->legend();
        $ax[2]->legend();

        $plt->xlabel('x-label');
        $plt->ylabel('y-label');
        $plt->title('sample');
        $plt->show();

        $this->assertTrue(true);
    }

    public function testTitleAndLabels()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(5);
        $plt->plot($x,$mo->op($x,'*',2),'-.o','x*2');
        $plt->legend();
        $plt->xlabel('x-label');
        $plt->ylabel('y-label');
        $plt->title('sample');
        $plt->show();

        $this->assertTrue(true);
    }
}









//


/*
'l'
'_'
'+'
'x'
'o'
','
'D'
'1'
'2'
'3'
'4'
'*'
'g'
'|'
't'
's'
'h'
'^'
'v'
*/
