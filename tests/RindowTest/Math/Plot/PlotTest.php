<?php
namespace RindowTest\NeuralNetworks\Model\ModelLoaderTest;

use PHPUnit\Framework\TestCase;
use Rindow\Math\Matrix\MatrixOperator;
use Rindow\Math\Plot\Plot;
use Rindow\Math\Plot\Renderer\GDDriver;
use Interop\Polite\Math\Matrix\NDArray;
use RuntimeException;

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
            //'frame.framePadding' => 0,
        ];
    }

    public function testCleanUp()
    {
        $renderer = new GDDriver();
        $renderer->cleanUp();
        $this->assertTrue(true);
    }

    public function testColorMap0()
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
        //$ax[0]->setAspect('equal');
        $ax[0]->setFrame(false);
        $ax[1]->plot($x->reshape([256]));
        $cmap = new \Rindow\Math\Plot\System\Colormap($cmap);
        $x = $mo->array($cmap->getMapData());
        $ax[2]->plot($x);
        $plt->show();

        $this->assertTrue(true);
    }

    public function testColormap3()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x0 = $mo->arange(256)->reshape([16,16]);
        $x1 = $mo->op($x0,'-',128);
        $x2 = $mo->op($mo->astype($x1,NDArray::float32),'/',256);
        $cmap = 'magma';
        [$fig,$ax] = $plt->subplots(1,3);
        $ax[0]->setDataAreaMargin(0);
        $ax[0]->imshow($x0,$cmap);
        $ax[1]->imshow($x1,$cmap,[-64,64]);
        $ax[2]->imshow($x2,$cmap);
        $plt->show();
        $this->assertTrue(true);
    }

    public function testColorbar0()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x0 = $mo->random()->randn([100,100]);
        $x1 = $mo->arange(256,-127)->reshape([16,16]);
        $x2 = $mo->op($mo->astype($x1,NDArray::float32),'/',256);
        [$fig,$ax] = $plt->subplots(3);
        //$ax[0]->setFrame(false);
        $ax[0]->setAspect('equal');
        $ax[0]->setDataAreaMargin(0);
        $img0 = $ax[0]->imshow($x0);
        //$img1 = $ax[1]->imshow($x1);
        $img2 = $ax[2]->imshow($x2);
        $fig->colorbar($img0,$ax[0]);
        $fig->colorbar($img2,$ax[1],true);
        $fig->colorbar($img2,$ax[2]);
        $plt->show();
        $this->assertTrue(true);
    }

    public function testColorbar1()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->random()->randn([10,10]);
        $img = $plt->imshow($x);
        $plt->colorbar($img);
        $plt->show();
        $this->assertTrue(true);
    }

    public function testColorbar2()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->random()->randn([10,10]);
        $img = $plt->imshow($x,'jet',[-4,6],[-3,3,0,7]);
        $plt->colorbar($img);
        $plt->show();
        $this->assertTrue(true);
    }

    public function testColorbar3()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $fn = function($x) {
            return 1/sqrt(2*pi())*exp(-$x*$x/2);
        };
        $x1 = $mo->f($fn,$mo->arange(100,-5.0, 0.08));
        $x2 = $mo->f($fn,$mo->arange(100,-3.0, 0.08));
        $y1 = $mo->la()->multiply($x1,$mo->la()->multiply($x1,$mo->ones([100,100])),true);
        $y2 = $mo->la()->multiply($x2,$mo->la()->multiply($x2,$mo->ones([100,100])),true);
        $y = $mo->op($y1,'-',$y2);
        $img = $plt->imshow($y,null,null,[-4,4,-4,4]);
        $plt->colorbar($img);
        $plt->show();
        $this->assertTrue(true);
    }

    public function testFrameEdge()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->array([0,1]);
        $y = $mo->array([0,1]);
        [$fig,$ax] = $plt->subplots(3);
        //$ax[0]->setFrame(false);
        $ax[0]->setAspect('equal');
        $ax[0]->setDataAreaMargin(0);
        $ax[0]->plot($x,$y);
        $ax[1]->setAspect('equal');
        $ax[1]->setDataAreaMargin(0);
        $ax[1]->plot($mo->scale(50,$x),$mo->scale(50,$y));
        $ax[2]->plot($x,$y);
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

    public function testXticks()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->arange(5,1);
        $xlabel = ['Jan','Feb','Mar','Apr','May'];
        $data0 = $mo->array([0.1, 0.3, 0.7, 1.2, 2.8]);
        $plt->plot($x,$data0);
        $plt->xticks($x,$xlabel);
        $plt->show();

        $this->assertTrue(true);
    }

    public function testYticks()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $y = $mo->arange(6, 0.0, 0.5);
        $ylabel = ['0m','0.5m','1m','1.5m','2m','2.5m'];
        $data0 = $mo->array([0.1, 0.3, 0.7, 1.2, 2.8]);
        $plt->plot($data0);
        $plt->yticks($y,$ylabel);
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

    public function testInvalidLogScale()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $x = $mo->array([-1000,-100,-10,-1,1,10,100,1000]);
        $plt->figure('new',[465,480]);
        $plt->plot($x,$x);
        $plt->xscale('log');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('"log" scale cannot be used for negative values.');
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
        $ax[0]->setTitle('labels sample');
        $pies = $ax[1]->pie($x,null,$startangle=90,$autopct='%1.1f%%',$explodes);
        $ax[1]->legend($pies,$labels);
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

    public function testShowFixedFile()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $plt->plot($mo->arange(5));
        $plt->show(RINDOWTEST_TEMP_DIR.'/test.png');
        $this->assertTrue(true);
    }

    public function testShowMultiFixedFile()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $plt->plot($mo->arange(5));
        $plt->figure();
        $plt->plot($mo->arange(5));
        $plt->show(RINDOWTEST_TEMP_DIR.'/test.png');
        $this->assertTrue(true);
    }

    public function testMarkerPolygon()
    {
        $config = $this->getConfig();
        $mo = new MatrixOperator;
        $plt = new Plot($config,$mo);

        $sizes = $mo->array([100,200,300,400,500]);
        $plt->scatter($mo->arange(5),$mo->arange(5),$sizes,$color=null,$marker='D');
        $plt->show();
        $this->assertTrue(true);
    }
}
