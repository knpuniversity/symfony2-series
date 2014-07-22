Services, Dependency Injection and Service Containers
=====================================================

If you've read anything about Symfony, then you've probably read terms like
"services", "dependency injection" and "dependency injection container".
In the next few minutes, we're going to discover what these terms mean, the
role they play in Symfony, and just how simple these scary sounding
terms really are.

The best way to learn what these terms mean is to see them in action. Start
by creating a new controller - ``ReportController`` then add an action called
``updatedEventsAction``. Our goal is to create a controller that renders a CSV
report of the recently-updated events::

    // src/Yoda/EventBundle/Controller/ReportController.php
    namespace Yoda\EventBundle\Controller;

    use Symfony\Component\HttpFoundation\Response;

    class ReportController extends Controller
    {
        public function updatedEventsAction()
        {
            $events = $this->getDoctrine()
                ->getManager()
                ->getRepository('EventBundle:Event')
                ->createQueryBuilder('e')
                ->andWhere('e.updated > :since')
                ->setParameter('since', new \DateTime('24 hours ago'))
                ->getQuery()
                ->execute()
            ;

            $rows = array();
            foreach ($events as $event) {
                $data = array($event->getId(), $event->getName(), $event->getTime()->format('Y-m-d H:i:s'));

                $rows[] = implode(',', $data);
            }

            $content = implode("\n", $rows);
            $response = new Response($content);
            $response->headers->set('Content-Type', 'text/csv');

            return $response;
        }
    }

This uses a few concepts we've seen already - like a custom query builder
to get the events we want. To create the CSV, we'll just loop over the records
by hand. PHP has a few methods to help handle CSV's, but this will work for
our simple example.

Remember that the job of the controller is to return a Symfony Response object.
To return the CSV content, we can just create a new Response object, set its
content, and make sure it's ``Content-Type`` header is set correctly.

Finally, add a route. For simplicity, we'll add it to the existing ``routing.yml``
file:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    # ...

    event_report_updated_events:
        pattern: /events/report/recentlyUpdated.csv
        defaults: { _controller: EventBundle:Report:updatedEvents, _format: csv }

Alright! Let's give it a try. When we check the contents of the file download,
everything looks great. Another Jedi trick mastered!

Make your Controller Skinny: Queries in the Repository
------------------------------------------------------

Hopefully, this all looks pretty easy to you by now. Really, the only problem
with what we've done is that all of our logic is inside the controller. Let's --
dare I say it?! -- Refactor a few things!

Start by moving the custom query into the ``EventRepository``::

    // src/Yoda/EventBundle/Entity/EventRepository.php
    
    public function getRecentlyUpdatedEvents()
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.updated > :since')
            ->setParameter('since', new \DateTime('24 hours ago'))
            ->getQuery()
            ->execute()
        ;
    }

Now, call the new ``getRecentlyUpdatedEvents`` method in the controller::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    public function updatedEventsAction()
    {
        $events = $this->getDoctrine()
            ->getManager()
            ->getRepository('EventBundle:Event')
            ->getRecentlyUpdatedEvents()
        ;

        // ...
    }


This is already better, but let's do even more!

Our First Service
-----------------

Create a new ``Reporting`` directory in the bundle with a new class called ``EventReportManager``::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    namespace Yoda\EventBundle\Reporting;
    
    class EventReportManager
    {
    }

Like any new class, give it the proper namespace. But, unlike entity, form
and controller classes, this one has nothing to do with Symfony. It's just
a "plain-old-PHP-object", which we're going to use to help organize our code.
Add a new ``getRecentlyUpdatedReport`` method to the class and paste all
of our report-generating logic from the controller into it::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    class EventReportManager
    {
        public function getRecentlyUpdatedReport()
        {
            $events = $this->getDoctrine()
                ->getManager()
                ->getRepository('EventBundle:Event')
                ->getRecentlyUpdatedEvents()
            ;

            $rows = array();
            foreach ($events as $event) {
                $data = array($event->getId(), $event->getName(), $event->getTime()->format('Y-m-d H:i:s'));

                $rows[] = implode(',', $data);
            }

            return implode("\n", $rows);
        }
    }

In the controller, we can now just create a new instance of ``EventReportManager``
and call ``getRecentlyUpdatedReport`` on it::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...
    
    use Yoda\EventBundle\Reporting\EventReportManager;

    public function updatedEventsAction()
    {
        $reportManager = new EventReportManager();
        $content = $reportManager->getRecentlyUpdatedReport();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }

Like always, don't forget the `use` statement for this new class.

The idea here is simple. When we create a custom query, we already know
that we can move that logic into a Repository class. This keeps our query
logic in a central location and allows queries to be re-used.

The same can be done for our reporting logic, or *anything* else you might
do in your application. The fact that there's no "pre-made" class for our
reporting logic is fine: we can just create our own and move the logic
into it.

But we're not quite done yet, so let's finish things up. When we refresh
the page, we see gah an error!

.. highlights::

    Call to undefined method Yoda\EventBundle\Reporting\EventReportManager::getDoctrine()

In our report manager, we're calling ``$this->getDoctrine()``. Remember that
our controller extends a base Controller class, which gives us lots of shortcut
methods, including ``getDoctrine``. But in ``EventReportManager``, we don't
extend anything and we don't magically have access to the Doctrine object.

Dependency Injection to the Rescue!
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The code inside ``EventReportManager`` is *dependent* on the "doctrine" object,
and more specifically Doctrine's entity manager. To solve our error, we'll
"inject the dependency". This is a fancy term for a really simple idea.

First, add a constructor method with a single ``$em`` argument. Set that on
an ``$em`` class property::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    class EventReportManager
    {
        private $em;
        
        public function __construct($em)
        {
            $this->em = $em;
        }
    }

This will be the entity manager object. Inside ``getRecentlyUpdatedReport``,
use the new ``$em`` property and remove the non-existent ``getDoctrine`` call::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...
    
    private $em;
    // ...

    public function getRecentlyUpdatedReport()
    {
        $events = $this->em
            ->getRepository('EventBundle:Event')
            ->getRecentlyUpdatedEvents()
        ;

        // ...
    }

Back in ``ReportController``, we can get the entity manager like we always do
and pass it as the first argument when creating a new ``EventReportManager``::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    use Yoda\EventBundle\Reporting\EventReportManager;

    public function updatedEventsAction()
    {
        $reportManager = new EventReportManager($this->getDoctrine()->getManager());
        $content = $reportManager->getRecentlyUpdatedReport();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }

Refresh the page to see that the CSV is downloaded successfully.

Congratulations! You've just done "dependency injection". Dependency injection
isn't some new programming practice or magic trick, it's just the idea
of passing dependencies into objects that need them. In our example, ``EventReportManager``
needs the entity manager object. When creating the manager, we "inject" it
by passing it into the manager's constructor. Now that the manager has everything
it needs, it can get its work done.

.. tip::

    To learn more, check out our free tutorial that's all about the great
    topic of `Dependency Injection`_.

In addition to "dependency injection", we've also just created our first "service".
That's right - a "service" is nothing more than a term that's loosely given
to a PHP object that performs an action. ``EventReportManager`` performs an
action, so it's technically a "service". Another common property of a "service"
is that you only ever need one instance at a time. For example, if we needed
to generate 2 CSV reports, it wouldn't really make sense to create 2 objects
when we can just re-use the same one twice. "Services" are the machines of
your application - each does its own "work", like creating reports, sending
emails, or anything else you can dream up.

The Service Container
---------------------

Now let's learn more about the third and final term: the service container.
The service container is an object that holds all of the services in your
project, including all of Symfony's core objects. Like we saw in previous
screencasts, you can use the ``container:debug`` console task to get a list
of all of the useful objects that are available.

    php app/console container:debug

Notice that one of these (``doctrine.orm.entity_manager``) is the entity manager.

Let's add our own service to this container. First, find and open a ``services.yml``
file that was generated automatically in our bundle. To add our ``EventReportManager``
into the service container, we need to "teach" Symfony how to construct it.

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        yoda_event.reporting.event_report_manager:
            class: Yoda\EventBundle\Reporting\EventReportManager
            arguments:
                # todo ...

First, give the service a name, which typically looks a bit like the class's
namespace.

.. tip::

    The service "name" (e.g. `yoda_event.reporting.event_report_manager``)
    can be anything you want. Making it look like the class's namespace is
    just a nice practice.

Next, add a "class" key. Finally, add an arguments key. The arguments
key tells Symfony exactly what to pass into the constructor when it creates
a new instance of our service. For example, if the first argument to ``EventReportManager``
were a string, we could just type the value of that here.

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        yoda_event.reporting.event_report_manager:
            class: Yoda\EventBundle\Reporting\EventReportManager
            arguments:
                # pass the string "foo" as the first constructor argument
                - "foo"

But instead of a string, the first argument to ``EventReportManager`` is the
entity manager object. In the service container, this object is available
under the name ``doctrine.orm.entity_manager``. Paste that string into the
first ``arguments`` entry and prefix it with an ``@`` symbol::

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        yoda_event.reporting.event_report_manager:
            class: Yoda\EventBundle\Reporting\EventReportManager
            arguments:
                - "@doctrine.orm.entity_manager"

The ``@`` symbol tells Symfony that ``doctrine.orm.entity_manager`` isn't
a string, but refers to another object inside the container. Now, when the
container creates a new instance of ``EventReportManager``, it will pass
the entity manager to it.

Re-run the ``container:debug`` console command:

.. code-block:: bash

    php app/console container:debug

Fabulous! Our new service is registered. Let's use it!

In ``ReportController``, remove the new call of the ``EventReportManager``
and replace it with a call to the ``container`` object::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    use Yoda\EventBundle\Reporting\EventReportManager;

    public function updatedEventsAction()
    {
        $reportManager = $this->container
            ->get('yoda_event.reporting.event_report_manager');
        $content = $reportManager->getRecentlyUpdatedReport();

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');

        return $response;
    }


Life is really easy inside a controller because the service container object
is available via ``$this->container``. By calling ``get``, we can fetch out
any service. Internally, Symfony creates a new instance of ``EventReportManager``
and returns it. If we were to ask for the service a second time, the container
just returns the same instance as before, instead of creating a new one.

Let's go back to the browser to try it out, again our file downloads! We've just
put our first service into the service container. We can re-use it anywhere
in our application by getting it out of the container.

Loading and Importing Configuration Files
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

I want to add one quick note about ``services.yml``. Like with routing, this
file isn't automatically discovered by Symfony. Instead, when the bundle
was generated, an ``EventExtension`` class was created for you. This class
is mostly useful for third-party bundles, but one thing it does by default
is load the ``services.yml`` file::

    // src/Yoda/EventBundle/DependencyInjection/EventExtension.php
    // ...

    public function load(array $configs, ContainerBuilder $container)
    {
        // ...
        // this was all generated when we generated the bundle
        $loader->load('services.yml');
    }

If you don't have this "Extension" class in your bundle, no problem! You
can always import your ``services.yml`` file from inside ``config.yml``:

.. code-block:: yaml

    # app/config/config.yml
    imports:
        # ...
        - { resource: "@EventBundle/Resources/config/services.yml" }

You could also rename ``services.yml`` to anything else - it's name isn't
really important.

.. note::

    The point is that any YAML or XML file that defines a service *must* be
    imported manually. This can be done via the special "extension" class
    of a bundle *or* simply by adding it to the ``imports`` section of ``config.yml``
    or any other configuration file.

Type-Hinting
------------

Let's do a few more quick things with our brand-new shiny service. In ``EventReportManager``,
the first argument to the constructor is the entity manager. While totally
optional, one good practice is to type-hint the argument. Add the ``EntityManager``
``use`` statement and then type-hint this argument::

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

If you're unsure what the class name behind a service is, use the ``container:debug``
command to find out. You could also type-hint using ``ObjectManager``, which
is the interface that the entity manager ultimately uses::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    use Doctrine\Common\Persistence\ObjectManager;

    class EventReportManager
    {
        private $em;

        public function __construct(ObjectManager $em)
        {
            $this->em = $em;
        }
    }

If you're not too comfortable with this, don't worry. This is optional, but
a good practice to get into.

Injecting the Logger with Setter Injection
------------------------------------------

To show off one more feature of the service container, let's add some logging
to ``EventReportManager``. Check out the ``container:debug`` output to discover
that Symfony already has a ``logger`` service that we can use. Symfony integrates
with a third-party library called `Monolog`_, which does some very cool things
with logging.

If we want ``EventReportManager`` to be able to log messages, then it's going
to need access to the ``logger`` service. To get it, we'll inject it!

When we injected the entity manager, we added it to the constructor and then
configured Symfony to pass the service as the first argument. We could certainly
do the same thing for the logger. But instead, I'll show you a second way
to inject dependencies: setter injection. Add a ``setLogger`` method and store
the logger as a property::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...
    
    use Symfony\Bridge\Monolog\Logger;

    class EventReportManager
    {
        // ...
        private $logger;
        // ...

        public function setLogger(Logger $logger)
        {
            $this->logger = $logger;
        }
    }

In the service configuration, add a ``calls`` key:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        yoda_event.reporting.event_report_manager:
            class: Yoda\EventBundle\Reporting\EventReportManager
            arguments:
                - "@doctrine.orm.entity_manager"
            calls:
                - [ setLogger, ["@logger"]]

This key has a strange syntax, but tells Symfony to call ``setLogger`` after
creating the object and to pass the ``logger`` service to that method.

Both constructor injection and setter injection have the same outcome: the
logger is stored on the ``logger`` property of our object. Constructor injection
tends to be used for "required" dependencies - things your object must have
in order to function properly. Setter injection is typically used for any
non-required dependencies. This fits better for a logger, since ``EventReportManager``
could still work without it.

Create a new ``logInfo`` method that logs a message if the ``logger`` property
is set::

    // src/Yoda/EventBundle/Reporting/EventReportManager.php
    // ...

    class EventReportManager
    {
        // ...
        private $logger;
        // ...
        
        public function getRecentlyUpdatedReport()
        {
            $this->logInfo('Generating the recently updated events CSV!!!');
            // ...
        }

        private function logInfo($msg)
        {
            if ($this->logger) {
                $this->logger->info($msg);
            }
        }
    }

Even though the service container will guarantee that ``setLogger`` is called,
it's important to check to see if ``logger`` has been set. If we ever decided
to create this class directly, it would still work nicely without us needing
to set the logger.

To see it in action, tail the ``dev.log`` and refresh the page:

    tail -f app/logs/dev.log

If we scroll up a little bit we'll find our message:

    [2013-06-26 19:33:48] app.INFO Generating the recently updated events CSV!!! [] []

.. _`Monolog`: https://github.com/Seldaek/monolog
.. _`Dependency Injection`: http://knpuniversity.com/screencast/dependency-injection
