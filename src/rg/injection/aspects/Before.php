<?php
namespace rg\injection\aspects;

interface Before {
    public function execute($aspectArguments, $className, $functionName, $functionArguments);
}