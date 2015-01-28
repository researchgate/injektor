<?php
/**
 * test_classes_issue.php
 *
 * @category
 * @author Johannes Brinksmeier <johannes.brinksmeier@googlemail.com>
 * @version $Id: $
 */
namespace issue  {

    interface Class_With_Underscores {

    }

    class SomeClass implements Class_With_Underscores {

    }

    class ClassWithDependencyToClassWithUnderscores {

        /**
         * @inject
         * @var issue\Class_With_Underscores
         */
        protected $dependency;

        /**
         * @param issue\Class_With_Underscores $dependency
         * @return ClassWithDependencyToClassWithUnderscores
         */
        public function setDependency($dependency)
        {
            $this->dependency = $dependency;

            return $this;
        }

        /**
         * @return issue\Class_With_Underscores
         */
        public function getDependency()
        {
            return $this->dependency;
        }


    }
}

namespace issue9\name {

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
         * @inject
         * @named abc
         * @var B
         */
        protected $myB;
    }
}
