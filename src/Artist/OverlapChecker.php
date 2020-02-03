<?php
namespace Rindow\Math\Plot\Artist;

interface OverlapChecker
{
    public function newOverlapCheckHandle(callable $function) : object;
    public function checkOverlap($handle,$data) : void;
    public function commitOverlap($handle) : void;
}
