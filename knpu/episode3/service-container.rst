Symfony Overlord: The Service Container
=======================================

One more buzzword: the service container, or dependency injection container.
The service container is the benevolent overlord that's behind everything.
He doesn't do any work, but he controls all the little peons, or services.

Accessing Existing Services
---------------------------

The container is just a simple object that holds *all* of the services in
your project, including Symfony's core objects. Run the ``container:debug``
console task to get a list of these:

    php app/console container:debug

The list is tiny, only about 200 or so. With such a tiny list, it's easy
to spot the entity manager service: ``doctrine.orm.entity_manager``. This
is the "name" of the service and we use it to get this object out of the
service container.

We've been getting the entity manager by using a helper function in the controller.
But since we know its service name, we can get it directly::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    public function updatedEventsAction()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $eventReportManager = new EventReportManager($em);
        $content = $eventReportManager->getRecentlyUpdatedReport();

        // ...
    }

Refresh! The download still works: this is just a more direct way to access
the same object. But stop! This is *hugely* powerful! Symfony's container
holds over 200 services, and you can get *any* of these in a controller and
use them. It's like someone just gave you 200 new power tools! You may not
know how to use them yet, but you're about to look like Edward Scissorhands!

Adding a Service
----------------

I want to go further by adding our own service to the container.

Find and open a ``services.yml`` file that was generated automatically in
``EventBundle``. When you add a new service, you're "teaching" the container
how to instantiate it. First, it needs to know what the class name is:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        event_report_manager:
            class: Yoda\EventBundle\Reporting\EventReportManager
            arguments: []

The ``event_report_manager`` is the internal name of the service and can
be anything.

The ``arguments`` key tells the container exactly what to pass to the constructor
when it creates a new instance of our service. For example, if the first
``__construct`` argument to ``EventReportManager`` were a string, we could
just type that value here:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        event_report_manager:
            class: Yoda\EventBundle\Reporting\EventReportManager
            arguments: [foo]

But instead of a string, the first argument to ``EventReportManager`` is the
entity manager *service* object. To pass in a service, just put its name
here and prefix it with the magic ``@`` symbol::

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        event_report_manager:
            class: Yoda\EventBundle\Reporting\EventReportManager
            arguments: ["@doctrine.orm.entity_manager"]

The ``@`` symbol tells the container that ``doctrine.orm.entity_manager``
isn't a string: it's another object inside the container. When the container
creates a new instance of ``EventReportManager``, it passes the entity manager
to it.

Re-run the ``container:debug`` console command:

.. code-block:: bash

    php app/console container:debug

Ooo la la! Our new service is in the container.

Using the New Service
~~~~~~~~~~~~~~~~~~~~~

Get this new service in our controller. You already know how to get objects
out of the container - we just did it a minute ago with the entity manager.
It's exactly the same with *our* service.

In ``ReportController``, remove the new call of the ``EventReportManager``
and replace it with a call to the ``container`` object::

    // src/Yoda/EventBundle/Controller/ReportController.php
    // ...

    public function updatedEventsAction()
    {
        $eventReportManager = $this->container->get('event_report_manager');
        $content = $eventReportManager->getRecentlyUpdatedReport();

        // ...
    }

Refresh! Bam, the CSV still downloads. Internally, Symfony creates a new
instance of ``EventReportManager`` and returns it. If we asked for the service
a second time, the container would just give us the same instance as before,
instead of creating a new one. That's nice for performance.

Back up and look at what we've accomplished. By creating ``EventReportManager``
and moving logic there, we made some of our code more organized and reusable.
By going a step further and registering a service, we made it *even* easier
to get and use this object. The services on the container are your application's
*tools*, and you'll add more and more.

Hey Look at this Dumped Container!
----------------------------------

Let's do a little digging where we shouldn't. Go into the ``app/cache/dev``
directory, where Symfony stores its cache files. In here, there's a file
called ``appDevDebugProjectContainer.php``. Open it up.

This is *actually* the container class. When you say ``$this->container``
in your controller, you're getting back an instance of *this* object. Search
for the "getEventReportManagerService" function::

    protected function getEventReportManagerService()
    {
        return $this->services['event_report_manager'] =
            new \Yoda\EventBundle\Reporting\EventReportManager(
                $this->get('doctrine.orm.default_entity_manager')
            );
    }

Internally, when we ask for our service, this is the code that's run. It's
not magic, it's just running the exact same PHP code that we had in our controller
before registering our class as a service. If we made a change to ``services.yml``
and refreshed, Symfony would update this file. Pretty amazing.
