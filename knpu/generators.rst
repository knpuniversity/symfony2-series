Code Generation FTW!
====================

I feel like making someone else do some work for awhile, so let's look
at Symfony's code generation tools.

The first thing we need our app to do is let users create, view, update and
delete events. In other words, we need a CRUD for the Event entity.

Want to use Doctrine to generate a CRUD? Yea, there's a console command for
that ``doctrine:generate:crud``:

.. code-block:: bash

    $ php app/console doctrine:generate:crud

This inquisitive command first wants to know which entity we need a CRUD for.
Answer with that shortcut entity "alias" name we've been seeing: ``EventBundle:Event``.

Say "yes" to the "write" actions, ``yml`` for the configuration format, and
use the default ``/event`` route prefix. Then finish up.

Routing Imports and Organization
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Ah crap! Red errors! It's ok, copy the code into the ``routing.yml`` of our
``EventBundle``.

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    # ...

    # copied in from the commands output
    EventBundle_event:
        resource: "@EventBundle/Resources/config/routing/event.yml"
        prefix:   /event

The generation tasks tried to put this in there for us, but we already had
something in this file so it panicked. All better now.

We now know this is a routing import, which loads a brand new ``event.yml``
file:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    event:
        pattern:  /
        defaults: { _controller: "EventBundle:Event:index" }

    event_show:
        pattern:  /{id}/show
        defaults: { _controller: "EventBundle:Event:show" }

    # ... more routes

Let's run the ``router:debug`` command to make sure these are being loaded:

.. code-block:: bash

    $ php app/console router:debug

.. code-block:: text

    event               ANY     /event/
    event_show          ANY     /event/{id}/show
    event_new           ANY     /event/new
    event_create        POST    /event/create
    event_edit          ANY     /event/{id}/edit
    event_update        POST    /event/{id}/update
    event_delete        POST    /event/{id}/delete

Check out the main ``app/config/routing.yml`` file - it's still only importing
the *one* file from the EventBundle:

.. code-block:: yaml

    # app/config/routing.yml
    event:
        resource: "@EventBundle/Resources/config/routing.yml"
        prefix:   /

But once we're in that file, we're of course free to organize routes into
even more files and import those. That's what's happening with ``event.yml``:
it holds all the routes for the new ``EventController``. Don't go crazy,
but when you have a lot of routes, splitting them into multiple files is
a good way to keep things sane.

Oh, and when we import another file, the key - like ``EventBundle_event`` -
is completely meaningless - make it whatever you want. **But**, the key for
an actual route *is* important: it becomes its internal "name". We'll use
it later when we generate links.

Checking out the Generated Code
-------------------------------

Enough with routing! Head to the ``/event`` page to see this in action. I
know we got Apache setup in the last chapter, but I'm going to continue using
the built-in PHP web server and access the site at ``localhost:8000``:

    http://localhost:8000/app_dev.php/event

Woh, that's ugly. Hmm, but it *does* work - we can add, view, update and
delete events. Easy!

Let's peek at some of the code. The generated controller is like a cheatsheet
for how to do common things, like form processing, deleting entities, redirecting
and showing a 404 page.

For example, ``showAction`` uses the ``id`` from its route to query for an
Event object. If one isn't found, it sends the user to a 404 page by calling
``createNotFoundException`` and throwing the result. This helper function
is just a shortcut to create a very specific type of Exception object that
causes a 404 page::

    // src/Yoda/EventBundle/Controller/Event.php
    // ...
    
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        
        $entity = $em->getRepository('EventBundle:Event')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('No event with id '.$id);
        }

        // ...
        return $this->render('EventBundle:Event:index.html.twig', array(
            'event' => $event,
            'delete_form' => $deleteForm->createView(),
        ));
    }

If we do find an Event, it's passed to the template and rendered. Take a
few minutes to look through the other parts of the controller. I mean it!

Making the Generated Code Less Ugly
-----------------------------------

I know this all works, but the ugly is killing me. I created a custom version
of each of the CRUD template files while you were looking through the controller.
You can find these in the ``resources`` directory of the code download for this
screencast. I already moved that directory from the code download into my project.

.. code-block:: bash

    $ cp resources/Event/* src/Yoda/EventBundle/Resources/views/Event/

The 3-template Inheritance System
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you think that the new template files probably extend a layout file, gold
star! But I can't make it that easy. Instead of extending the ``::base.html.twig``
file we're familiar with, each extends ``EventBundle::layout.html.twig``:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {% extends 'EventBundle::layout.html.twig' %}

    ...

Let's create this template. The middle piece of the 3-part template syntax
is missing, which tells us that this will live directly in the ``Resources/views``
directory of our bundle, and not in a sub-directory:

    {# src/Yoda/EventBundle/Resources/views/layout.html.twig #}

    create this file... but nothing here yet...

Inside the new template, simply extend ``::base.html.twig``:

.. code-block:: jinja

    {# src/Yoda/EventBundle/Resources/views/layout.html.twig #}
    {% extends '::base.html.twig' %}

Now we have a template hierarchy - ``index.html.twig`` extends ``layout.html.twig``,
which extends ``base.html.twig``.

.. tip::

    If you try the new templates out, and your browser shows the old ones, try clearing
    out your cache (``php app/console cache:clear``) - this could be a rare time when
    Symfony doesn't rebuild the cache correctly.

This is awesome because *all* the new templates extend ``layout.html.twig``.
So if we want to override a block for *all* of our event pages, we can do
that right here.

Let's try it: set the title block to "Events":

.. code-block:: jinja

    {# src/Yoda/EventBundle/Resources/views/layout.html.twig #}
    {% extends '::base.html.twig' %}
    
    {% block title 'Events' %}

Now we have a better default page title for every event page. Of course,
we can still override the title block in any child template. Template inheritance,
you're awesome.

This 3-level inheritance is definitely not required, keep things simple if
you can. But if you have many slightly different sections on your site, it
might be perfect.

Route Prefix
------------

Look back at the ``routing.yml`` file in our bundle. You're smart, so you
probably already saw the ``prefix`` key and guessed that this prefixes all
the imported route URLs with ``/event``:

    {# src/Yoda/EventBundle/Resources/config/routing.yml #}
    {# ... #}

    EventBundle_event:
        resource: "@EventBundle/Resources/config/routing/event.yml"
        prefix:   /event

This is a nice little feature. Now kill it!

    {# src/Yoda/EventBundle/Resources/config/routing.yml #}
    {# ... #}

    EventBundle_event:
        resource: "@EventBundle/Resources/config/routing/event.yml"
        prefix:   /

With this gone, the events will show up on the homepage. Remove the ``/event`` from
the URL in your browser to see it:

    http://localhost:8000/app_dev.php
