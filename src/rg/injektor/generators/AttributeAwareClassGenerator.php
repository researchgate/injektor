<?php
namespace rg\injektor\generators;
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Laminas\Code\Generator\ClassGenerator;

class AttributeAwareClassGenerator extends ClassGenerator {

    /**
     * @var string[]
     */
    private array $attributes = [];

    /**
     * @param string[] $attributes
     */
    public function setAttributes(array $attributes): void {
        $this->attributes = $attributes;
    }

    public function generate() {
        $output = parent::generate();
        if (!$this->attributes) {
            return $output;
        }

        return implode(self::LINE_FEED, $this->attributes) . self::LINE_FEED . $output;
    }
}
