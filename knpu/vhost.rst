Virtual Host Setup Extravaganza
===============================

To make all of this look a little more realistic, I want to show you how you'd
actually setup Symfony in a production environment. Instead of going to ``localhost``
to see our application, we'll make a fake domain - events.local - and use
it to play with our site.

I'll walk you through how to do this in Apache, though the exact details
will differ across operating systems and setups. And if you're not using Apache, 
no problem! You'll just need to take what we do here and adapt it to your webserver.

The first step to setting up our fake domain is to create an Apache VirtualHost.
The location of your new virtualhost config will depend on your setup. I
use Apache via MacPorts, so my virtual hosts live in the ``/opt`` directory.
The virtualhost needed for Symfony is pretty simple. The most important thing
to notice is that the web root for the new host is actually the *web* directory
of the project. This means that only files inside ``web/`` will be accessible
via your browser. Also, the ``AllowOverride All`` lets us use an ``.htaccess``
file in the ``web/`` directory:

.. code-block:: apache

    <VirtualHost *:80>
        ServerName events.local
        DocumentRoot "/Users/leanna/Sites/starwarsevents/web"

        <Directory "/Users/leanna/Sites/starwarsevents/web">
            AllowOverride All
        </Directory>
    </VirtualHost>

.. tip::

    For more information, or to see Nginx configuration, see `Configuring a Web Server`_.

Now, restart Apache. Again, this will vary depending on your setup. Finally,
add a new entry to your ``/etc/hosts`` file, so that ``events.local`` actually
points right back at your local computer. For once, this is the same on
*all* computers... except for windows:

    # /etc/hosts
    # ...

    127.0.0.1   events.local

Now, let's go use our new domain! Go to ``http://events.local/app_dev.php``
to see our app. The error is fine, because we don't actually have a homepage
yet. Add the path to the page we've been working on after ``app_dev.php`` to
see it:

    http://events.local/app_dev.php/hello/skywalker/5

The dev and prod Environments
-----------------------------

Yeah! This is starting to look more like a real site. Out of the box,
you can run any Symfony app in two different "modes", called "environments".
When you hit the ``app_dev.php`` like we've been doing, you're running your
application in the ``dev`` environment. This is great for debugging, because
Symfony's cache is automatically rebuilt, error pages are explanatory, and
the web debug toolbar shows up.

But a Symfony app can also be executed in the ``prod`` environment, where
things are optimized for speed, error messages are hidden, and debugging tools
are off. To run our app in the ``prod`` environment, just switch from ``app_dev.php``
to ``app.php``:

    http://events.local/app.php/hello/skywalker/5

Now, when you do this, there's a good chance you'll see the "white screen
of death". If this ever happens to you, you can always check the ``app/logs/prod.log``
file to see the error:

.. code-block:: bash

    $ tail app/logs/prod.log

But in most cases, the white screen of death just means that you need to
clear your cache. This is because the ``prod`` environment is optimized for
speed, so when your application changes, you need to flush the cache manually.
To do this, run the `cache:clear` command and pass it two options: ``env=prod``
and ``--no-debug``. The first flag means you're clearing the cache for the
``prod`` environment. The second flag means you want to run this task with
debug mode off, which may make a difference in the way some cache is generated:

.. code-block:: bash

    $ php app/console cache:clear --env=prod --no-debug

.. tip::

    The ``--no-debug`` actually isn't needed - it's assumed when you execute
    a command in the ``prod`` environment.

Once your cache is cleared, you can execute your app in the ``prod`` environment.

    http://events.local/app.php/hello/skywalker/5

To make your URL's even cleaner, you can remove the ``app.php`` altogether.
This is because Symfony comes with an ``.htaccess`` file, which says that all
URL's should be handled by ``app.php`` by default.

    http://events.local/hello/skywalker/5

This gives us perfectly clean URLs. Of course, when we're developing, we'll
almost always use the ``dev`` environment, because seeing our errors is awesome.

And that's it! Now, let's generate some code!

.. _`Configuring a Web Server`: http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html