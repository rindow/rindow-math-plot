<?php
namespace Rindow\Math\Plot\Artist;

interface Mappable
{
    public function colormap();
    public function colorRange() : array;
}
