<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
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

