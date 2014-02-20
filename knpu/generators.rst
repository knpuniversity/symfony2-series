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
the routes for the new event controller.

To follow this fully, let's organize the default controller route into it's
own file.

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/default.yml
    # move the existing route into a new file

    event_homepage:
        pattern:  /hello/{firstName}/{count}
        defaults: { _controller: EventBundle:Default:index }

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    # import the new file

    _default_import:
        resource: "routing/default.yml"

If you check ``router:debug``, everything is exactly the same as before.

Notice that when you're importing another routing file, the key you use has
absolutely no meaning. I can change ``_default_import`` to anything it has
no effect,  as long as its unique. But the key for a route becomes its internal
"name", and *is* important. Also, notice that I can refer to the ``default.yml``
routing file just by using its relative path. This is equal to using the
``@EventBundle`` syntax.

Checking out the Generated Code
-------------------------------

Phew! Enough with routing, let's see this all in action. Head to the ``/event``
page in your browser.

    http://events.l/app.php/event

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

    You can find these templates in the "stubs" directory of the code download.

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

Adding CSS
~~~~~~~~~~

Since our page is still really ugly, I want to copy in some CSS and image
files I've prepared. Because, these files are meant to style the events section,
we should put them in the ``EventBundle``. I'll create a new ``Resources/public``
directory and put them there.

.. tip::

    You can find these CSS files in the "stubs" directory of the code download.

To add the stylesheets to our layout, we can take advantage of the ``stylesheets``
block that's in ``::base.html.twig`` by redefining it in ``layout.html.twig``:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/layout.html.twig #}
    {# ... #}

    {% block stylesheets %}
        <link rel="stylesheet" href="" />
    {% endblock %}

At this point, the only question is, what's the path to our CSS files?

The assets:install Command
..........................

This is actually a bit of a problem. Remember that only things in the ``web/``
directory are accessible by a browser. And since the CSS files live in our
``EventBundle``, they're not web accessible. Fortunately, Symfony provides
a console task called ``assets:install`` that solves this problem:

.. code-block:: bash

    $ php app/console assets:install --help

As the help message says, this command copies the ``Resources/public`` directory
from each bundle and puts it in a ``web/bundles`` directory so that its assets
are public. Unless you're on windows, I'd recommend passing the ``--symlink``
option, which creates a symbolic link instead of copying:

.. code-block:: bash

    $ php app/console assets:install --symlink

After running the command, you'll see that each bundle's ``Resources/public``
directory shows up in ``web/bundles`` and has a similar name. This includes
the files in our EventBundle. Problem, solved.

One thing to quickly note is that the ``assets:install`` command is run automatically
each time you run ``composer.phar install``. That's great, but if you prefer
symlinks over actually copying the files, you should edit the bottom of the
``composer.json`` script to activate the symlink option:

.. code-block:: json

    "extra": {
        " ... "
        "symfony-assets-install": "symlink",
    },

The Twig asset Function
.......................

Ok, back in ``layout.html.twig``, we can include link tags to our CSS files:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/layout.html.twig #}
    {# ... #}

    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('bundles/event/css/event.css') }}" />
        <link rel="stylesheet" href="{{ asset('bundles/event/css/events.css') }}" />
        <link rel="stylesheet" href="{{ asset('bundles/event/css/main.css') }}" />
    {% endblock %}

The `Twig asset function`_ helps you make sure that the path to your assets
is generated correctly. When we refresh, we have the beautiful layout we deserve.

Preview to Assetic
..................

Quickly, head back to ``layout.html.twig`` and replace the link tags with
a special Twig ``stylesheets`` tag. This bit of code comes from Assetic,
an asset management library integrated into Symfony. It's quite powerful and
beyond the scope of this first screencast, but I wanted you to see it in action:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/layout.html.twig #}
    {# ... #}

    {% block stylesheets %}
        {% stylesheets
            'bundles/event/css/*'
            filter='cssrewrite'
        %}
            <link rel="stylesheet" href="{{ asset_url }}" />
        {% endstylesheets %}
    {% endblock %}

One of its cool features is that we can point it at an entire directory, and
it'll include all of the CSS files. We also need to add our bundle to our ``config.yml``
file to activate our bundle with Assetic:

.. code-block:: yaml

    # app/config/config.yml
    # ...
    
    assetic:
        # ...
        bundles:    [EventBundle]

Generating URLs in a Template
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Before we move on, let's look at one of the new templates - ``index.html.twig``.
This template uses HTML5 tags, which isn't important, so don't worry if you're
not used to them. First, notice the ``for`` tag. This loops through an ``entities``
array that's passed to the template:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/index.html.twig #}
    {# ... #}
    
    {% for entity in entities %}
        <article>...</article>
    {% endfor %}

Further down look at how we link to the "show" page of each event. Instead
of hardcoding it, we'll let Symfony generate the URL from one of our routes.
If you look at the event routes that were generated earlier, you'll see one
called ``event_show`` that renders the ``show`` action. The route has an
``id`` wildcard, which we'll fill in with each event's id.

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # ...
    
    event_show:
        pattern:    /{id}/show
        defaults:   { _controller: "EventBundle:Event:show" }

To generate a URL in Twig, we can use the Twig ``path`` function. The first 
argument is the name of the route we're linking to. The second is an array
of variables - we use it to pass in a real value for the ``id`` wildcard:

.. code-block:: html+jinja

    <a href="{{ path('event_show', {'id': entity.id}) }}">
        {{ entity.name }}
    </a>

In the browser, you can see how each link generates almost the same URL, but
with a different id portion.

Rendering Dates in a Template
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Let's look at one last thing. The Event class's `date` field is represented
internally by a `PHP DateTime object`_. We saw this back in our play script
when we were creating new events. To actually render that as a string, we
can use Twig's `date filter`_. This takes a date and transforms it into a
string, based on the format we want. The format here uses the same format
as the good ol' fashioned `PHP date function`_:

.. code-block:: html+jinja

    <dd>
        {{ entity.time|date('g:ia / l M j, Y') }}
    </dd>

So, we didn't do a lot of work in this chapter, but we generated a ton of
code and went through a lot more of Symfony's core features. With the power
of code generators, you should feel like you can really get things done
quickly.

.. _`Twig asset function`: http://symfony.com/doc/current/reference/twig_reference.html#functions
.. _`PHP DateTime object`: http://www.php.net/manual/en/class.datetime.php
.. _`date filter`: http://twig.sensiolabs.org/doc/filters/date.html
.. _`PHP date function`: http://www.php.net/manual/en/function.date.php