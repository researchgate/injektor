<?php
/*
 * This file is part of rg\injektor.
 *
 * (c) ResearchGate GmbH <bastian.hofmann@researchgate.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace rg\injektor {

    require_once 'test_classes.php';

    class DICTestClassWithTypedProperties {

        /**
         * @inject
         */
        public DICTestClassOne $one;

        /**
         * @inject
         */
        public \rg\injektor\DICTestClassTwo $two;

        /**
         * @inject
         */
        public ?DICTestClassThree $three;
    }
}
