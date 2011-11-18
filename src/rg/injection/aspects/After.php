<?php
namespace rg\injection\aspects;

interface After {
    public function execute($aspectArguments, $className, $functionName, $response);
}