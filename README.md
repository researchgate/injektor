rg\\injection
=============

rg\\injection is a sophisticated dependency injection container for PHP that was inspired by Guice.
Unlike other reflection based containers rg\\injection includes a factory class generator that you can use to prevent
the use of reflection on production.

ToDo
====

- Migrate to annotation support from doctrine commons instead of custom implementation
- Clean up FactoryGenerator

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

Using Provider Classes
----------------------

      class Foo {
         /**
          * @inject
          * @var Bar
          */
         protected $bar;
      }

      /**
       * @providedBy BarProvider
       */
      interface Bar {

      }

      class BarImpl implements Bar {

      }

      class BarProvider implements rg\injection\Provider {
          public function get() {
              return new BarImpl();
          }
      }

      $dic->getInstanceOfClass('Foo');

Instead of Bar, the return value of BarProvider's get Method (BarImpl) is injected into $bar. You can also
configure this in the dependecy injection configuration instead of using annotations


    'Bar' => array(
        'provider' => array(
            'class' => 'BarImpl'
        )
    )

Passing fixed data to providers
-------------------------------

      class Foo {
         /**
          * @inject
          * @var Bar
          */
         protected $bar;
      }

      /**
       * @providedBy BarProvider {'foo' : 'bar'}
       */
      interface Bar {

      }

      class BarImpl implements Bar {

      }

      class BarProvider implements rg\injection\Provider {

          /**
           * @inject
           */
          public function __construct(SomeClass $someClass, $foo) {
          }

          public function get() {
              return new BarImpl();
          }
      }

      $dic->getInstanceOfClass('Foo');

Here the provider gets an additional instance of SomeClass injected. The variable $foo is set to 'bar'. You can also
configure this in the config:

    'Bar' => array(
        'provider' => array(
            'class' => 'BarImpl',
            'params' => array(
                'foo' => 'bar',
            )
        )
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
         * @param Bar $default
         * @named barOne $one
         * @named barTwo $two
         */
        public function __construct(Bar $one, Bar $two, Bar $default) {

        }
     }

     interface Bar {

     }

     class BarImplDefault implements Bar {

     }

     class BarImplOne implements Bar {

     }

     class BarImplTwo implements Bar {

     }

     $dic->getInstanceOfClass('Foo');

Configuration:

     'Bar' => array(
        'class' = > 'BarImplDefault'
        'named' => array(
            'barOne' => 'BarImplOne',
            'barTwo' => 'BarImplTwo'
        )
     )

You can also configure this directly with annotations

     /**
      * @implementedBy BarImplDefault
      * @implementedBy barOne BarImplOne
      * @implementedBy barTwo BarImplTwo
      */
     interface Bar {

     }

     class BarImplDefault implements Bar {

     }

     class BarImplOne implements Bar {

     }

     class BarImplTwo implements Bar {

     }

It is also possible to name the default implementation, so that our configuration looks a bit cleaner. The result is the
same:

    /**
      * @implementedBy default BarImplDefault
      * @implementedBy barOne  BarImplOne
      * @implementedBy barTwo  BarImplTwo
      */
     interface Bar {

     }

Named providers
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
         * @param Bar $default
         * @named barOne $one
         * @named barTwo $two
         */
        public function __construct(Bar $one, Bar $two, Bar $default) {

        }
     }

     interface Bar {

     }

     $dic->getInstanceOfClass('Foo');

Configuration:

     'Bar' => array(
        'provider' => array(
            'class' => 'BarProvider'
        )
        'namedProviders' => array(
            'barOne' => array(
                'class' => 'BarProvider',
                'parameters' => array('name' => 'barOne')
            ),
            'barTwo' => array(
                'class' => 'BarProvider',
                'parameters' => array('name' => 'barTwo')
            )
        )
     )

You can also configure this directly with annotations

     /**
      * @providedBy BarProvider
      * @providedBy barOne BarProvider {"name" : "barOne"}
      * @providedBy barTwo BarProvider {"name" : "barOne"}
      */
     interface Bar {

     }

     class BarProvider implements rg\injection\Provider {
        private $name;

        public function __construct($name) {
            $this->name = $name;
        }

        public function get() {
            switch ($this->name) {
                case 'barOne':
                    return new BarImplOne();
                case 'barTwo':
                    return new BarImplTwo();
            }

            return new BarImplDefault();
        }
     }

     class BarImplDefault implements Bar {

     }

     class BarImplOne implements Bar {

     }

     class BarImplTwo implements Bar {

     }

It is also possible to name the default provider, so that our configuration looks a bit cleaner. The result is the
same:

    /**
      * @providedBy default BarProvider
      * @providedBy barOne BarProvider {"name" : "barOne"}
      * @providedBy barTwo BarProvider {"name" : "barOne"}
      */
     interface Bar {

     }

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

Aspects
-------
When creating an instance or calling a method it is additionally possible to use one of the following aspects:

* Before aspects will be executed before the function (or constructor) is called and you can manipulate the arguments
* Intercept aspects will be executed before the funciton (or constructor) is called, if the interceptor returns false,
  the method is going to be executed, if not, the method is not going to be executed but the container returns the
  result from the aspect
* After aspects will be executed after the function (or constructer) is called and can manipulate the function response
