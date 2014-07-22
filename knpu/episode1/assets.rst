Less Ugly with CSS and JavaScript
=================================

Things are still too ugly. I'll copy some CSS and image files I wrote up
after the last chapter. We could put these in the ``web/`` directory - it
is publicly accessible afterall.

But there's a trick I want to show you, so let's copy them to a new ``Resources/public``
directory in ``EventBundle``. Get these files by downloading the code for this screencast
and looking in the ``resources`` directory. I already downloaded and copied that
directory into my project for simplicity:

.. code-block:: bash

    cp -r resources/public src/Yoda/EventBundle/Resources

Now we just need to add some ``link`` tags to ``base.html.twig``. In fact,
the layout already has a ``stylesheets`` block - let's put the link tags
there:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}

    {% block stylesheets %}
        <link rel="stylesheet" href="???" />
    {% endblock %}

Why put them in a block? I'll show you exactly why in `Episode 2`_, but
basically this will let us include extra CSS files on only one page. The
page-specific CSS file will show up *after* whatever we have in this block.

The assets:install Command
--------------------------

Wait a second - what should we put in the ``href``? Only things in the ``web/``
directory are web-accessible, and these *aren't* in there. What was I thinking?

Ok, so Symfony has a dead-simple trick here. Actually, it's console again,
with its ``assets:install`` command. Get some help info about it first:

.. code-block:: bash

    $ php app/console assets:install --help

As it says, the command copies the ``Resources/public`` directory from each
bundle and moves it to a ``web/bundles`` directory. This little trick makes
our bundle assets public!

And unless you're on windows, run this with the ``--symlink`` option: it
creates a symbolic link instead of copying the directory:

.. code-block:: bash

    $ php app/console assets:install --symlink

Now, our bundle's ``Resources/public`` directory shows up as ``web/bundles/event``.
There's even a few core bundles that use this trick.

assets:install with Composer
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

There's a secret. When we run ``php composer.phar install``, the ``assets:install``
command is run automatically at the end. But it's not black-magic, there's
just a ``scripts`` key in ``composer.json`` that tells it to do this and
a few other things.

The uncool part about this is that it runs the command *without* the ``--symlink``
option. When the directories are copied instead of symlinked, testing CSS
changes is a huge pain.

Edit the bottom of the ``composer.json`` script to activate the symlink option:

.. code-block:: json

    "extra": {
        " ... "
        "symfony-assets-install": "symlink",
    },

The ``extra`` key is occasionally used in random ways like this. If you ever
need to do anything else here, the README of some library will tell you.

The Twig asset Function
-----------------------

Ok, *now* lets finish up the ``link`` tags:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}

    {% block stylesheets %}
        <link rel="stylesheet" href="{{ asset('bundles/event/css/event.css') }}" />
        <link rel="stylesheet" href="{{ asset('bundles/event/css/events.css') }}" />
        <link rel="stylesheet" href="{{ asset('bundles/event/css/main.css') }}" />
    {% endblock %}

This is just the plain web path, except for the `Twig asset function`_. This
function doesn't do much, but it will make putting our assets on a CDN really
easy later. So whenever you have a path to a CSS, JavaScript or image file,
wrap it with this.

Preview to Assetic
------------------

This is cool. BUT, I want to give you a sneap peek of Assetic - a library
that integrates with Symfony and lets you combine and process CSS and JS
files:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}

    {% block stylesheets %}
        {% stylesheets
            'bundles/event/css/event.css'
            'bundles/event/css/events.css'
            'bundles/event/css/main.css'
            filter='cssrewrite'
        %}
            <link rel="stylesheet" href="{{ asset_url }}" />
        {% endstylesheets %}
    {% endblock %}

When we refresh, everything still looks the same. BUT, we've laid the foundation
for being able to do things like use SASS and combining everything into 1
file for speed. We talk about Assetic more in `Episode 4`_.

.. _`Twig asset function`: http://symfony.com/doc/current/reference/twig_reference.html#functions
.. _`Episode 2`: http://knpuniversity.com/screencast/symfony2-ep2/basic-security#adding-css-to-a-single-page
.. _`Episode 4`: http://knpuniversity.com/screencast/symfony2-ep4/assetic
