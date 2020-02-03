<?php
namespace Rindow\Math\Plot\Artist;

interface DataArtist
{
    public function calcDataLimit() : array;
    public function draw(OverlapChecker $checkOverlap=null);
    public function drawLegend($x,$y,$length);
    public function getLabel() : ?string;
    public function setLabel(?string $label);
}
