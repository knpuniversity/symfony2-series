Twig Extensions and Dependency Injection Tags
=============================================

We know services. And that makes us really dangerous. Let me show you one
of your new tricks.

Twig gives us a ton of built-in functions, filters, tests and other goodies.
Everything in Twig - like the ``path`` function, the ``upper`` filter and
even "tests" like ``divisibleby`` are loaded into Twig by "extensions", which
are basically Twig "plugins".

So can we add our own custom Twig stuff? Of course we can, and it's really
fun.

Create a Twig Extension
-----------------------

Create a ``Twig`` directory inside ``EventBundle`` and a new class called
``EventExtension``::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    namespace Yoda\EventBundle\Twig;
    
    class EventExtension
    {
    }

The name and location of this class aren't important and you'll see why.
Make the new class extend ``Twig_Extension`` and add the required ``getName``
method::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    namespace Yoda\EventBundle\Twig;

    class EventExtension extends \Twig_Extension
    {
        public function getName()
        {
            return 'event';
        }
    }

This isn't important - just make sure ``getName`` returns something unique
to your project.

The mission, if you choose to accept it, is to create an ``ago`` filter:
something that'll turn a date into a friendlier phrase like "5 minutes ago".

Use the Non-Existent Filter
---------------------------

In ``_upcomingEvents.html.twig``, add a new line that takes the ``createdAt``
time of each event and pushes it through this imaginary ``ago`` filter:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/_upcomingEvents.html.twig #}
    {# ... #}

    <dt>posted:</dt>
    <dd>{{ event.createdAt|ago }}</dd>

Adding a Custom Filter
----------------------

To add the filter, create a new method called ``getFilters`` and return an
array with a single ``ago`` entry::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    // ...

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('ago', array($this, 'calculateAgo')),
        );
    }

This says: "Hey, whenever someone uses an ``ago`` filter in Twig, call a
``calculateAgo`` function". Create that function and give it a ``DateTime``
argument::

    // src/Yoda/EventBundle/Twig/EventExtension.php
    // ...

    public function calculateAgo(\DateTime $dt)
    {
        // todo
    }

To do the heavy lifting, I'll use a ``DateUtil`` class that I have in the
code download. Creat a new ``Util`` directory and paste it there::

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

Inside ``EventExtension``, just call this function statically and return it::

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

Ok, try going to the homepage. It says the filter still doesn't exist.

We *have* created a valid Twig extension with the filter, but we haven't
actually told Twig about it. Services to the rescue!

First, create a new service for our Twig extension:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        # ...
        
        twig.event_extension:
            class: Yoda\EventBundle\Twig\EventExtension
            arguments: []

Hey, this look familiar! The only difference is that ``arguments`` is empty,
because we don't even have a constructor in this case. 

At this point, our Twig extension *is* a service, but Twig still doesn't
know about it. Somehow, we need to raise our hand and say "Hey Symfony, this
isn't a normal service, it's a Twig Extension!".

Add a ``tags`` key with a funny-looking ``twig.extension`` below it:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/services.yml
    services:
        # ...
    
        yoda_event.twig.event_extension:
            class: Yoda\EventBundle\Twig\EventExtension
            arguments: []
            tags:
                - { name: twig.extension }

You know how a blog post can have tags? The idea is the same here. When Symfony
boots, Twig looks for all services with the ``twig.extension`` tag and includes
those as extensions.

Refresh! The new "posted" text looks fantastic. If you want this functionality
in real life, check out the `KnpTimeBundle`_, which is even more powerful.

.. note::

    Want to know more about Twig Extensions? See the `official documentation`_.

More on Tags
------------

What other tags are there? Well I'm *so* glad you asked. In the reference
section of the docs, we have a fantastic page called `The Dependency Injection Tags`_.
If you're doing something really custom, or awesome, in Symfony, you're probably
using a dependency injection tag. You won't use them too often, but they're
key to unlocking really powerful features.

A very important tag is `kernel.event_listener`_, which allows you to register
"hooks" inside Symfony at various stages of the request lifecycle. That topic
is for another screencast, but we'll cover a very similar subject next: Doctrine
events.

.. _`official documentation`: http://twig.sensiolabs.org/doc/advanced.html
.. _`kernel.event_listener`: http://symfony.com/doc/current/reference/dic_tags.html#kernel-event-listener
.. _`KnpTimeBundle`: https://github.com/KnpLabs/KnpTimeBundle
.. _`The Dependency Injection Tags`: http://symfony.com/doc/current/reference/dic_tags.html
