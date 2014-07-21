Twig Extensions and Dependency Injection Tags
=============================================

Let's leverage some of what we just learned about services in a lighter way:
twig extensions. When we use Twig, we take advantage of a lot of built-in
functions, filters, tests and other things. Almost everything in Twig - like
the ``path`` function, the ``upper`` filter and even "tests" like ``divisibleby``
are loaded into Twig by "extensions".

While developing your project, you'll eventually come across a situation where
a custom function or filter will come in handy. Creating your own custom Twig
extension is easy, and pretty fun.

Create a Twig Extension
-----------------------

Start by creating a new ``Twig`` directory inside ``EventBundle`` and create
a new class called ``EventExtension``::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    namespace Yoda\EventBundle\Twig;
    
    class EventExtension
    {
    }

The name and location of this class aren't important - you'll see in a moment
how we tell Twig about our new extension. Make the new class extend ``Twig_Extension``
and add the required ``getName`` method::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    namespace Yoda\EventBundle\Twig;

    class EventExtension extends \Twig_Extension
    {
        public function getName()
        {
            return 'event';
        }
    }

The return value of `getName` isn't really important, just make it unique
in your project.

The goal of our Twig extension will be to add an ``ago`` filter. Inside the
``_events.html.twig`` template, add a new line that takes the ``created`` time
of the event and pushes it through the ``ago`` filter:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/_events.html.twig #}
    {# ... #}

    <dt>posted:</dt>
    <dd>{{ entity.created|ago }}</dd>

The ``ago`` filter will convert the DateTime object into something like
"5 seconds ago" or "2 days" ago.

To add the filter, create a new method called ``getFilters`` and return an
array with a single ``ago`` entry::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    // ...

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('ago', array($this, 'ago')),
        );
    }

.. tip::

    The ``Twig_SimpleFilter`` class is a newer and more preferred way of
    adding a filter. Previously, we used ``Twig_Filter_Method``, which still
    works, but is deprecated.

Next, create the ``ago`` method inside the class::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    // ...

    public function ago(\DateTime $dt)
    {
        // todo
    }

For the heavy-lifting, I'll create a new ``DateUtil`` class and copy in some
prepared logic::

    // src/Yoda/EventBundle/Util/DateUtil.php
    
    namespace Yoda\EventBundle\Util;
    
    use DateTime;
    
    class DateUtil
    {
        static public function ago(DateTime $dt)
        {
            // ... check the code download for the source of this class
        }
    }

Back inside ``EventExtension`` we can use the new class to convert the ``DateTime``
object being passed into the filter into our "ago" string::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    // ...
    use Yoda\EventBundle\Util\DateUtil;
    // ...

    public function ago(\DateTime $dt)
    {
        return DateUtil::ago($dt);
    }

Tags: Telling Symfony about your Twig Extension
-----------------------------------------------

At this point, we've created a valid Twig extension with a new filter, but
we haven't actually told Twig about our new class. This is where our knowledge
of services comes in handy.

First, create a new service for our Twig extension:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        # ...
        
        yoda_event.twig.event_extension:
            class: Yoda\EventBundle\Twig\EventExtension
            arguments: []

This works just like the report manager service except that the ``arguments``
key is empty since our class doesn't have a constructor.

Our Twig extension is now a service, but Twig still doesn't know about it.
To prove it, head to the homepage to see the error - Yay!

.. highlights::

    The filter "ago" does not exist in ...

To tell Twig about our extension, add a "tags" key with a single entry and
a name property of ``twig.extension``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        # ...
    
        yoda_event.twig.event_extension:
            class: Yoda\EventBundle\Twig\EventExtension
            arguments: []
            tags:
                - { name: twig.extension }

This syntax may look strange, but it works just like adding a tag to a blog
post. When Symfony boots, Twig looks for all services with the ``twig.extension``
tag and includes those as extensions.

Refresh the page to see our filter in action. You can do a lot of cool things
with Twig extensions and of course you should check out the `official documentation`_
for more details.

Public and Private Services
---------------------------

Head back to the console and run the ``container:debug`` command. As expected,
our new Twig extension shows up in the list. Back in ``services.yml``, add
a ``public`` option and set it to ``false``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        # ...

        yoda_event.twig.event_extension:
            class: Yoda\EventBundle\Twig\EventExtension
            arguments: []
            tags:
                - { name: twig.extension }
            public: false

When you re-run ``container:debug``, the service is gone! The service container
consists of both "public" and "private" services. The only difference between
the two is that a "private" service can't be fetched out of the container
directly. If we try to fetch it in our controller, we'll get an error. If
you're in doubt about this option, just leave it blank and let your service
default to public. Marking a service as private gives you a very slight performance
boost.

More on Tags
------------

As we now know, the ``twig.extension`` tag is how we tell Twig that our service
should be used as an extension. There are a number of `other special "tags"`_
inside Symfony that make your services special in one way or another. A
very important one is `kernel.event_listener`_, which allows you to register
"hooks" inside Symfony at various stages of the request lifecycle. That topic
is for another screencast, but we'll cover a very similar subject next: Doctrine
events.

.. _`official documentation`: http://twig.sensiolabs.org/doc/advanced.html
.. _`other special "tags"`: http://symfony.com/doc/current/reference/dic_tags.html
.. _`kernel.event_listener`: http://symfony.com/doc/current/reference/dic_tags.html#kernel-event-listener