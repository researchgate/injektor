# rg\\injektor

rg\\injektor is a sophisticated dependency injection container for PHP that was inspired by Guice.
Unlike other reflection based containers rg\\injektor includes a factory class generator that you can use to prevent
the use of reflection on production.

[![Build Status](https://travis-ci.org/researchgate/injektor.svg?branch=master)](https://travis-ci.org/researchgate/injektor)

# Prerequisites

This library needs PHP 5.5+.

It has been tested using PHP5.5 - PHP7.0 and HHVM.


# Installation

You can install the library directly with composer. Just run this command in your project directory:
```bash
$ composer require rg/injektor
```

# Usage
After you installed rg\\injektor you can use it like this:

```php
$configuration = new \rg\injektor\Configuration($pathToConfigFile, $pathToFactoryDirectory);
$dic = new \rg\injektor\DependencyInjectionContainer($configuration);

$instance = $dic->getInstanceOfClass('ClassName');
$result = $dic->callMethodOnObject($instance, 'methodName');
```

For more details on the specific features of rg\\injektor see below.

If you use some kind of MVC framework it is recommended to include rg\\injektor in your front controller to create
your controller objects and call methods on them.

# Generating Factories

By default rg\\injektor relies heavily on Reflection which is fine for your development environment but would slow down
your production environment unnecessarily. So you should use the built in possiblity to use generated factory classes
instead. In order to do this you have to generate these factories before deploying your project.

First you have to use the \rg\injektor\FactoryDependencyInjectionContainer class in your code:

```php
$configuration = new \rg\injektor\Configuration($pathToConfigFile, $pathToFactoryDirectory);
$dic = new \rg\injektor\FactoryDependencyInjectionContainer($configuration);
```
If no factories are present \rg\injektor\FactoryDependencyInjectionContainer falls back to Reflection.

To generate factories you have to write a small script that iterates over your PHP files and create factories for each
of them. Here is an example of such a script based on the Symfony Console Component:

```php
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use rg\injektor\WritingFactoryGenerator;

class GenerateDependencyInjectionFactories extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var \rg\injektor\DependencyInjectionContainer
     */
    private $dic;

    /**
     * @var \rg\injektor\WritingFactoryGenerator
     */
    private $factoryGenerator;

    /**
     * @var string
     */
    private $root;

    protected function configure()
    {
        $this->setDescription('generates factories for dependency injection container');
        $this->setHelp('generates factories for dependency injection container');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Generating Factories');

        $this->root = '/path/to/your/project';

        $factoryPath = $this->root . '/folder/for/generated/factories';

        if (!file_exists($factoryPath)) {
            mkdir($factoryPath, 0777, true);
        }

        $pathToConfigFile = '/config/dic.php';

        $configuration = new \rg\injektor\Configuration($pathToConfigFile, $factoryPath);
        $this->dic = new \rg\injektor\FactoryDependencyInjectionContainer($configuration);

        $this->factoryGenerator = new WritingFactoryGenerator($this->dic->getConfig(), $factoryPath);

        $this->factoryGenerator->cleanUpGenerationDirectory($factoryPath);

        $this->processAllDirectories($output);
    }

    /**
     * @param OutputInterface $output
     */
    private function processAllDirectories(OutputInterface $output)
    {
        $this->processDirectory($this->root . DIRECTORY_SEPARATOR . 'folderWithPhpClasses', $output);
    }

    /**
     * @param $directory
     * @param OutputInterface $output
     */
    private function processDirectory($directory, OutputInterface $output)
    {
        $output->writeln('Directory: ' . $directory);
        $directoryIterator = new \RecursiveDirectoryIterator($directory);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);
        $regexIterator = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);
        foreach ($regexIterator as $file) {
            $this->processFile($file[0], $output);
        }
    }

    /**
     * @param $fullpath
     * @param OutputInterface $output
     */
    private function processFile($fullpath, OutputInterface $output)
    {
        $output->writeln('Process file [' . $fullpath . ']');

        require_once $fullpath;

        $fileReflection = new \Zend\Code\Reflection\FileReflection($fullpath);
        $classes = $fileReflection->getClasses();
        foreach ($classes as $class) {
            $this->processClass($class);
        }
    }

    /**
     * @param \Zend\Code\Reflection\ClassReflection $class
     */
    private function processClass(\Zend\Code\Reflection\ClassReflection $class)
    {
        if (!$class->isInstantiable()) {
            return;
        }
        $this->factoryGenerator->processFileForClass($class->name);
    }
}
```

# Features

Constructor Injection
---------------------

```php
class Foo
{
    /**
     * @inject
     * @param Bar $bar
     */
    public function __construct(Bar $bar)
    {

    }
}

class Bar
{

}

$dic->getInstanceOfClass('Foo');
```

An instance of Bar will be injected as the constructor argument $bar. Of course Bar could use dependency injection as
well. The container can inject any classes that are injectable because:

- they have a @inject annotation at the constructor
- they have a constructor without arguments
- they have no constructor
- the arguments are optional
- the arguments are configured (see below)

A constructor can be either a __construct method or a static getInstance method if the class is configured as singleton
and the __construct method is private or protected.

```php
class Foo
{
    /**
     * @inject
     * @param Bar $bar
     */
    public function __construct(Bar $bar)
    {

    }
}

/**
 * @singleton
 */
class Bar
{
    private function __construct()
    {

    }

    public static function getInstance()
    {

    }
}

$dic->getInstanceOfClass('Foo');
```

Property Injection
------------------

```php
class Foo
{
    /**
     * @inject
     * @var Bar
     */
    protected $bar;
}

class Bar
{

}

$dic->getInstanceOfClass('Foo');
```

Field $bar will have an instance of Bar. In order for this to work the field can not be private but has to be protected
or public. This can also be combined with constructor injection.

Inject Concrete Implementation
------------------------------

```php
class Foo
{
    /**
     * @inject
     * @var Bar
     */
    protected $bar;
}

/**
 * @implementedBy BarImpl
 */
interface Bar
{

}

class BarImpl implements Bar
{

}

$dic->getInstanceOfClass('Foo');
```

Instead of Bar, BarImpl is injected into $bar. You can also configure this in the dependecy injection configuration
instead of using annotations

```php
'Bar' => array(
    'class' => 'BarImpl'
)
```

Using Provider Classes
----------------------

```php
class Foo
{
    /**
     * @inject
     * @var Bar
     */
    protected $bar;
}

/**
 * @providedBy BarProvider
 */
interface Bar
{

}

class BarImpl implements Bar
{

}

class BarProvider implements rg\injektor\Provider
{
    public function get()
    {
        return new BarImpl();
    }
}

$dic->getInstanceOfClass('Foo');
```

Instead of Bar, the return value of BarProvider's get Method (BarImpl) is injected into $bar. You can also
configure this in the dependecy injection configuration instead of using annotations

```php
'Bar' => array(
    'provider' => array(
        'class' => 'BarImpl'
    )
)
```

Passing fixed data to providers
-------------------------------

```php
class Foo
{
    /**
     * @inject
     * @var Bar
     */
    protected $bar;
}

/**
 * @providedBy BarProvider {'foo' : 'bar'}
 */
interface Bar
{

}

class BarImpl implements Bar
{

}

class BarProvider implements rg\injektor\Provider
{

    /**
     * @inject
     */
    public function __construct(SomeClass $someClass, $foo)
    {
    }

    public function get()
    {
        return new BarImpl();
    }
}

$dic->getInstanceOfClass('Foo');
```

Here the provider gets an additional instance of SomeClass injected. The variable $foo is set to 'bar'. You can also
configure this in the config:

```php
'Bar' => array(
    'provider' => array(
        'class' => 'BarImpl',
        'params' => array(
            'foo' => 'bar',
        )
    )
)
```



Inject as Singleton
-------------------

```php
class Foo
{
    /**
     * @inject
     * @var Bar
     */
    protected $bar;
}

/**
 * @singleton
 */
class Bar
{

}

$instanceOne = $dic->getInstanceOfClass('Foo');
$instanceTwo = $dic->getInstanceOfClass('Foo');
```

Both $instanceOne and $instanceTwo will have the same instance of Bar injections.

You can also configure this in the dependecy injection configuration instead of using annotations

```php
'Bar' => array(
    'singleton' => true
)
```

Note that for a singleton injektor analizes the given arguments of the injected class to determine if
the wanted instance is already created or not.

That means in this example:

```php
class Foo
{
    /**
     * @inject
     * @var Bar
     */
    protected $bar;
}

/**
 * @singleton
 */
class Bar
{
    public function __construct($arg)
    {
    }
}

$instanceOne = $dic->getInstanceOfClass('Foo', array('arg' => 1));
$instanceTwo = $dic->getInstanceOfClass('Foo', array('arg' => 2));
```

$instanceOne and $instanceTwo will be different instances. This feature comes with a speed price though,
so if you want to have the same instance regardless of the parameter are always pass in the same or
inject all parameters, mark it as a service instead (see below).

Injecting as service
--------------------

```php
class Foo
{
    /**
     * @inject
     * @var Bar
     */
    protected $bar;
}

/**
 * @service
 */
class Bar
{

}

$instanceOne = $dic->getInstanceOfClass('Foo');
$instanceTwo = $dic->getInstanceOfClass('Foo');
```

Both $instanceOne and $instanceTwo will have the same instance of Bar injections.

You can also configure this in the dependecy injection configuration instead of using annotations

```php
'Bar' => array(
    'service' => true
)
```

In contrast to singletons, In a service this example

```php
class Foo
{
    /**
     * @inject
     * @var Bar
     */
    protected $bar;
}

/**
 * @service
 */
class Bar
{
    public function __construct($arg)
    {
    }
}

$instanceOne = $dic->getInstanceOfClass('Foo', array('arg' => 1));
$instanceTwo = $dic->getInstanceOfClass('Foo', array('arg' => 2));
```

would lead to $instanceOne and $instanceTwo being the same object instance.

Configuring parameters
----------------------

You can also configure the content of all or some parameters that the container should pass to the __construct or getInstance
method in the configuration instead of letting the container guess them from typehints:

```php
class Foo
{
    /**
     * @inject
     */
    public function __construct($bar)
    {

    }
}

/**
 * @singleton
 */
class Bar
{
    private function __construct()
    {

    }

    /**
     * @inject
     */
    public static function getInstance($foo, $buzz)
    {

    }
}

$dic->getInstanceOfClass('Foo');
```

Configuration:

```php
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
```

Alternatively you can also configure this with annotations

```php
class Foo
{
    /**
     * @inject
     * @var Bar {"foo":456,"buzz":"content"}
     */
    protected $propertyInjection;


    /**
     * @inject
     * @param Bar $bar {"foo":123,"buzz":"content"}
     */
    public function __construct(Bar $bar)
    {

    }
}

/**
 * @singleton
 */
class Bar
{
    private function __construct()
    {

    }

    /**
     * @inject
     */
    public static function getInstance($foo, $buzz)
    {

    }
}

$dic->getInstanceOfClass('Foo');
```

Pass additional parameters on runtime
-------------------------------------

You also can pass some values to the new instance on runtime.

```php
class Foo
{
    /**
     * @inject
     */
    public function __construct($val, Bar $bar, Buzz $buzz)
    {

    }
}

class Bar
{
}

class Buzz
{
}

$dic->getInstanceOfClass('Foo', array(
    'val' => 123,
    'buzz' => new Buzz()
));
```

This can also be combined with configured parameters.

Named injection
---------------

```php
class Foo
{
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
    public function __construct(Bar $one, Bar $two, Bar $default)
    {

    }
}

interface Bar
{

}

class BarImplDefault implements Bar
{

}

class BarImplOne implements Bar
{

}

class BarImplTwo implements Bar
{

}

$dic->getInstanceOfClass('Foo');
```

Configuration:

```php
'Bar' => array(
    'class' => 'BarImplDefault'
    'named' => array(
        'barOne' => 'BarImplOne',
        'barTwo' => 'BarImplTwo'
    )
)
```

You can also configure this directly with annotations

```php
/**
 * @implementedBy BarImplDefault
 * @implementedBy barOne BarImplOne
 * @implementedBy barTwo BarImplTwo
 */
interface Bar
{

}

class BarImplDefault implements Bar
{

}

class BarImplOne implements Bar
{

}

class BarImplTwo implements Bar
{

}
```

It is also possible to name the default implementation, so that our configuration looks a bit cleaner. The result is the
same:

```php
/**
 * @implementedBy default BarImplDefault
 * @implementedBy barOne  BarImplOne
 * @implementedBy barTwo  BarImplTwo
 */
interface Bar
{

}
```

Named providers
---------------

```php
class Foo
{
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
    public function __construct(Bar $one, Bar $two, Bar $default)
    {
    }
}

interface Bar
{

}

$dic->getInstanceOfClass('Foo');
```

Configuration:

```php
'Bar' => array(
    'provider' => array(
        'class' => 'BarProvider'
    ),
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
```

You can also configure this directly with annotations

```php
/**
 * @providedBy BarProvider
 * @providedBy barOne BarProvider {"name" : "barOne"}
 * @providedBy barTwo BarProvider {"name" : "barOne"}
 */
interface Bar
{

}

class BarProvider implements rg\injektor\Provider
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function get()
    {
        switch ($this->name) {
            case 'barOne':
                return new BarImplOne();
            case 'barTwo':
                return new BarImplTwo();
        }

        return new BarImplDefault();
    }
}

class BarImplDefault implements Bar
{

}

class BarImplOne implements Bar
{

}

class BarImplTwo implements Bar
{

}
```

It is also possible to name the default provider, so that our configuration looks a bit cleaner. The result is the
same:

```php
/**
 * @providedBy default BarProvider
 * @providedBy barOne BarProvider {"name" : "barOne"}
 * @providedBy barTwo BarProvider {"name" : "barOne"}
 */
interface Bar
{

}
```

Call method on object instance
------------------------------

The container can also call methods on instances an inject all method arguments

```php
class Foo
{
    /**
     * @inject
     */
    public function doSomething(Bar $bar)
    {
    }
}

class Bar
{

}

$foo = new Foo();
$dic->callMethodOnObject($foo, 'doSomething');
```

Of course you can also use named injections.

It is also possible to add additional values to the method call, like with object creation:

```php

class Foo
{
    /**
     * @inject
     */
    public function doSomething(Bar $bar, $foo)
    {
    }
}

class Bar
{

}

$foo = new Foo();
$dic->callMethodOnObject($foo, 'doSomething', array('foo' => 'value'));
```

