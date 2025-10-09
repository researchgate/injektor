<?php
namespace rg\injektor;

use rg\injektor\attributes\Inject;

class InjectionLoopDetectionTest extends \PHPUnit\Framework\TestCase {

    public function testInjectionLoopDetectionA() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $this->expectException(InjectionLoopException::class);
        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepA::class);
    }

    public function testInjectionLoopDetectionB() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $this->expectException(InjectionLoopException::class);
        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepB::class);
    }

    public function testInjectionLoopDetectionC() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $this->expectException(InjectionLoopException::class);
        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepC::class);
    }

    public function testInjectionLoopDetectionD() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $this->expectException(InjectionLoopException::class);
        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepD::class);
    }

    public function testInjectionLoopDetectionE() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $this->expectException(InjectionLoopException::class);
        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepE::class);
    }

    public function testInjectionLoopDetectionNoRecA() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $this->assertInstanceOf(InjectionLoopDetectionTest_NoRecA::class, $dic->getInstanceOfClass(InjectionLoopDetectionTest_NoRecA::class));
    }
}

class InjectionLoopDetectionTest_DepA {

    /**
     * @param InjectionLoopDetectionTest_DepB $dep
     */
    #[Inject]
    public function __construct(InjectionLoopDetectionTest_DepB $dep) {

    }
}

class InjectionLoopDetectionTest_DepB {

    /**
     * @param InjectionLoopDetectionTest_DepC $dep
     */
    #[Inject]
    public function __construct(InjectionLoopDetectionTest_DepC $dep) {

    }
}

class InjectionLoopDetectionTest_DepC {

    /**
     * @param InjectionLoopDetectionTest_DepA $dep
     */
    #[Inject]
    public function __construct(InjectionLoopDetectionTest_DepA $dep) {

    }
}

class InjectionLoopDetectionTest_DepD {

    /**
     * @param InjectionLoopDetectionTest_DepD $dep
     */
    #[Inject]
    public function __construct(InjectionLoopDetectionTest_DepD $dep) {

    }
}

class InjectionLoopDetectionTest_DepE {

    /**
     * @param InjectionLoopDetectionTest_DepD $dep
     */
    #[Inject]
    public function __construct(InjectionLoopDetectionTest_DepD $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecA {

    /**
     * @param InjectionLoopDetectionTest_NoRecB $dep
     * @param InjectionLoopDetectionTest_NoRecC $dep2
     */
    #[Inject]
    public function __construct(
        InjectionLoopDetectionTest_NoRecC $dep3,
         InjectionLoopDetectionTest_NoRecBA $dep2,
        InjectionLoopDetectionTest_NoRecB $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecBA {

    /**
     * @param InjectionLoopDetectionTest_NoRecB $dep
     */
    #[Inject]
    public function __construct(InjectionLoopDetectionTest_NoRecB $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecB {

    /**
     * @param InjectionLoopDetectionTest_NoRecD $dep
     */
    #[Inject]
    public function __construct(InjectionLoopDetectionTest_NoRecD $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecC {

    /**
     * @param InjectionLoopDetectionTest_NoRecD $dep
     */
    #[Inject]
    public function __construct(InjectionLoopDetectionTest_NoRecD $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecD {

    #[Inject]
    public function __construct() {

    }
}
