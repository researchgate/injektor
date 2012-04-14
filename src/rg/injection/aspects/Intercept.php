<?php
namespace rg\injection\aspects;

interface Intercept {
    public function execute($aspectArguments, $className, $functionName, $functionArguments, $lastResult);
}