Combining and Minifying CSS & JS
================================

The second big feature of Assetic is its ability to combine our CSS or JS
into a single file. First, clear your cache and switch over to the ``prod``
environment:

.. code-block:: bash

    php app/console cache:clear --env=prod

.. code-block:: text

    http://localhost:8000/app.php

Things still look nice. But view the source. Woh! Our 3 CSS files are
now one:

    <link rel="stylesheet" href="/css/8e49901.css?v=5-return-of-the-jedi" />

.. tip::

    If your page does *not* look fine. that's actually normal! Keep reading
    about how to dump your assets.

In the ``dev`` environment, Symfony keeps our 3 files so we can debug more
easily. In ``prod``, it puts them all together.

More Speed: assetic:dump
------------------------

But when your browser requests this one CSS file, it's still being executed
through a dynamic Symfony route. For production, that's *way* too slow. And
depending on your setup, it may not even be working in the ``prod`` environment.

The secret? The ``assetic:dump`` console command. Run it in the ``prod``
environment.

.. code-block:: bash

    php app/console assetic:dump --env=prod

This wrote a physical file to the ``web/css`` directory. And when we refresh,
the web server loads this file instead of going through Symfony.

When we deploy our application, this command will be part of our deploy process.

Controlling the Output Filename
-------------------------------

Assetic gave our CSS file a weird name - ``8e49901.css`` for me, which is
just a random name it created. But we can control this by adding an ``output``
option to the ``stylesheets`` tag:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}
    
    {% stylesheets
        'bundles/event/css/event.css'
        'bundles/event/css/events.css'
        'bundles/event/css/main.css'
        filter='cssrewrite'
        output='css/built/layout.css'
    %}
        <link rel="stylesheet" href="{{ asset_url }}" />
    {% endstylesheets %}

Refresh and look at the source. Woops, nothing changed! I can't forget to
clear my cache when I'm in the ``prod`` environment:

.. code-block:: bash

    php app/console cache:clear --env=prod

Now the link tag points to this exact spot:

.. code-block:: html

    <link rel="stylesheet"
        href="/css/built/layout.css?v=5-return-of-the-jedi" />

And of course, if we dump assetic, it writes this file instead of the one
with the funny name:

.. code-block:: bash

    php app/console assetic:dump --env=prod

I also like to put all my built files into ``css/built`` and ``js/built``
directories. Add both of these to your ``.gitignore`` file. There's
no need to commit these - we can build them at any time:

.. code-block:: text

    # .gitignore
    # ...
    
    /web/css/built
    /web/js/built
