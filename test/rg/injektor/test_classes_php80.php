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

    use PHPUnit\Framework\MockObject\MockObject;
    use rg\injektor\attributes\Inject;

    require_once 'test_classes.php';

    class DICTestClassWithUnionTypedProperties {

        #[Inject]
        public DICTestClassOne|MockObject $one;

        #[Inject]
        public \rg\injektor\DICTestClassTwo|MockObject $two;

        #[Inject]
        public DICTestClassThree|MockObject|null $three;
    }
}
