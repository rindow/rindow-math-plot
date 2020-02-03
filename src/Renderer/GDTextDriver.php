<?php
namespace Rindow\Math\Plot\Renderer;

use InvalidArgumentException;

use LogicException;

class GDTextDriver
{
    protected $bottomOrigin;
    protected $defaultFontSize = 2;

    protected $fonts = [];
    protected $line_spacing;
    protected $image;
    protected $imageYMax;

    public function __construct($bottomOrigin,$image = null)
    {
        $this->bottomOrigin = $bottomOrigin;
        if($image)
            $this->setImage($image);
    }

    public function setImage($image)
    {
        $this->image = $image;
        $this->imageYMax = imagesy($image)-1;
    }

    protected function phy($y)
    {
        if(!$this->bottomOrigin) {
            return $y;
        }
        return $this->imageYMax-$y;
    }

    public function getDefaultFont()
    {
        if(isset($this->fonts['default'])) {
            return $this->fonts['default'];
        }
        $this->fonts['default'] = new GDFont($this->defaultFontSize);
        return $this->fonts['default'];
    }

    public function allocateFont($size=null)
    {
        if($size===null) {
            return $this->getDefaultFont();
        }
        return new GDFont($size);
    }

    public function text(GDFont $font, $xpos, $ypos, $text, $color,
            $angle=null, $halign=null, $valign=null)
    {
        [$x, $y, $width, $height] = $this->textSize($font, $xpos, $ypos, $text,
                           $angle, $halign, $valign);
        if($angle==0) {
            if($this->bottomOrigin) {
                $phy = $this->phy($y)-$height;
            } else {
                $phy = $this->phy($y);
            }
            imagestring($this->image, $font->getFontNumber(),
                        $x, $phy, $text, $color);
        } elseif($angle==90) {
            if($this->bottomOrigin) {
                $phy = $this->phy($y);
            } else {
                $phy = $this->phy($y)+$height;
            }
            imagestringup($this->image, $font->getFontNumber(),
                        $x, $phy, $text, $color);
        } else {
            throw new InvalidArgumentException('angle must be 0 or 90');
        }
    }

    public function textSize(GDFont $font, $xpos, $ypos, $text,
            $angle=null, $halign=null, $valign=null)
    {
        if($angle===null)
            $angle = 0;
        if($halign===null)
            $halign = 'left';
        if($valign===null)
            $valign = 'bottom';

        $length = strlen($text);
        $width = $font->width() * $length;
        $height = $font->height();
        if($angle==0) {
            if($halign=='left') {
                $x = $xpos;
            } elseif($halign=='center') {
                $x = $xpos - (int)floor($width/2);
            } elseif($halign=='right') {
                $x = $xpos - $width;
            } else {
                throw new InvalidArgumentException('invalid h-align: '.$halign);
            }
            if($valign=='bottom') {
                if($this->bottomOrigin) {
                    $y = $ypos;
                } else {
                    $y = $ypos - $height;
                }
            } elseif($valign=='center') {
                if($this->bottomOrigin) {
                    $y = $ypos - (int)floor($height/2);
                } else {
                    $y = $ypos - (int)floor($height/2);
                }
            } elseif($valign=='top') {
                if($this->bottomOrigin) {
                    $y = $ypos - $height;
                } else {
                    $y = $ypos;
                }
            } else {
                throw new InvalidArgumentException('invalid v-align: '.$valign);
            }
            return [$x,$y,$width,$height];
        } else {
            if($halign=='left') {
                if($this->bottomOrigin) {
                    $y = $ypos;
                } else {
                    $y = $ypos - $width;
                }
            } elseif($halign=='center') {
                if($this->bottomOrigin) {
                    $y = $ypos - (int)floor($width/2);
                } else {
                    $y = $ypos - (int)floor($width/2);
                }
            } elseif($halign=='right') {
                if($this->bottomOrigin) {
                    $y = $ypos - $width;
                } else {
                    $y = $ypos;
                }
            } else {
                throw new InvalidArgumentException('invalid h-align: '.$halign);
            }
            if($valign=='top') {
                $x = $xpos;
            } elseif($valign=='center') {
                $x = $xpos - (int)floor($height/2);
            } elseif($valign=='bottom') {
                $x = $xpos - $height;
            } else {
                throw new InvalidArgumentException('invalid v-align: '.$valign);
            }
            return [$x,$y,$height,$width];
        }
    }
}
