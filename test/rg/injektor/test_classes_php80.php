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
    use rg\injektor\attributes\Param;

    require_once 'test_classes.php';

    class DICTestClassWithUnionTypedProperties {

        #[Inject]
        public DICTestClassOne|MockObject $one;

        #[Inject]
        public \rg\injektor\DICTestClassTwo|MockObject $two;

        #[Inject]
        public DICTestClassThree|MockObject|null $three;
    }

    class DICTestClassWithPromotedProperties {

        public function __construct(
            #[Inject(overwriteParams: [
                new Param(name: 'one', value: 'foo'),
                new Param(name: 'two', value: 'bar'),
            ])]
            private readonly DICProvidedTestClassNoTypeHint $withOverwriteParams,
            #[Inject(named: 'implOne')]
            private readonly DICTestAnnotatedInterface $withNamed,
            #[Inject]
            private readonly DICTestClassOne $plain,
            #[Inject]
            public readonly DICTestClassTwo $publicPlain,
        ) {
        }

        public function getWithOverwriteParams(): DICProvidedTestClassNoTypeHint {
            return $this->withOverwriteParams;
        }

        public function getWithNamed(): DICTestAnnotatedInterface {
            return $this->withNamed;
        }

        public function getPlain(): DICTestClassOne {
            return $this->plain;
        }
    }
}
