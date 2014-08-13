Applying a Minification Filter
------------------------------

Open up the built CSS file. Ugh. All that nasty whitespace that my user's
are going to download. Is there nothing we can do?

Reason #1 to use Assetic was because of its filters, like ``cssrewrite``.
It also has filters to minify assets. Your best option is to use a binary
called uglifycss through Assetic. There's also an ``uglify-js``.

Intalling uglifycss with npm
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

We're also going to get a crash-course in ``npm``, the Composer for node.js.
*Very* Rebel hipster of us.

First, create a nearly empty ``package.json`` file - this is like the ``composer.json``
for node libraries:

.. code-block:: json

    {
    }

Next, install uglify!

.. code-block:: bash

    npm install uglifycss --save

If you don't have ``npm``, install `node.js`_ to get it. This installs ``uglifycss``
into a ``node_modules`` directory. It also updated our ``package.json`` file
to have this library. Another developer on the project only needs to run
``npm install`` to download uglify. Nice. In fact, let's add ``node_modules/``
to our ``.gitignore`` file, just like we did for the ``vendor/`` directory:

.. code-block:: text

    # .gitignore
    # ...

    /node_modules

Configuring and Using the Filter
--------------------------------

The rest is a breeze. Configure the filter in ``config.yml`` under the ``assetic``
key. Basically, add an ``uglifycss`` filter and point it to where the new
executable lives:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    assetic:
        # ...
        filters:
            cssrewrite: ~
            uglifycss:
                bin: %kernel.root_dir%/../node_modules/.bin/uglifycss

That ``node_modules.bin/uglifycss`` is a physical binary that was downloaded.
The ``%kernel.root_dir%`` is a parameter that points to ``app/``. We'll
talk about parameters in a second.

To actually use uglify, add it to the ``stylesheets`` block:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}
    
    {% stylesheets
        'bundles/event/css/event.css'
        'bundles/event/css/events.css'
        'bundles/event/css/main.css'
        filter='cssrewrite'
        filter='uglifycss'
        output='css/built/layout.css'
    %}
        <link rel="stylesheet" href="{{ asset_url }}" />
    {% endstylesheets %}

Head back to the ``dev`` environment and refresh. And when we look at one
of the CSS files, no more nasty whitespace.

Applying a Filter only in the prod Environment
----------------------------------------------

Ok, I got a little over-excited about whitespace and made working with CSS
hell. Our browser thinks that every style is coming from line 1 of these files...
because there's only one line in each. Good luck frontend people!

Really, I want the ``uglifycss`` filter to *only* run in the ``prod`` environment.
We can do just this by adding a ``?`` before the filter name:

.. code-block:: html+jinja

    {# app/Resources/views/base.html.twig #}
    {# ... #}
    
    {% stylesheets
        'bundles/event/css/event.css'
        'bundles/event/css/events.css'
        'bundles/event/css/main.css'
        filter='cssrewrite'
        filter='?uglifycss'
        output='css/built/layout.css'
    %}
        <link rel="stylesheet" href="{{ asset_url }}" />
    {% endstylesheets %}

Refresh in the ``dev`` environment. Cool, whitespace restored. Now switch
over to the ``prod`` environment, clear your cache and re-dump the assets:

.. code-block:: bash

    php app/console cache:clear --env=prod
    php app/console assetic:dump --env=prod

Now, ``layout.css`` is a physical file *and* has no whitespace. That's perfect.

Assetic with JavaScript Files
-----------------------------

We just did this all with CSS, but it's all the same with JavaScript. Instead
of a ``stylesheets`` tag, there's a ``javascripts`` tag that works exactly
the same. Symfony has a `cookbook`_ entry about this, but seriously, it's
no different at all. Even the minification is the same, except that the
library is called ``uglify-js``.

In other words, you now know pretty much everything you need to about Assetic.
If you start using it a lot and notice your pages loading slower and
slower, check out the ``use_controller`` option that's mentioned on that
same page.

Ok, back to work!

.. _`UglifyJs`: http://bit.ly/sf2-uglify
.. _`node.js`: http://nodejs.org/
.. _`cookbook`: http://symfony.com/doc/current/cookbook/assetic/asset_management.html
