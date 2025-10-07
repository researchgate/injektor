<?php
/**
 * test_classes_issue.php
 *
 * @category
 * @author Johannes Brinksmeier <johannes.brinksmeier@googlemail.com>
 * @version $Id: $
 */
namespace issue  {

    use rg\injektor\attributes\Inject;

    interface Class_With_Underscores {

    }

    class SomeClass implements Class_With_Underscores {

    }

    class ClassWithDependencyToClassWithUnderscores {

        /**
         * @var \issue\Class_With_Underscores
         */
        #[Inject]
        protected $dependency;

        /**
         * @param \issue\Class_With_Underscores $dependency
         * @return ClassWithDependencyToClassWithUnderscores
         */
        public function setDependency($dependency)
        {
            $this->dependency = $dependency;

            return $this;
        }

        /**
         * @return \issue\Class_With_Underscores
         */
        public function getDependency()
        {
            return $this->dependency;
        }


    }
}

namespace issue9\name {

    use rg\injektor\attributes\Inject;

    /**
     * @implementedBy default issue9\name\D
     * @implementedBy abc issue9\name\C
     * @implementedBy abd issue9\name\D
     */
    interface B {

    }

    class C implements B {

    }
    class D implements B {

    }

    class A {

        /**
         * @var B
         */
        #[Inject(named: 'abc')]
        protected $myB;
    }
}

namespace issueImplementedByOrder\name {

    /**
     * @implementedBy abc issueImplementedByOrder\name\C
     * @implementedBy default issueImplementedByOrder\name\D
     */
    interface B {

    }

    class C implements B {

    }

    class D implements B {

    }
}
