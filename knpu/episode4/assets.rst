Assets
======

For the most part, handling CSS and JS files in your project is easy. In
the first screencast, we added a fancy ``stylesheets`` Twig tag. Let's remove
that for now. Instead, let's just bring in our three CSS files by using
good-old-fashioned ``link`` tags:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}

    {% block stylesheets %}
        {# link tag for bootstrap... #}
        
        <link rel="stylesheet" href="{{ asset('bundles/event/css/event.css') }}" />
        <link rel="stylesheet" href="{{ asset('bundles/event/css/events.css') }}" />
        <link rel="stylesheet" href="{{ asset('bundles/event/css/main.css') }}" />
    {% endblock %}

Making Bundle Assets Public
---------------------------

The three CSS files are actually located inside our ``EventBundle``. Recall
from episode 1 that we placed these inside a ``Resources/public`` directory
of our bundle. By running the ``assets:install`` command, the ``Resources/public``
directory is copied to ``web/bundles/event``:

.. code-block:: text

    Symbolically links src/Yoda/EventBundle/Resources/public to web/bundles/event

    $ php app/console assets:install --symlink

This is fabulous because it means that we can put our public asset files
inside our bundle, but still make them available in the ``web/`` directory.

The Twig asset Function
-----------------------

The interesting thing with the new ``link`` tags is the Twig ``asset()`` function.
This is a subtle, but really cool function that you should use whenever you're
rendering the path to CSS, JS, image or any other static files. The value
you pass into the ``asset()`` function is simply the path to your asset, starting
at the ``web/`` directory.

Busting Browser Cache
~~~~~~~~~~~~~~~~~~~~~

Refresh the page so we can see our stylesheets load correctly. One reason
the ``asset()`` function is so cool is that it allows you to "bust" browser
cache. Go to ``app/config/config.yml`` and find the ``framework`` ``templating``
key. Add the ``assets_version`` key, set it to some value and refresh:

.. code-block:: yaml

    # app/config/config.yml
    # ...
    
    framework:
        # ...
        templating: { engines: ['twig'], assets_version: 1 }

When we view the source code, there's a ``?1`` at the end of our CSS files.

.. code-block:: html

    <link href="/bundles/event/css/main.css?1" rel="stylesheet" />

In fact, there will now be a ``?1`` at the end of *everything* that uses the
the ``asset`` function. Since browser cache problems suck, increment this
number before you deploy to eliminate the problem. These aren't the assets
you're looking for. 

Since this line is getting a bit long, let's move each key onto its own line.

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: 1

In YAML, this means exactly the same thing as we had before.

If you want to get fancy, add an ``assets_version_format`` configuration option:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: 1
            assets_version_format: "%s?v=%s"

This looks a little funny: pass it a string with 2 ``%s`` placeholders. The
first represents the path to the asset and the second is the version. Refresh
the page to try it out. But instead of our beautiful page, we see a strange
error that mentions a non-existent "parameter":

.. highlights::

    You have requested a non-existent parameter "s?v=".

An Aside: Dependency Injection Parameters
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A "parameter" is a little variable that you can define and use inside ``config.yml``
or any other files where you define services. If you want to create a parameter,
just add a "parameters" key and then start adding some keys beneath it. We
can use it in any configuration file by surrounding it with two percent signs.

.. code-block:: yaml

    # app/config/config.yml
    # ...
    
    # an example of using a parameter
    parameters:
        routing_filename: routing.yml
    
    framework:
        # ...
        router:
            resource: "%kernel.root_dir%/config/%routing_filename%"

This is a great way to re-use information without repeating yourself.

We actually already have a bunch of parameters that we've defined in ``parameters.yml``
and used in ``config.yml``. One important note here is that there's nothing
special at all about ``parameters.yml``. It's imported just like any other
config file and we could even put its parameters right into ``config.yml``.
So then, why do we bother having the ``parameters.yml`` file? In the first
screencast we added ``parameters.yml`` to our ``.gitignore`` file so that
it won't be committed to the repository:

.. code-block:: text

    # .gitignore
    # ...
    app/config/parameters.yml

This means that every developer and every server will have its own copy of
this file. The ``parameters.yml`` file allows us to isolate all of our server-specific
configuration into one, small file.

Assets Version Format
~~~~~~~~~~~~~~~~~~~~~

Let's get all the way back to our error. Since a parameter is used by surrounding
it with two percent signs, our ``assets_version_format`` string looks like
a parameter. Since we're not actually trying to use a parameter here, we
can "escape" the percent character by adding another percent:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        templating:
            engines: ['twig']
            assets_version: 1
            assets_version_format: "%%s?v=%%s"

Refresh again and view the source:

.. code-block:: html

    <link href="/bundles/event/css/main.css?v=1" rel="stylesheet" />

By using the ``asset()`` function with some configuration, we easily bust
the browser cache. The ``asset()`` function is also useful for other things,
like pointing all of your assets to a CDN. It's easy to use, and if you're
curious how, check out Symfony's configuration reference section for more
details.

Next we going to learn about Assetic and some really cool things that this
library will allow you to do with your assets. If you choose not to use assetic,
then you already know pretty much everything there is to know about including
assets in Symfony. If you have some CSS or JavaScript files that need to be
included on all pages, just add them inside your base layout. And if you have
one or two CSS or JavaScript files that you need to include only on one page,
you can override the correct block from your base template and use Twig's
``parent()`` function. We saw this trick in episode 2 when building our login
page:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Login/login.html.twig #}

    {% block stylesheets %}
        {{ parent() }}
        
        <link rel="stylesheet" href="{{ asset('bundles/user/css/login.css') }}" />
    {% endblock %}
    
    {# ... #}

In this example, the ``parent()`` function ensures that everything in the
base template's ``stylesheets`` block is printed first and then we add our
extra CSS file at the end.
