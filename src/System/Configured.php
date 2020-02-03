<?php
namespace Rindow\Math\Plot\System;

use LogicException;

trait Configured
{
    public function loadConfigure(Configure $config,array $valueNames,$class=null)
    {
        if($class==null)
            $class = '';
        else
            $class = $class.'.';
        foreach($valueNames as $valueName) {
            if(!is_string($valueName))
                throw new LogicException('valueNames must be string array');
            $name = $class.$valueName;
            if($config->offsetExists($name)) {
                $pos = strrpos($valueName,'.');
                if($pos!==false) {
                    $valueName = substr($valueName,$pos+1);
                }
                $this->$valueName = $config[$name];
            }
        }
    }
}
