We (mostly) don't care about your CSS/JS
========================================

We'll start by talking about CSS and JS files, and just how much Symfony
*doesn't* care about these. I mean that in a good way - you don't necessarily
need a PHP Framework to help you include a JavaScript file.

Open up your base template and find the weird ``stylesheets`` tag there:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}

    {% block stylesheets %}
        {# link tag for bootstrap... #}

        {% stylesheets
            'bundles/event/css/event.css'
            'bundles/event/css/events.css'
            'bundles/event/css/main.css'
            filter='cssrewrite'
        %}
            <link rel="stylesheet" href="{{ asset_url }}" />
        {% endstylesheets %}
    {% endblock %}

Symfony does have some *optional* tricks for assets, and this is one of them.
For now, just remove this whole block and replace it with 3 good, old-fashioned
``link`` tags:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}

    {% block stylesheets %}
        {# link tag for bootstrap... #}
        
        <link rel="stylesheet" href="/bundles/event/css/event.css" />
        <link rel="stylesheet" href="/bundles/event/css/events.css" />
        <link rel="stylesheet" href="/bundles/event/css/main.css" />
    {% endblock %}

Login with Wayne and password waynepass (party on) and then open up the HTML
source on the homepage.

No Symfony magic here - this is just pure frontend code that points to real 
files in the ``web/bundles/event/css`` directory. And since the ``web/`` 
directory is the document root, we don't include that part.

Making Bundle Assets Public
---------------------------

The only thing Symfony is doing is helping move these files from their original
location inside EventBundle's ``Resources/public`` directory. But remember from
`episode 1`_ that Symfony has an ``assets:install`` console command. Run
this again with a ``symlink`` option:

.. code-block:: bash

    php app/console assets:install --symlink

.. note::

    Symbolically links ``src/Yoda/EventBundle/Resources/public`` to ``web/bundles/event``.

This creates a symbolic link from ``web/bundles/event`` to that ``Resources/public``
directory. This is just a cheap trick to expose CSS or JS files to the ``web/``
directory that live inside a bundle. This lets us point at a real, physical
file with the ``link`` tag.

.. tip::

    The ``--sylmink`` option may not work on all Windows setups (depending)
    on your permissions.

You can also just put your CSS and JS files directly into the ``web/`` directory.
In fact, that's a great idea.

The Twig asset Function
-----------------------

Take your simple link tag ``href`` and wrap it in a Twig ``asset`` function:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}

    {% block stylesheets %}
        {# link tag for bootstrap... #}
        
        <link rel="stylesheet" href="{{ asset('bundles/event/css/event.css') }}" />
        <link rel="stylesheet" href="{{ asset('bundles/event/css/events.css') }}" />
        <link rel="stylesheet" href="{{ asset('bundles/event/css/main.css') }}" />
    {% endblock %}

I want you to notice that the path isn't changing, except that we don't need
the first ``/`` anymore. When you've got this, refresh. The site still looks
great and the HTML source looks exactly as it did before, so ``asset`` 
isn't doing anything . . . yet.

.. _`episode 1`: http://knpuniversity.com/screencast/symfony2-ep1/assets#the-assets-install-command
