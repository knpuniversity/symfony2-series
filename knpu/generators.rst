Code Generation FTW!
====================

Now for why code generation is awesome. We saw some earlier when we let Doctrine
generate the entity class and annotations for us. Now, we'll use generators
to give us code for an entire section! In the next few minutes, we'll see
our application come to life, and with relatively little effort.

To get this rolling, let's commit our changes to git. This is a good idea for
obvious reasons, but will also help us track our progress:

.. code-block:: bash

    $ git status
    $ git add src/ app/
    $ git commit

Generating a CRUD
-----------------

The first goal of our application is to let users create, display, update
and delete events. We'll need a fairly generic set of routes, controllers
and templates for each of these pages. Instead of writing those ourselves,
we'll let Symfony generate them for us with the ``generate:doctrine:crud``
command:

.. code-block:: bash

    $ php app/console generate:doctrine:crud

Like before, the generator is interactive. Start by entering the "shortcut"
name for our Event entity: ``EventBundle:Event``. Answer "yes" to the "write"
actions, ``yml`` for the configuration format, and use the default ``/event``
for the route prefix. But when it asks you to confirm automatic update of
the routing, choose no.

Routing Imports and Organization
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

It should finish by printing a little bit of code that you need to copy into
the ``routing.yml`` file of the ``EventBundle``. The generator *should* be able
to do this for us, but at the recording of this screencast, a bug prevents this
from happening in some cases.

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    # ...
    
    # copied in from the commands output
    EventBundle_event:
        resource: "@EventBundle/Resources/config/routing/event.yml"
        prefix:   /event

As the code indicates, one of the things the generator did was to create a
new ``event.yml`` routing file in our bundle. By running the ``router:debug``
command, we can see the new routes:

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

Check out our apps main routing file, and notice that we are still only importing
one from the Event Bundle. Inside *that* file, you can organize your routes
based on their controller. For example the new ``event.yml`` file holds all
the routes for the new event controller. Later, we might decide to add more
routing files and import them.

Notice that when you're importing another routing file, the key you use has
absolutely no meaning. I can change ``EventBundle_event`` to anything it has
no effect,  as long as its unique. But the key for a route becomes its internal
"name", and *is* important. Also, notice that I can refer to the ``default.yml``
routing file just by using its relative path. This is equal to using the
``@EventBundle`` syntax.

Checking out the Generated Code
-------------------------------

Phew! Enough with routing, let's see this all in action. Head to the ``/event``
page in your browser. I know we got Apache setup in the last chapter, but
I'm going to continue using the built-in PHP web server:

    http://localhost:8000/app_dev.php/event

You'll see a really ugly, but totally functional section where you can add,
view, update and delete events. Easy, right!

Let's peek at some of the code. The controller is a great source for how
common tasks should be accomplished, like form processing, deleting entities,
redirecting, and causing a 404 page to be thrown. For example, the ``showAction``
uses the ``id`` from its route to look for an event object. If one isn't found,
it sends the user to a 404 page by throwing a special type of exception.
If an event is found, it's passed to the template and rendered. Take some
time to look through the other parts of the controller yourself::

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

Making the Generated Code Less Ugly
-----------------------------------

Time to make this section look less ugly. I'll copy in some template files
that I've already customized:

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    
    {% extends 'EventBundle::layout.html.twig' %}
    {# ... #}

.. tip::

    You can find these templates in the ``resources/Events`` directory of
    the code download.

The 3-template Inheritance System
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Not surprisingly, each new template extends a base template. What might surprise
you is that this isn't the ``::base.html.twig`` layout that we extended earlier.
Instead, it's a template that will live right inside the ``EventBundle``.
Let's create this template. Since the middle part of the template name is
missing, we know that the new template should live directly in the ``Resources/views``
directory of our bundle, and not in a sub-directory:

    Create the file at src/Yoda/EventBundle/Resources/views/layout.html.twig

Inside the new template, simply extend the ``::base.html.twig``. This creates
a template hierarchy - ``index.html.twig`` extends ``layout.html.twig``,
which extends ``base.html.twig``:

.. code-block:: jinja

    {# src/Yoda/EventBundle/Resources/views/layout.html.twig #}
    {% extends '::base.html.twig' %}

In fact, all of our new templates extend ``layout.html.twig``. This means
that if we need to override a base layout block for *all* of our event pages,
we can do that here. Let's try it. Create and set the title block to "Events".
This becomes the default page title for every event page:

.. code-block:: jinja

    {# src/Yoda/EventBundle/Resources/views/layout.html.twig #}
    {% extends '::base.html.twig' %}
    
    {% block title 'Events' %}

Of course, we can still override the title block in any child template, which
is what makes template inheritance awesome.

Route Prefix
------------

TODO - remove the /event route prefix
