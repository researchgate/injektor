<?php
namespace rg\injektor;

class InjectionLoopDetectionTest extends \PHPUnit\Framework\TestCase {

    /**
     * @expectedException \rg\injektor\InjectionLoopException
     */
    public function testInjectionLoopDetectionA() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepA::class);
    }

    /**
     * @expectedException \rg\injektor\InjectionLoopException
     */
    public function testInjectionLoopDetectionB() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepB::class);
    }

    /**
     * @expectedException \rg\injektor\InjectionLoopException
     */
    public function testInjectionLoopDetectionC() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepC::class);
    }

    /**
     * @expectedException \rg\injektor\InjectionLoopException
     */
    public function testInjectionLoopDetectionD() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

        $dic->getInstanceOfClass(InjectionLoopDetectionTest_DepD::class);
    }

    /**
     * @expectedException \rg\injektor\InjectionLoopException
     */
    public function testInjectionLoopDetectionE() {
        $config = new Configuration();
        $dic = new DependencyInjectionContainer($config);

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
     * @inject
     * @param InjectionLoopDetectionTest_DepB $dep
     */
    public function __construct(InjectionLoopDetectionTest_DepB $dep) {

    }
}

class InjectionLoopDetectionTest_DepB {

    /**
     * @inject
     * @param InjectionLoopDetectionTest_DepC $dep
     */
    public function __construct(InjectionLoopDetectionTest_DepC $dep) {

    }
}

class InjectionLoopDetectionTest_DepC {

    /**
     * @inject
     * @param InjectionLoopDetectionTest_DepA $dep
     */
    public function __construct(InjectionLoopDetectionTest_DepA $dep) {

    }
}

class InjectionLoopDetectionTest_DepD {

    /**
     * @inject
     * @param InjectionLoopDetectionTest_DepD $dep
     */
    public function __construct(InjectionLoopDetectionTest_DepD $dep) {

    }
}

class InjectionLoopDetectionTest_DepE {

    /**
     * @inject
     * @param InjectionLoopDetectionTest_DepD $dep
     */
    public function __construct(InjectionLoopDetectionTest_DepD $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecA {

    /**
     * @inject
     * @param InjectionLoopDetectionTest_NoRecB $dep
     * @param InjectionLoopDetectionTest_NoRecC $dep2
     */
    public function __construct(
        InjectionLoopDetectionTest_NoRecC $dep3,
         InjectionLoopDetectionTest_NoRecBA $dep2,
        InjectionLoopDetectionTest_NoRecB $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecBA {

    /**
     * @inject
     * @param InjectionLoopDetectionTest_NoRecB $dep
     */
    public function __construct(InjectionLoopDetectionTest_NoRecB $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecB {

    /**
     * @inject
     * @param InjectionLoopDetectionTest_NoRecD $dep
     */
    public function __construct(InjectionLoopDetectionTest_NoRecD $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecC {

    /**
     * @inject
     * @param InjectionLoopDetectionTest_NoRecD $dep
     */
    public function __construct(InjectionLoopDetectionTest_NoRecD $dep) {

    }
}

class InjectionLoopDetectionTest_NoRecD {

    /**
     * @inject
     */
    public function __construct() {

    }
}
