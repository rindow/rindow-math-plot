<?php
namespace Rindow\Math\Plot\Renderer;

use RuntimeException;
use InvalidArgumentException;

class GDDriver
{
    protected $bottomOrigin = true;
    protected $image;
    protected $filename;
    protected $width;
    protected $height;
    protected $yMax;
    protected $color;
    protected $textDriver;
    protected $colorNames;
    protected $thickness = 1;
    protected $lastLineStyle;
    protected $lastLineStyleColor;
    protected $viewer = 'RINDOW_MATH_PLOT_VIEWER';
    protected $skipRunViewer = false;
    protected $execBackground = false;
    protected $mkdir = false;
    protected $php80 = false;

    public function __construct(
        bool $bottomOrigin=null,
        $image=null,
        string $filename=null,
        bool $skipCleaning=null,
        bool $skipRunViewer=null,
        bool $execBackground=null)
    {
        $this->php80 = (version_compare(phpversion(),'8.1.0')<0);
        if($bottomOrigin !== null) {
            $this->bottomOrigin = $bottomOrigin;
        }
        if($image) {
            $this->setImageContext($image);
        }
        if($filename==null) {
            $filename = sys_get_temp_dir().'/rindow/mathplot';
        }
        if($skipRunViewer!==null) {
            $this->skipRunViewer = $skipRunViewer;
        }
        if($execBackground) {
            $this->execBackground = $execBackground;
        }
        $this->setTempFile($filename);
        $this->colorNames = require __DIR__.'/rgb.inc.php';

        //$this->SetDefaultStyles();
        //$this->SetDefaultFonts();
    }

    public function setTempFile($filename)
    {
        $this->filename = $filename;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function setImageContext($image)
    {
        $this->image = $image;
    }

    protected function newTextDriver($image)
    {
        return new GDTextDriver($this->bottomOrigin,$image);
    }

    protected function phy($y)
    {
        if(!$this->bottomOrigin) {
            return $y;
        }
        return $this->yMax-$y;
    }

    protected function phypoints(array $points)
    {
        if(!$this->bottomOrigin) {
            $newPoints = [];
            foreach ($points as $point) {
                [$x,$y] = $point;
                $newPoints[] = $x;
                $newPoints[] = $y;
            }
            return $newPoints;
        }
        $newPoints = [];
        foreach ($points as $point) {
            [$x,$y] = $point;
            $newPoints[] = $x;
            $newPoints[] = $this->yMax-$y;
        }
        return $newPoints;
    }

    public function open($width, $height)
    {
        $this->image = imagecreatetruecolor($width, $height);
        if(!$this->image) {
            throw new RuntimeException('Could not create image resource.');
        }
        $this->width = $width;
        $this->height = $height;
        $this->yMax = $height-1;
        $this->setColor($this->allocateColor([0,0,0,0]));
        if($this->textDriver) {
            $this->textDriver->setImage($this->image);
        } else {
            $this->textDriver = $this->newTextDriver($this->image);
        }

        $this->thickness = 1;
        $this->lastLineStyle = null;
        $this->lastLineStyleColor = null;
        return $this->image;
    }

    public function close()
    {
        imagedestroy($this->image);
    }

    public function point($x, $y, $color=null)
    {
        if($color===null)
            $color = $this->color;
        imagesetpixel($this->image,
            $x, $this->phy($y),
            $color);
    }

    public function line($x1, $y1, $x2, $y2, $color=null, string $style=null)
    {
        if($color===null) {
            $color = $this->color;
        }
        if($style) {
            if($this->lastLineStyle!==$style ||
                $this->lastLineStyleColor!==$color) {
                $this->doSetLineStyle($style,$color);
                $this->lastLineStyle=$style;
                $this->lastLineStyleColor=$color;
            }
            $color = IMG_COLOR_STYLED;
        }
        $rc = imageline($this->image,
            $x1, $this->phy($y1),
            $x2, $this->phy($y2),
            $color);
        return $rc;
    }

    public function filledRectangle($x1, $y1, $x2, $y2, $color=null)
    {
        if($color===null)
            $color = $this->color;
        imagefilledrectangle($this->image,
            $x1, $this->phy($y2),
            $x2, $this->phy($y1),
            $color);
    }

    public function rectangle($x1, $y1, $x2, $y2, $color=null)
    {
        if($color===null)
            $color = $this->color;
        imagerectangle($this->image,
            $x1, $this->phy($y2),
            $x2, $this->phy($y1),
            $color);
    }

    public function filledEllipse($x, $y, $width, $height, $color=null)
    {
        if($color===null)
            $color = $this->color;
        imagefilledellipse(
            $this->image, $x, $this->phy($y),
            $width, $height,
            $color);
    }

    public function ellipse($x, $y, $width, $height, $color=null)
    {
        if($color===null)
            $color = $this->color;
        imageellipse($this->image,
            $x, $this->phy($y),
            $width, $height,
            $color);
    }

    public function filledArc($x, $y, $width, $height, $start, $end, $color=null, $style=null)
    {
        if($color===null)
            $color = $this->color;
        if($style===null)
            $style = IMG_ARC_PIE;
        imagefilledarc(
            $this->image, $x, $this->phy($y),
            $width, $height,
            $start, $end,
            $color, $style);
    }

    public function filledPolygon(array $points, $color=null)
    {
        if($color===null)
            $color = $this->color;
        $count = count($points);
        $points = $this->phypoints($points);
        if($this->php80) {
            imagefilledpolygon($this->image,
            $points, $count,
            $color);
        } else {
            imagefilledpolygon($this->image,
            $points,
            $color);
        }
    }

    public function polygon(array $points, $color=null)
    {
        if($color===null)
            $color = $this->color;
        $count = count($points);
        $points = $this->phypoints($points);
        if($this->php80) {
            imagepolygon($this->image,
            $points, $count,
            $color);
        } else {
            imagepolygon($this->image,
            $points,
            $color);
        }
    }

    public function allocateColor($color)
    {
        if(is_string($color)) {
            if(isset($this->colorNames[$color])) {
                $color = $this->colorNames[$color];
            } else {
                throw new InvalidArgumentException("Color name is not found: ".$color);
            }
        } elseif(!is_array($color)) {
            throw new InvalidArgumentException("color must be array or string");
        }
        if(count($color)==3) {
            [$red, $green, $blue] = $color;
            return imagecolorresolve($this->image,$red, $green, $blue);
        } elseif(count($color)==4) {
            [$red, $green, $blue, $alpha] = $color;
            return imagecolorresolvealpha($this->image,$red, $green, $blue, $alpha);
        } else {
            throw new InvalidArgumentException("color array must have tree or four vaule.");
        }
    }

    public function allocateFont($size=null)
    {
        return $this->textDriver->allocateFont($size);
    }

    public function text(GDFont $font, int $xpos, int $ypos, string $text, $color,
            $angle=null, string $halign=null, string $valign=null)
    {
        return $this->textDriver->text($font, $xpos, $ypos, $text, $color,
                $angle, $halign, $valign);
    }

    public function textSize(GDFont $font, int $xpos, int $ypos, string $text,
            $angle=null, string $halign=null, string $valign=null)
    {
        return $this->textDriver->textSize($font, $xpos, $ypos, $text,
                $angle, $halign, $valign);
    }

    public function show($filename=null)
    {
        if(PHP_SAPI!='cli') {
            imagepng($this->image);
            return;
        }
        if($filename==null) {
            $this->makeDirectory();
            $filename = tempnam($this->filename,'plo');
            rename($filename, $filename.'.png');
            $filename = $filename.'.png';
        }
        imagepng($this->image,$filename);
        if($viewer = getenv($this->viewer)) {
            $filename = '"'.$viewer.'" '.$filename;
        }
        if(!$this->skipRunViewer) {
            if(PHP_OS=='Linux' && $this->execBackground) {
                $filename = $filename.' > /dev/null &';
            }
            system($filename);
        }
    }

    protected function makeDirectory()
    {
        if($this->mkdir)
            return;
        if(!file_exists($this->filename)) {
            @mkdir($this->filename,0777,true);
        }
        $this->mkdir = true;
    }

    public function cleanUp()
    {
        $this->deleteTempfiles('plo');
    }

    protected function deleteTempfiles($prefix)
    {
        $this->makeDirectory();
        if(($d=opendir($this->filename))==false)
            return;
        $pattern = '/^'.$prefix.'.*\\.png$/';
        while ($filename=readdir($d)) {
            if(is_file($this->filename.'/'.$filename) &&
                    preg_match($pattern,$filename)) {
                unlink($this->filename.'/'.$filename);
            }
        }
        closedir($d);
    }

    public function setThickness(int $thickness)
    {
        $this->thickness = $thickness;
        imagesetthickness($this->image,$thickness);
    }

    public function getThickness()
    {
        return $this->thickness;
    }

    protected function doSetLineStyle(string $style,int $color)
    {
        $count = strlen($style);
        $array = [];
        for($i=0;$i<$count;$i++) {
            $c = $style[$i];
            if($c=='.') {
                $array = array_merge($array,array_fill(0,$this->thickness**2,$color));
                $array = array_merge($array,array_fill(0,$this->thickness**2,IMG_COLOR_TRANSPARENT));
            } elseif($c=='-') {
                $array = array_merge($array,array_fill(0,($this->thickness**2)*4,$color));
                $array = array_merge($array,array_fill(0,$this->thickness**2,IMG_COLOR_TRANSPARENT));
            } else {
                throw new InvalidArgumentException('Invalid style character: '.$c);
            }
        }
        imagesetstyle($this->image,$array);
    }

    public function setClip(int $x1,int $y1,int $x2,int $y2)
    {
        if($x1<0)
            $x1 = 0;
        if($y1<0)
            $y1 = 0;
        if($x2<0)
            $x2 = $this->width-1;
        if($y2<0)
            $y2 = $this->height-1;
        $y1 = $this->phy($y1);
        $y2 = $this->phy($y2);
        if($y1>$y2)
            [$y1,$y2] = [$y2,$y1];
        imagesetclip($this->image,$x1,$y1,$x2,$y2);
    }
}
