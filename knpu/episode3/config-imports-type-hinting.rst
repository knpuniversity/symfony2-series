Configuration Loading and Type-Hinting
======================================

So just like with routing files, ``services.yml`` isn't magically loaded
by Symfony: something needs to import it.

When the bundle was generated, an ``EventExtension`` class was created for
you.

This class is mostly useful for third-party bundles, but one thing it does
by default is load the ``services.yml`` file::

    // src/Yoda/EventBundle/DependencyInjection/EventExtension.php
    // ...

    public function load(array $configs, ContainerBuilder $container)
    {
        // ...
        // this was all generated when we generated the bundle
        $loader->load('services.yml');
    }

If you don't have this "Extension" class in your bundle, no problem! In fact,
delete the entire ``DependencyInjection`` directory. Now, just import your
``services.yml`` file from inside ``config.yml``:

.. code-block:: yaml

    # app/config/config.yml
    imports:
        # ...
        - { resource: "@EventBundle/Resources/config/services.yml" }

You could also rename ``services.yml`` to anything else. As you can see,
the name isn't important.

.. note::

    The point is that any file that defines a service *must* be imported
    manually. This can be done via the special "extension" class of a bundle
    *or* simply by adding it to the ``imports`` section of ``config.yml``
    or any other configuration file.

Refresh! More CSV Downloading!

Type-Hinting
------------

There's one more thing in our service that's bothering me. The first argument
to the constructor is the entity manager object, but we're not type-hinting
it. Type-hinting is optional, but I like doing it because it gives me better
errors and gives me auto-completion in PhpStorm.

So what *is* the class for the entity manager service? One way to find out
is to use ``container:debug`` but pass it the service name:

.. code-block:: yaml

    php app/console container:debug doctrine.orm.entity_manager

It says that it's just an "alias" for a different service. So let's look
up that one:

.. code-block:: yaml

    php app/console container:debug doctrine.orm.default_entity_manager

Great! Now we can add a type-hint for the argument. And by the way, a lot
of times I just guess the class name and let PhpStorm mind trick ... I mean 
auto-complete the ``use`` statement for me. It's lazy, but it almost always works::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...
    
    use Doctrine\ORM\EntityManager;

    class EventReportManager
    {
        private $em;
    
        public function __construct(EntityManager $em)
        {
            $this->em = $em;
        }
    }

If you're not too comfortable with this, don't worry. This is optional, but
a good practice to get into.
