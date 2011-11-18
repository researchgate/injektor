rg\injection
============

rg\injection is a sophisticated dependency injection container for PHP that was inspired by Guice.
Unlike other reflection based containers rg\injection includes a factory class generator that you can use to prevent
the use of reflection on production.

Features
========

Constructor Injection
---------------------

     class Foo {
        /**
         * @inject
         * @param Bar $bar
         */
        public function __construct(Bar $bar) {

        }
     }

     class Bar {

     }

     $dic->getInstanceOfClass('Foo');

An instance of Bar will be injected as the constructor argument $bar. Of course Bar could use dependency injection as
well. The container can inject any classes that are injectable because:

- they have a @inject annotation at the constructor
- they have a constructor without arguments
- they have no constructor
- the arguments are optional
- the arguments are configured (see below)

A constructor can be either a __construct method or a static getInstance method if the class is configured as singleton
and the __construct method is private or protected.


      class Foo {
         /**
          * @inject
          * @param Bar $bar
          */
         public function __construct(Bar $bar) {

         }
      }

      /**
       * @singleton
       */
      class Bar {
        private function __construct() {

        }

        public static function getInstance() {

        }
      }

      $dic->getInstanceOfClass('Foo');

Property Injection
------------------

     class Foo {
        /**
         * @inject
         * @var Bar
         */
        protected $bar;
     }

     class Bar {

     }

     $dic->getInstanceOfClass('Foo');

Field $bar will have an instance of Bar. In order for this to work the field can not be private but has to be protected
or public. This can also be combined with constructor injection.

Inject Concrete Implementation
------------------------------

      class Foo {
         /**
          * @inject
          * @var Bar
          */
         protected $bar;
      }

      /**
       * @implementedBy BarImpl
       */
      interface Bar {

      }

      class BarImpl implements Bar {

      }

      $dic->getInstanceOfClass('Foo');

Instead of Bar, BarImpl is injected into $bar. You can also configure this in the dependecy injection configuration
instead of using annotations


    'Bar' => array(
        'class' => 'BarImpl'
    )

Inject as Singleton
-------------------

      class Foo {
         /**
          * @inject
          * @var Bar
          */
         protected $bar;
      }

      /**
       * @singleton
       */
      class Bar {

      }

      $instanceOne = $dic->getInstanceOfClass('Foo');
      $instanceTwo = $dic->getInstanceOfClass('Foo');

Both $instanceOne and $instanceTwo will have the same instance of Bar injections.

You can also configure this in the dependecy injection configuration instead of using annotations

    'Bar' => array(
        'singleton' => true
    )

Configuring parameters
----------------------

You can also configure the content of all or some parameters that the container should pass to the __construct or getInstance
method in the configuration instead of letting the container guess them from typehints:

      class Foo {
         /**
          * @inject
          */
         public function __construct($bar) {

         }
      }

      /**
       * @singleton
       */
      class Bar {
        private function __construct() {

        }

        /**
         * @inject
         */
        public static function getInstance($foo, $buzz) {

        }
      }

      $dic->getInstanceOfClass('Foo');

Configuration:

    'Foo' => array(
        'params' => array(
            'bar' => array(
                'class' => 'Bar'
            )
        )
    ),
    'Bar' = array(
        'params' => array(
            'foo' => array(
                'value' => 'fooBar'
            ),
            'buzz' => array(
                'value' => true
            )
        )
    )

Pass additional parameters on runtime
-------------------------------------

You also can pass some values to the new instance on runtime.

      class Foo {
         /**
          * @inject
          */
         public function __construct($val, Bar $bar, Buzz $buzz) {

         }
      }

      class Bar {
      }

      class Buzz {
      }

      $dic->getInstanceOfClass('Foo', array(
        'val' => 123,
        'buzz' => new Buzz()
      ));

This can also be combined with configured parameters.

Named injection
---------------

     class Foo {

        /**
         * @var Bar
         * @named barOne
         */
        protected $bar;

        /**
         * @inject
         * @param Bar $one
         * @param Bar $two
         * @named barOne $one
         * @named barTwo $two
         */
        public function __construct(Bar $one, Bar $two) {

        }
     }

     interface Bar {

     }

     class BarImplOne implements Bar {

     }

     class BarImplTwo implements Bar {

     }

     $dic->getInstanceOfClass('Foo');

Configuration:

     'Bar' => array(
        'named' => array(
            'barOne' => 'BarImplOne',
            'barTwo' => 'BarImplTwo'
        )
     )

Call method on object instance
------------------------------

The container can also call methods on instances an inject all method arguments

     class Foo {
        /**
         * @inject
         */
        public function doSomething(Bar $bar) {

        }
     }

     class Bar {

     }

     $foo = new Foo();
     $dic->callMethodOnObject($foo, 'doSomething');

Of course you can also use named injections.

It is also possible to add additional values to the method call, like with object creation:


     class Foo {
        /**
         * @inject
         */
        public function doSomething(Bar $bar, $foo) {

        }
     }

     class Bar {

     }

     $foo = new Foo();
     $dic->callMethodOnObject($foo, 'doSomething', array('foo' => 'value'));


