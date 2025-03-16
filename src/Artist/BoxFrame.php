<?php
namespace Rindow\Math\Plot\Artist;

use LogicException;
use Interop\Polite\Math\Matrix\NDArray;
use Rindow\Math\Plot\System\Configured;
use Rindow\Math\Plot\System\Configure;

class BoxFrame
{
    use Configured;

    protected $renderer;
    protected $plotArea;
    protected $scaling;

    //Ticks
    protected $xTicks;                            // ndarray of x ticks
    protected $xTickLabels;                       // array text of x ticks
    protected $yTicks;                            // ndarray of y ticks
    protected $yTickLabels;                       // array text of y ticks
    protected $hideXTicks = false;
    protected $hideYTicks = false;
    protected $axis;

    // configure

    // Ticks
    protected $frameColor = 'black';
    protected $labelColor = 'black';
    protected $xTickPosition = 'down';          // down, up
    protected $yTickPosition = 'left';          // right, left
    protected $xTickLength = 5;                 // tick length in pixels for upper/lower axis
    protected $yTickLength = 5;                 // tick length in pixels for left/right axis

    // labels
    protected $labelFormat = [
        'x' => [],
        'logx' => ['type'=>'data','prefix'=>'10**','precision'=>0,'suffix'=>''],
        'y' => [],
        'logy' => ['type'=>'data','prefix'=>'10**','precision'=>0,'suffix'=>''],
    ];
    // Label angles: 0 or 90 degrees for fixed fonts, any for TTF
    protected $xTickLabelMargin = 2;            // x tick label margin
    protected $yTickLabelMargin = 2;            // y tick label margin
    protected $xTickLabelAngle = 0;             // For X tick labels
    protected $yTickLabelAngle = 0;             // For Y tick labels
    protected $tickLabelStandardCount = 6;
    protected $tickLabelWidth = 6;
    protected $tickLabelHeight = 4;
    protected $framePadding = 1;
    //Fonts
    protected $xTickLabelFontSize = 4;
    protected $yTickLabelFontSize = 4;

    public function __construct(
        Configure $config, $renderer,
        array $plotArea,$scaling)
    {
        $this->loadConfigure($config,
            [
                'frameColor','labelColor',
                'xTickPosition','yTickPosition','xTickLength','yTickLength',
                'xTickLabelMargin','yTickLabelMargin','xTickLabelAngle','yTickLabelAngle',
                'tickLabelStandardCount','tickLabelWidth','tickLabelHeight',
                'xTickLabelFontSize','yTickLabelFontSize','framePadding',
            ],
            'frame');
        $this->renderer = $renderer;
        $this->plotArea = $plotArea;
        $this->scaling  = $scaling;
    }

    public function setXTicks(?NDArray $ticks=null)
    {
        $this->xTicks = $ticks;
    }

    public function setXTickLabels(?array $labels=null)
    {
        $this->xTickLabels = $labels;
    }

    public function setYTicks(?NDArray $ticks=null)
    {
        $this->yTicks = $ticks;
    }

    public function setYTickLabels(?array $labels=null)
    {
        $this->yTickLabels = $labels;
    }

    public function hideXTicks(bool $hide)
    {
        $this->hideXTicks = $hide;
    }

    public function hideYTicks(bool $hide)
    {
        $this->hideYTicks = $hide;
    }

    public function setAxis($axis)
    {
        $this->axis = $axis;
    }

    public function setXTickPosition(string $position)
    {
        $this->xTickPosition = $position;
    }

    public function setYTickPosition(string $position)
    {
        $this->yTickPosition = $position;
    }

    public function setPlotArea($plotArea)
    {
        $this->plotArea = $plotArea;
    }

    public function draw()
    {
        //$this->renderer->rectangle($left,$bottom,$left+$width-1,$bottom+$height-1);
        $dvTickColor = $this->renderer->allocateColor($this->frameColor);
        $dvTextColor = $this->renderer->allocateColor($this->labelColor);

        [$left, $bottom, $width, $height] = $this->plotArea;
        $this->renderer->rectangle(
            $left-$this->framePadding,
            $bottom-$this->framePadding,
            $left+$width+$this->framePadding,
            $bottom+$height+$this->framePadding,$dvTickColor);

        $this->scaling->setTickLabelInfo([
            $this->xTickLabelAngle,
            $this->yTickLabelAngle,
            $this->tickLabelStandardCount,
            $this->tickLabelWidth,
            $this->tickLabelHeight,
        ]);

        if(!$this->hideXTicks) {
            $this->drawXTicks($dvTickColor,$dvTextColor);
        }
        if(!$this->hideYTicks) {
            $this->drawYTicks($dvTickColor,$dvTextColor);
        }
    }

    protected function drawXTicks($dvTickColor,$dvTextColor)
    {
        [$left, $bottom, $width, $height] = $this->plotArea;
        $font = $this->renderer->allocateFont($this->xTickLabelFontSize);
        $scaleType = $this->scaling->xscale();
        $format = ($scaleType=='log') ? 'logx' : 'x';
        [$py1, $py2, $pty, $halign, $valign, $pyh] = $this->calcXTick();

        if($this->xTicks) {
            // draw tick labels
            $count = $this->xTicks->size();
            for($i=0;$i<$count;$i++) {
                if($this->xTickLabels) {
                    $x = $this->xTicks[$i];
                    $label = $this->xTickLabels[$i];
                } else {
                    $x = $this->xTicks[$i];
                    $label = $this->formatLabel($format, $x, $scaleType);
                }
                if($scaleType=='log') {
                    $x = 10**$x;
                }
                $px = $this->scaling->px($x);
                $this->renderer->line($px, $py1, $px, $py2, $dvTickColor);
                $this->renderer->text($font, $px, $pty, $label, $dvTextColor,
                                        $this->xTickLabelAngle, $halign, $valign);

            }
            return;
        }

        // draw auto ticks
        [$start, $end, $delta] = $this->scaling->calcAutoTicks('x',$font);
        $n = 0;
        $x = $start;
        if($scaleType=='log') {
            $endLog = 10**$end;
        }
        while ($x <= $end) {
            $label = $this->formatLabel($format, $x, $scaleType);
            if($scaleType=='log') {
                $x = 10**$x;
            }
            $px = $this->scaling->px($x);
            $this->renderer->line($px, $py1, $px, $py2, $dvTickColor);
            $this->renderer->text($font, $px, $pty, $label, $dvTextColor,
                                    $this->xTickLabelAngle, $halign, $valign);
            if($scaleType=='log'&&$delta==1.0) {
                for($i=2;$i<10;$i++) {
                    $xLog = $x*$i;
                    if($xLog > $endLog)
                        break;
                    $px10 = $this->scaling->px($xLog);
                    $this->renderer->line($px10, $py1, $px10, $pyh, $dvTickColor);
                }
            }

            $n++;
            $x = $start + $n*$delta;
        }
    }

    protected function calcXTick()
    {
        [$left, $bottom, $width, $height] = $this->plotArea;

        if($this->xTickPosition == 'down') {
            $py1 = $bottom - $this->framePadding;
            $py2 = $py1 - $this->xTickLength;
            $pty = $py2 - $this->xTickLabelMargin;
            $pyh = $py1 - (int)($this->xTickLength/2);
            if($this->xTickLabelAngle==0) {
                $halign = 'center'; $valign = 'top';
            } else {
                $halign = 'right'; $valign = 'center';
            }
        } elseif($this->xTickPosition == 'up') {
            $py1 = $bottom + $height + $this->framePadding;
            $py2 = $py1 + $this->xTickLength;
            $pty = $py2 + $this->xTickLabelMargin;
            $pyh = $py1 + (int)($this->xTickLength/2);
            if($this->xTickLabelAngle==0) {
                $halign = 'center'; $valign = 'bottom';
            } else {
                $halign = 'left'; $valign = 'center';
            }
        } else {
            throw new LogicException('unknown xtick position: '.$this->xTickPosition);
        }
        return [$py1, $py2, $pty, $halign, $valign, $pyh];
    }
/*
    protected function drawXTick($label, $px, $font, $dvTickColor, $dvTextColor)
    {
        [$left, $bottom, $width, $height] = $this->plotArea;

        if($this->xTickPosition == 'down') {
            $py1 = $bottom;
            $py2 = $py1 - $this->xTickLength;
            $pty = $py2 - $this->xTickLabelMargin;
            if($this->xTickLabelAngle==0) {
                $halign = 'center'; $valign = 'top';
            } else {
                $halign = 'right'; $valign = 'center';
            }
        } elseif($this->xTickPosition == 'up') {
            $py1 = $bottom + $height;
            $py2 = $py1 + $this->xTickLength;
            $pty = $py2 + $this->xTickLabelMargin;
            if($this->xTickLabelAngle==0) {
                $halign = 'center'; $valign = 'bottom';
            } else {
                $halign = 'left'; $valign = 'center';
            }
        } else {
            throw new LogicException('unknown xtick position: '.$this->xTickPosition);
        }
        $this->renderer->line($px, $py1, $px, $py2, $dvTickColor);
        $this->renderer->text($font, $px, $pty, $label, $dvTextColor,
                                $this->xTickLabelAngle, $halign, $valign);
    }
*/
    protected function drawYTicks($dvTickColor,$dvTextColor)
    {
        [$left, $bottom, $width, $height] = $this->plotArea;

        $font = $this->renderer->allocateFont($this->yTickLabelFontSize);
        $scaleType = $this->scaling->yscale();
        $format = ($scaleType=='log') ? 'logy' : 'y';
        [$px1, $px2, $ptx, $halign, $valign, $pxh] = $this->calcYTick();

        if($this->yTicks) {
            // draw tick labels
            $count = $this->yTicks->size();
            for($i=0;$i<$count;$i++) {
                if($this->yTickLabels) {
                    $y = $this->yTicks[$i];
                    $label = $this->yTickLabels[$i];
                } else {
                    $y = $this->yTicks[$i];
                    $label = $this->formatLabel($format, $y, $scaleType);
                }
                $py = $this->scaling->py($y);
                $this->renderer->line($px1, $py, $px2, $py, $dvTickColor);
                $this->renderer->text($font, $ptx, $py, $label, $dvTextColor,
                                        $this->yTickLabelAngle, $halign, $valign);
            }
            return;
        }

        // draw auto ticks
        [$start, $end, $delta] = $this->scaling->calcAutoTicks('y',$font);

        if($scaleType=='log') {
            $endLog = 10**$end;
        }
        $n = 0;
        $y = $start;
        while ($y <= $end) {
            $label = $this->formatLabel($format, $y, $scaleType);
            if($scaleType=='log') {
                $y = 10**$y;
            }
            $py = $this->scaling->py($y);
            $this->renderer->line($px1, $py, $px2, $py, $dvTickColor);
            $this->renderer->text($font, $ptx, $py, $label, $dvTextColor,
                                    $this->yTickLabelAngle, $halign, $valign);
            if($scaleType=='log'&&$delta==1.0) {
                for($i=2;$i<10;$i++) {
                    $yLog = $y*$i;
                    if($yLog > $endLog)
                        break;
                    $py10 = $this->scaling->py($yLog);
                    $this->renderer->line($px1, $py10, $pxh, $py10, $dvTickColor);
                }
            }
            $n++;
            $y = $start +  $n*$delta;
        }
    }

    protected function calcYTick()
    {
        [$left, $bottom, $width, $height] = $this->plotArea;

        if($this->yTickPosition == 'left') {
            $px1 = $left - $this->framePadding;
            $px2 = $px1 - $this->yTickLength;
            $ptx = $px2 - $this->yTickLabelMargin;
            $pxh = $px1 - (int)($this->yTickLength/2);
            if($this->yTickLabelAngle==0) {
                $halign = 'right'; $valign = 'center';
            } else {
                $halign = 'center'; $valign = 'bottom';
            }
        } elseif($this->yTickPosition == 'right') {
            $px1 = $left + $width + $this->framePadding;
            $px2 = $px1 + $this->yTickLength;
            $ptx = $px2 + $this->yTickLabelMargin;
            $pxh = $px1 + (int)($this->yTickLength/2);
            if($this->yTickLabelAngle==0) {
                $halign = 'left'; $valign = 'center';
            } else {
                $halign = 'center'; $valign = 'top';
            }
        } else {
            throw new LogicException('unknown ytick position: '.$this->yTickPosition);
        }
        return [$px1, $px2, $ptx, $halign, $valign, $pxh];
    }
/*
    protected function drawYTick($label, $py, $font, $dvTickColor,$dvTextColor)
    {
        [$left, $bottom, $width, $height] = $this->plotArea;

        if($this->yTickPosition == 'left') {
            $px1 = $left;
            $px2 = $px1 - $this->yTickLength;
            $ptx = $px2 - $this->yTickLabelMargin;
            if($this->yTickLabelAngle==0) {
                $halign = 'right'; $valign = 'center';
            } else {
                $halign = 'center'; $valign = 'bottom';
            }
        } elseif($this->yTickPosition == 'right') {
            $px1 = $left + $width;
            $px2 = $px1 + $this->yTickLength;
            $ptx = $px2 + $this->yTickLabelMargin;
            if($this->yTickLabelAngle==0) {
                $halign = 'left'; $valign = 'center';
            } else {
                $halign = 'center'; $valign = 'top';
            }
        } else {
            throw new LogicException('unknown ytick position: '.$this->yTickPosition);
        }
        $this->renderer->line($px1, $py, $px2, $py, $dvTickColor);
        $this->renderer->text($font, $ptx, $py, $label, $dvTextColor,
                                $this->yTickLabelAngle, $halign, $valign);
        return [$py1, $py2, $pty, $halign, $valign, $pyh];
    }
*/

    /*
     * Formats a tick or data label.
     *    which_pos - 'x', 'xd', 'y', or 'yd', selects formatting controls.
     *        x, y are for tick labels; xd, yd are for data labels.
     *    label - String to format as a label.
     * Credits: Time formatting suggested by Marlin Viss
     *          Custom formatting suggested by zer0x333
     * Notes:
     *   Type 'title' is obsolete and retained for compatibility.
     *   Class variable 'data_units_text' is retained as a suffix for 'data' type formatting for
     *      backward compatibility. Since there was never a function/method to set it, there
     *      could be somebody out there who sets it directly in the object.
     */
    protected function formatLabel($formatId, $value, $scaleType)
    {
        if(is_callable($formatId)) {
            // custom formatter
            return $formatId($label,$scaleType);
        }

        // default formatter
        $label = '';
        switch($formatId) {
            case 'logx':
            case 'logy':
                $label = '10^';
            case 'x':
            case 'y':
                //$label .= number_format($value, 0, '.', ',');
                $label .= strval($value);
                break;
        }
        return $label;
    }
}
