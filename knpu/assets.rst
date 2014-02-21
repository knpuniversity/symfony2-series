Less Ugly with CSS and JavaScript
=================================

Since our page is still really ugly, I want to copy in some CSS and image
files I've prepared. Because, these files are meant to style the events section,
we should put them in the ``EventBundle``. I'll create a new ``Resources/public``
directory and put them there.

.. tip::

    You can find these CSS files in the ``resources/public`` directory of
    the code download.

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

.. _`Twig asset function`: http://symfony.com/doc/current/reference/twig_reference.html#functions
