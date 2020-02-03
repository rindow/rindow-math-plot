<?php
namespace Rindow\Math\Plot\Artist;

use Rindow\Math\Plot\System\Configure;
use Rindow\Math\Plot\System\Configured;

abstract class AbstractAxisLabel
{
    use Configured;

    protected $renderer;
    protected $plotArea;
    protected $text;

    // configure
    protected $configClass;
    protected $position;
    protected $rotate;
    protected $fontSize;
    protected $color;
    protected $margin;

    public function __construct(Configure $config,$renderer,array $plotArea,string $text=null)
    {
        $this->loadConfigure($config,
            ['position','rotate','fontSize','color','margin'],
            $this->configClass
        );
        $this->renderer = $renderer;
        $this->plotArea = $plotArea;
        $this->text = $text;
    }

    /*
     * Draws the X-Axis Label
     */
    public function draw()
    {
        if (!$this->text)
            return;
        [$left, $bottom, $width, $height] = $this->plotArea;

        // Center of the plot
        $color = $this->color ? $this->renderer->allocateColor($this->color) : null;

        $font = $this->renderer->allocateFont($this->fontSize);

        // Upper title
        switch($this->position) {
            case 'up':
                $xpos =  $left+$width/2;
                $ypos = $bottom + $height + $this->margin;
                $halign = 'center';
                $valign = 'bottom';
                break;
            case 'down':
                $xpos =  $left+$width/2;
                $ypos = $bottom - $this->margin;
                $halign = 'center';
                $valign = 'top';
                break;
            case 'left':
                $xpos = $left - $this->margin;
                $ypos =  $bottom + $height/2;
                $halign = 'center';
                $valign = 'bottom';
                break;
            case 'right':
                $xpos = $left - $this->margin;
                $ypos =  $bottom + $height/2;
                $halign = 'center';
                $valign = 'top';
                break;
            default:
                throw new LogicException('invalid axis label position: '.$this->position);
        }
        $this->renderer->text($font, $xpos, $ypos, $this->text,
                         $color, $this->rotate, $halign, $valign);
    }
}
