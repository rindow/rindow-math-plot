<?php
namespace Rindow\Math\Plot\Artist;

use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;

class Marker implements DataArtist
{
    use Configured;

    protected $renderer;
    protected $mo;
    protected $scaling;
    protected $x;
    protected $y;
    protected $color;
    protected $size;
    protected $label;

    // configure
    protected $markerSize = 8;
    protected $markerStyle = 'dot';

    public function __construct(
        Configure $config=null, $renderer=null, $mo=null, $scaling=null,
        NDArray $x=null,NDArray $y=null,NDArray $size=null,
        $color=null,$marker=null,$label=null)
    {
        if($config) {
            $this->loadConfigure($config,
                ['markerSize','markerStyle'],
                'marker');
        }
        $this->renderer = $renderer;
        $this->mo = $mo;
        $this->scaling = $scaling;
        $this->x = $x;
        $this->y = $y;
        $this->size = $size;
        $this->color = $color;
        if($marker!==null) {
            $this->markerStyle = $marker;
        }
        $this->label = $label;
    }

    public function calcDataLimit() : array
    {
        $minX = $this->mo->min($this->x);
        $minY = $this->mo->min($this->y);
        $maxX = $this->mo->max($this->x);
        $maxY = $this->mo->max($this->y);
        return [$minX,$minY,$maxX,$maxY];
    }

    public function draw(OverlapChecker $checkOverlap=null)
    {
        //[$scaleX,$offsetX,$scaleY,$offsetY] = $scaling;
        $color = $this->renderer->allocateColor($this->color);
        $count = $this->x->size();
        $origThickness = $this->renderer->getThickness();
        $this->renderer->setThickness(1);
        if($checkOverlap) {
            $handle = $checkOverlap->newOverlapCheckHandle([$this,'checkOverlap']);
        }
        for($i=0;$i<$count;$i++) {
            if($this->size) {
                $size = $this->size[$i];
                if($size>0) {
                    $size = (int)sqrt($size)+3;
                }
            } else {
                $size = $this->markerSize;
            }
            if($size>0) {
                [$px,$py] = $this->scaling->pixels(
                    [$this->x[$i],$this->y[$i]]
                );
                $this->doDrawDot($px, $py, $this->markerStyle, $size, $color);
                if($checkOverlap) {
                    $checkOverlap->checkOverlap($handle,[$px,$py,$size]);
                }
            }
        }
        if($checkOverlap) {
            $checkOverlap->commitOverlap($handle);
        }
        $this->renderer->setThickness($origThickness);
    }

    public function checkOverlap($rx1,$ry1,$rx2,$ry2,$data)
    {
        [$px,$py,$size] = $data;
        $halfSize = $size/2;
        $px1 = $px-$halfSize;
        $px2 = $px+$halfSize;
        $py1 = $py-$halfSize;
        $py2 = $py+$halfSize;

        if($px2<$rx1 || $rx2<$px1 || $py2<$ry1 || $ry2<$py1) {
            return false;
        }
        return true;
    }

    public function drawLegend($x,$y,$length)
    {
        $color = $this->renderer->allocateColor($this->color);
        $origThickness = $this->renderer->getThickness();
        $this->renderer->setThickness(1);
        $this->doDrawDot(
            $x+(int)($length/2), $y,
            $this->markerStyle, $this->markerSize, $color);
        $this->renderer->setThickness($origThickness);
    }

    /*
     * Draws a styled dot. Uses world coordinates.
     * The list of supported shapes can also be found in SetPointShapes().
     * All shapes are drawn using a 3x3 grid, centered on the data point.
     * The center is (x_mid, y_mid) and the corners are (x1, y1) and (x2, y2).
     *   $record is the 0-based index that selects the shape and size.
     */
    public function doDrawDot($x, $y, $style, $size, $color)
    {
        $half_size = (int)($size / 2);

        $x_mid = $x;
        $y_mid = $y;
        $x1 = $x_mid - $half_size;
        $x2 = $x_mid + $half_size;
        $y1 = $y_mid - $half_size;
        $y2 = $y_mid + $half_size;

        switch($style) {
            case 'halfline':
                $this->renderer->line($x1, $y_mid, $x_mid, $y_mid, $color);
                break;
            case 'line':
                $this->renderer->line($x1, $y_mid, $x2, $y_mid, $color);
                break;
            case 'vertical':
                $this->renderer->line($x_mid, $y1, $x_mid, $y2, $color);
                break;
            case 'plus':
                $this->renderer->line($x1, $y_mid, $x2, $y_mid, $color);
                $this->renderer->line($x_mid, $y1, $x_mid, $y2, $color);
                break;
            case 'cross':
                $this->renderer->line($x1, $y1, $x2, $y2, $color);
                $this->renderer->line($x1, $y2, $x2, $y1, $color);
                break;
            case 'circle':
                $this->renderer->ellipse($x_mid, $y_mid, $size, $size, $color);
                break;
            case 'dot':
                $this->renderer->filledEllipse($x_mid, $y_mid, $size, $size, $color);
                break;
            case 'smalldot':
                $this->renderer->filledEllipse($x_mid, $y_mid, $half_size+1, $half_size+1, $color);
                break;
            case 'pixel':
                $this->renderer->point($x_mid, $y_mid, $color);
                break;
            case 'diamond':
                $arrpoints = [[$x1, $y_mid], [$x_mid, $y1], [$x2, $y_mid], [$x_mid, $y2]];
                $this->renderer->filledPolygon($arrpoints, $color);
                break;
            case 'triangle':
                $arrpoints = [[$x1, $y_mid], [$x2, $y_mid], [$x_mid, $y2]];
                $this->renderer->filledPolygon($arrpoints, $color);
                break;
            case 'trianglemid':
                $arrpoints =[[$x1, $y1], [$x2, $y1], [$x_mid, $y_mid]];
                $this->renderer->filledPolygon($arrpoints, $color);
                break;
            case 'yield':
                $arrpoints = [[$x1, $y1], [$x2, $y1], [$x_mid, $y2]];
                $this->renderer->filledPolygon($arrpoints, $color);
                break;
            case 'delta':
                $arrpoints = [[$x1, $y2], [$x2, $y2], [$x_mid, $y1]];
                $this->renderer->filledPolygon($arrpoints, $color);
                break;
            case 'star':
                $this->renderer->line($x1, $y_mid, $x2, $y_mid, $color);
                $this->renderer->line($x_mid, $y1, $x_mid, $y2, $color);
                $this->renderer->line($x1, $y1, $x2, $y2, $color);
                $this->renderer->line($x1, $y2, $x2, $y1, $color);
                break;
            case 'hourglass':
                $arrpoints = [[$x1, $y1], [$x2, $y1], [$x1, $y2], [$x2, $y2]];
                $this->renderer->filledPolygon($arrpoints, $color);
                break;
            case 'bowtie':
                $arrpoints = [[$x1, $y1], [$x1, $y2], [$x2, $y1], [$x2, $y2]];
                $this->renderer->filledPolygon($arrpoints, $color);
                break;
            case 'target':
                $this->renderer->filledRectangle($x1, $y1, $x_mid, $y_mid, $color);
                $this->renderer->filledRectangle($x_mid, $y_mid, $x2, $y2, $color);
                $this->renderer->rectangle($x1, $y1, $x2, $y2, $color);
                break;
            case 'box':
                $this->renderer->rectangle($x1, $y1, $x2, $y2, $color);
                break;
            case 'square':
                $this->renderer->filledRectangle($x1, $y1, $x2, $y2, $color);
                break;
            case 'home': /* As in: "home plate" (baseball), also looks sort of like a house. */
                $arrpoints = [[$x1, $y2], [$x2, $y2], [$x2, $y_mid], [$x_mid, $y1], [$x1, $y_mid]];
                $this->renderer->filledPolygon($arrpoints, $color);
                break;
            case 'up':
                $arrpoints = [[$x_mid, $y1], [$x2, $y2], [$x1, $y2]];
                $this->renderer->polygon($arrpoints, $color);
                break;
            case 'down':
                $arrpoints = [[$x_mid, $y2], [$x1, $y1], [$x2, $y1]];
                $this->renderer->polygon($arrpoints, $color);
                break;
            case 'none': /* Special case, no point shape here */
                break;
            default: /* Also 'rect' */
                $this->renderer->filledRectangle($x1, $y1, $x2, $y2, $color);
                break;
        }
        return TRUE;
    }

    public function getDataX()
    {
        return $this->x;
    }

    public function getDataY()
    {
        return $this->y;
    }

    public function getDataSize()
    {
        return $this->size;
    }

    public function getLabel() : ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label)
    {
        $this->label = $label;
    }
}
