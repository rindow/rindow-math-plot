<?php
namespace Rindow\Math\Plot\System;

class CmapManager
{
    protected $colormaps = [];

    public function get(string $name)
    {
        if(isset($this->colormaps[$name])) {
            return $this->colormaps[$name];
        }

        $this->colormaps[$name] = new Colormap($name);
        return $this->colormaps[$name];
    }
}
