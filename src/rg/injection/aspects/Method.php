<?php
namespace rg\injection\aspects;

class Method implements Before {

    public function execute($aspectArguments, $className, $functionName, $functionArguments) {
        if (! isset($aspectArguments['method'])) {
            throw new \Exception('method parameter not found');
        }

        $allowedHttpMethod = $aspectArguments['method'];
        if (strtolower($allowedHttpMethod) !== strtolower($_SERVER['REQUEST_METHOD'])) {
            throw new \RuntimeException('invalid http method ' . $_SERVER['REQUEST_METHOD'] . ' for ' . $className . '::' . $functionName . '(), ' . $allowedHttpMethod . ' expected');
        }

        return $functionArguments;
    }
}

