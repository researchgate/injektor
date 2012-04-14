<?php
/*
 * This file is part of rg\injection.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injection;

class WritingFactoryGenerator extends FactoryGenerator {

    /**
     * @param string $fullClassName
     */
    public function processFileForClass($fullClassName) {
        $file = $this->generateFileForClass($fullClassName);
        if ($file) {
            $file->write();
        }
    }

    public static function cleanUpGenerationDirectory($path) {
        $files = glob($path . DIRECTORY_SEPARATOR . '*.php');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}