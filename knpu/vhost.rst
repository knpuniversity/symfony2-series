Virtual Host Setup Extravaganza
===============================

We're using the built-in PHP web server, and it's awesome for development.
But it only handles one request at a time, so unless you only ever want 1
visitor, we're going to need something different.

To see how this might look, we'll invent a fake domain - events.l - and set
it up to point to our project. I'll use Apache, though it's more and more
common to use Nginx with PHP-FPM, because they're lightning fast. But all
the ideas are the same and we have a `page`_ on the official Symfony documentation
with some more details.

Creating a VirtualHost
----------------------

Step 1 is to modify the Apache configuration to create a new VirtualHost.
I *would* love to tell you where this lives, but this is one of those files
that hides in different places on each system setup. I use Apache via MacPorts,
so my virtual hosts live in the ``/opt`` directory.

.. tip::

    In Ubuntu, it lives in ``/etc/apache2/sites-available`` and each has
    its own file that needs to be activated. See `HTTPD Configuration`_.

The configuration we need is simple:

.. code-block:: apache

    <VirtualHost *:80>
        ServerName events.l
        DocumentRoot "/Users/leanna/Sites/starwarsevents/web"

        <Directory "/Users/leanna/Sites/starwarsevents/web">
            AllowOverride All
        </Directory>
    </VirtualHost>

Make sure the ``DocumentRoot`` points to the *web* directory of the project
so that *only* files inside it are accessible via your browser. Oh, and the
``AllowOverride All`` tells Apache that it's ok to use the ``.htaccess`` file
in the ``web/`` directory.

.. tip::

    For more information, or to see Nginx configuration, see `Configuring a Web Server`_.

Now, restart Apache. Yep, this command *also* varies across systems:

.. code-block:: bash

    $ sudo /opt/local/apache2/bin/apachectl restart

.. note::

    In Ubuntu, the command is:

    .. code-block:: bash

        sudo service apache2 restart

Finally, we need to add a hosts entry to ``/etc/hosts``:

    # /etc/hosts
    # ...

    127.0.0.1   events.l

This points ``events.l`` right back at our local computer. And this file
is always at the same location... except for windows.

We have a VirtualHost and the hosts entry, so let's go to ``http://events.l/app_dev.php``.
You *may* get a permissions error, and if you do, just ``chmod 777`` your cache
and logs directories for now. But longer-term, go back to the installation
chapter for details on how to fix this.

The 404 error is fine, because we don't have a homepage yet. Add the path to
the page we've been working on after ``app_dev.php`` to see it:

    http://events.l/app_dev.php/hello/skywalker/5

The dev and prod Environments
-----------------------------

Let's talk more about that ``app_dev.php`` script that's always in our URL.
A stock Symfony app has two different "modes" called "environments". When
you hit ``app_dev.php``, you're running your app in the ``dev`` environment.
This shows us big descriptive errors, automatically rebuilds the cache, and
makes the web debug toolbar popup. It's our debugging hero.

The other environment is ``prod`` and it kicks butt by being fast and by
turning off debugging tools. To run the app in the ``prod`` environment,
switch the URL from ``app_dev.php`` to ``app.php``:

    http://events.l/app.php/hello/skywalker/5

What!? 404 page! Outrageous!

We can't see the error, but we *can* tail the ``prod`` log file:

.. code-block:: bash

    $ tail app/logs/prod.log

Hmm, no route found. Ah, of course! Symfony compiles all of its configuration
into cache files. So if we change a ``routing.yml`` file, the cache needs
to be rebuilt. The ``dev`` environment does that for us, but for speed reasons,
``prod`` doesn't.

To do this, find our friend console and run the ``cache:clear`` command with
a ``--env=prod`` option.

.. code-block:: bash

    $ php app/console cache:clear --env=prod --no-debug

The means we're clearing the cache for the ``prod`` environment.

.. tip::

    If you haven't properly `fixed your permissions <ep1-install-permissions>` yet, you'll need to
    ``sudo chmod -R 777 app/cache`` after this command.

Refresh the page to see your functional page in the ``prod`` environment:

    http://events.l/app.php/hello/skywalker/5

Awesome! But I thought we had put ``app.php`` in the URL. Where did it go?
Our project came with a ``web/.htaccess`` file that have 2 pieces of goodness
in it.

First, it has a rewrite rule that sends all requests through ``app.php``, which
means we don't need to have it in our URL.

.. code-block:: apache

    # web/.htaccess
    # ...

    # If the requested filename exists, simply serve it.
    # We only want to let Apache serve files and not directories.
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteRule .? - [L]

    # Rewrite all other queries to the front controller.
    RewriteRule .? %{ENV:BASE}/app.php [L]

Awesome, the ``app.php`` was ugly anyways.

Second, even if you *do* put ``app.php`` in the URL, it notices that you don't
need this and redirects to remove it:

.. code-block:: apache

    # web/.htaccess
    # ...

    # Redirect to URI without front controller to prevent duplicate content
    # (with and without `/app.php`).
    RewriteCond %{ENV:REDIRECT_STATUS} ^$
    RewriteRule ^app\.php(/(.*)|$) %{ENV:BASE}/$2 [R=301,L]

The ``prod`` environment is only useful after you deploy. So let's get back to the ``dev``
environment so we can see errors.

.. _`page`: http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html
.. _`Configuring a Web Server`: http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html
.. _`HTTPD Configuration`: https://help.ubuntu.com/13.10/serverguide/httpd.html#http-configuration
