<?php

namespace Sibcem\Processes;

abstract class BaseProcess
{
    abstract public static function getName();

    abstract public static function run();
}