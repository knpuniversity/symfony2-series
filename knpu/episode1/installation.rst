Downloading & Configuration
===========================

Ok, let's get Symfony downloaded and setup. Head over to Symfony.com and
click "Download Now". If the site looks a little different for you, that's
because the Internet loves to change things after we record screencasts!
But no worries, just find the download page - all the steps will be the same.

Downloading Composer
--------------------

.. tip::

    Not familiar with Composer? Get spun up by going through the free
    `The Wonderful World of Composer`_ tutorial.

We're going to use a tool called `Composer`_ to get the project started.
Composer is PHP's package manager. That's a way of saying that it downloads
external libraries into our project. Oh yea, and it's also the most important
PHP innovation in years.

To get it, go to - you guessed it - GetComposer.org and click Download. Depending
on whether you have ``curl`` installed, copy one of the two install lines.

.. note::

    If you're using Windows, someone made an `Installer for you`_ :).

Open up your terminal and start typing wildly:

.. code-block:: bash

    $ sdfSFFOOLOOBOO

Hmm, ok, that didn't work. So let's try pasting the command instead. If you
have PHP 5.4 installed, run this anywhere: we'll use PHP's built-in web server.
If you don't, get with it! PHP 5.3 is ancient. But anyways, make sure you
have Apache setup and run the command at the server's document root:

.. code-block:: bash

    $ curl -s https://getcomposer.org/installer | php

This downloads an executable PHP file called ``composer.phar``. If Composer
complains with any warnings or errors, follow its recommendations to make
sure your system doesn't panic when we use it.

Downloading the Standard Distribution
-------------------------------------

Go back to the Symfony.com download page and copy the ``create-project``
Composer command. Change the target directory to say statwarsevents and 
the last part to say ``@stable``. This is the version number and ``@stable`` 
is a neat way of making sure we get the latest and greatest.

.. code-block:: bash

    $ php composer.phar create-project symfony/framework-standard-edition starwarsevent @stable

This tells Composer to download the ``symfony/framework-standard-edition``
package into the ``starwarsevents`` directory. That's all you need to know
for now - we're going to explore Composer later.

Ok, so downloading everything is going to take a few minutes. Composer is
busy with 2 things at once. First, it's downloading a little example project
that uses the Symfony libraries. Even while Composer is doing its thing,
we can open a new terminal and move into the new directory. It contains a
relatively small number of files and directories:

.. code-block:: bash

    $ cd starwarsevent
    $ ls -l

Second, our project depends on a bunch of 3rd-party libraries and Composer
is downloading these into the ``vendor/`` directory. If you
run ``ls vendor/``, you'll see that more and more things are popping up here.

When that finishes, our terminal will become self-aware and start asking
us configuration questions. Just hit enter through all of these - we can
tweak the config later.

Setup Checks and Web Server Config
----------------------------------

Let's make sure that our computer is ready to run Symfony. The project has
a little PHP file - ``web/config.php`` - that checks our computer and tells
us if we're super-heroes on system setup or if our machine is missing some
libraries.

We need to navigate to this script in our browser. So if you have PHP 5.4
or higher, just use the built-in PHP web server. Run this command from the
root of our project:

.. code-block:: text

    $ php app/console server:run

If you get an error or are using Apache, we have a note on this chapter's
page about all that.

.. tip::

    This is just a shortcut for:

    .. code-block:: bash

        $ cd web/
        $ php -S localhost:8000

We now have a web server running at ``http://localhost:8000``, which uses
the ``web/`` directory as its doc root. We can just surf directly to the
``config.php`` file:

    http://localhost:8000/config.php

.. note::

    If you're using Apache instead and downloaded the project to your Apache
    document root, then you can go to "localhost" and find your way to the
    ``config.php`` script:

        http://localhost/starwarsevents/web/config.php

We'll talk more about a proper web server setup later.

If you see any scary "Major Problems", you'll need to fix those. But feel
free to ignore any "Minor Problems" for now.

Permissions Craziness
~~~~~~~~~~~~~~~~~~~~~

You may see two major issues - permissions problems with the ``cache`` and
``logs`` directories. Ok, since this can be *really* annoying, we gotta get
it fixed.

Basically, we need the cache and logs directories to be writable by our terminal
user *and* our web server's user, like ``www-data``. And if a cache file
is created by one user, that file needs to be modifiable by the other user.
It's an epic battle of 2 UNIX users needing to mess with the same set of
files.

.. tip::

    If you're screaming , "If Symfony just creates cache files with 777 permissions,
    this wouldn't be an issue!", you're right! But that would be a security
    no-no for shared hosting #sadpanda

Of course, you're awesome and are using the PHP built-in web server. For us,
our terminal user *is* our PHP web server user, so we don't have any issues.

If you're using Apache or *are* having issues, check out the sidebar on this
page with some tips.

.. _ep1-install-permissions:

.. sidebar:: Fixing Permissions Issues

    The easiest permissions fix is to add a little ``umask`` function to
    the top of 2 files. Pop open your project in your favorite editor, we
    *love* PhpStorm.

    Open up ``app/console`` and ``web/app_dev.php``. You'll see a little
    ``umask`` line there - uncomment this::

        #!/usr/bin/env php
        <?php

        umask(0000);
        // ...

    .. note::

        What the heck? The ``umask`` function makes it so that cache and logs
        files are created as 777 (world writable).

    Once you're done, set the permissions on the two cache and logs directories:

    .. code-block:: bash

        $ chmod -R 777 app/cache/* app/logs/*

    You shouldn't have any more issues, but if you do, just set the permissions
    again.

    This method *can* be a security issue if you're deploying to a shared
    server. Check out Symfony's `installation chapter`_ for details on other
    ways to setup your permissions.

Loading up the First Page
-------------------------

Ok, we're ready to get to work. Check out our first real Symfony page, by
hitting the ``app_dev.php`` file in your browser:

  http://localhost:8000/app_dev.php

Hopefully a cute welcome page greets you. The project came with a few demo
pages and you're looking at the first one. The code for these lives in the
``src/Acme/DemoBundle`` directory. You can see the rest of the demo pages
by clicking the "Run The Demo" button.

.. tip::

    If you're using Apache with the same setup as we've done, then the URL
    will be:

    .. code-block:: text

        http://localhost/starwarsevents/web/app_dev.php

Directory Structure
-------------------

Without writing any code, we already have a working project. Yea, I know,
it's kinda lame and boring now, but it *does* have the normal directory
structure.

app
~~~

Let's look at the ``app/`` dir. It holds configuration and a few other things
that tie the whole project together. If your app were a computer, this would
be the motherboard: it doesn't really do anything, but it controls everything.

Most of our code will live somewhere else, in directories called "bundles".
These bundles are activated in the ``AppKernel`` class and configured in
the ``config.yml`` file inside ``app/config/``.

For example, there's a core bundle called FrameworkBundle. It controls a lot
of things, including the session timeout length. So if we needed to tweak
this, we'd do it under the ``framework`` config key:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        session:
            cookie_lifetime: 1440

Routes are the URLs of your app, and they also live in this directory in
the ``routing.yml`` file. We'll master routes in a few minutes.

You can ignore everything else in the ``app/config/`` directory - we'll talk
more about them when we cover environments.

The ``app/`` directory is also where the base layout file (``app/Resources/views/base.html.twig``)
and console script (``app/console``) live. More on those soon!

bin
~~~

After ``app/``, we have ``bin/``. You know what? Just forget you ever saw
this directory. It has some executable files that Composer added, but nothing
we'll ever need at this point.

.. note::

    Curious about the secrets behind Composer and this ``bin/`` directory.
    Then do some `homework`_!

src
~~~

*All* the magic and code-writing happens in the ``src/`` directory. We're
going to fill it with sub-directories called "bundles". The idea is that
each bundle has the code for a single feature or part of your app.

We're about 10 seconds away from nuking it, but if you want to enjoy the
demo code, it lives here inside AcmeDemoBundle.

vendor
~~~~~~

We already know about the ``vendor/`` directory - this is where Composer
downloads outside libraries. It's kinda fat, with a ton of files in it. But
no worries, you don't need to look in here, unless you want to dig around
in some core files to see how things work. Actually, I love doing that! We'll
tear open some core files later.

web
~~~

The last directory is ``web/``. It's simple: this is your document root,
so put your public stuff here, like CSS and JS files.

There are also two PHP files here that actually execute Symfony. One loads
the app in the ``dev`` environment (``app_dev.php``) and the other in the
``prod`` environment (``app.php``). More on this environment stuff later.

Removing Demo Code
------------------

It's time to get serious, so let's get all of that demo code out of the way.
First, take your wrecking ball to the ``src/Acme`` directory:

.. code-block:: bash

    $ rm -rf src/Acme

Next, take out the reference to the bundle in your ``AppKernel`` so Symfony
doesn't look for it when it's loading::

    // app/AppKernel.php
    // ...
    
    if (in_array($this->getEnvironment(), array('dev', 'test'))) {
        // delete the following line
        $bundles[] = new Acme\DemoBundle\AcmeDemoBundle();
        $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
        $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();
    }

Finally, get rid of the ``_acme_demo`` route import in the ``routing_dev.yml``
file to fully disconnect the demo bundle:

.. code-block:: yaml

    # app/config/routing_dev.yml
    # ...

    # Please! Delete me (the next 2 lines!)
    _acme_demo:
        resource: "@AcmeDemoBundle/Resources/config/routing.yml"

Refresh your browser. Yes, an error! No, I'm serious, this is good - it's
telling us that the page can't be found. The demo page that was here a second
ago is gone. Congratulations on your completely fresh Symfony project.

Setting up git
--------------

Let's make our first commit! We're going to use git but not much is different
if you use something else. If you don't use version control, shame!

If you already have a ``.git`` directory, get rid of it! Otherwise, you'll
inherit the history from Symfony's standard distribution, which is about
1000 commits.

.. code-block:: text

    $ rm -rf .git

Create a new repository with ``git init``:

.. code-block:: text

    $ git init

Now don't go crazy with adding files: there are some things that we don't
want to commit. Fortunately, Symfony gives us a solid ``.gitignore`` file
to start with.

The ``bootstrap.php.cache`` file is generated when you run Composer. It's
super important, though you'll never need to look at it. Regardless, since
it's generated automatically, we don't need to commit it.

The ``cache`` and ``logs`` directories also have generated contents, so we
should ignore those too.

The ``app/config/parameters.yml`` file holds all server-specific config, like
your database username and password. By ignoring it, each developer can keep
their own version of the file.

To make life easier, we *do* commit an example version of the file called
``parameters.yml.dist``. That way, a new dev can actually create their ``parameters.yml``
file, without guessing what it needs to look like.

We also ignore the ``vendor/`` directory, because Composer downloads everything
in here for us. If a new dev clones the code, they can just run ``php composer.phar install``
and **bam**, their ``vendor/`` directory looks just like yours.

Everything is being ignored nicely so let's go crazy and add everything to
git and commit:

.. code-block:: text

    git add .
    git commit -m "It's a celebration!!!!!!!"

.. tip::

    Unless you want to accidentally commit vacation photos and random notes
    files, don't run try to avoid running ``git add .``, or at least run
    ``git status`` before committing.

Find some friends! It's time to celebrate the first to your awesome project.
Do some jumping high fives, grab a pint, and make a Chewbacca cry.

.. _`Composer`: https://getcomposer.org/
.. _`Installer for you`: https://getcomposer.org/doc/00-intro.md#installation-windows
.. _Packagist.org: https://packagist.org/
.. _`installation chapter`: http://symfony.com/doc/current/book/installation.html#configuration-and-setup
.. _`The Wonderful World of Composer`: http://knpuniversity.com/screencast/composer
.. _`homework`: https://getcomposer.org/doc/articles/vendor-binaries.md#what-happens-when-composer-is-run-on-a-composer-json-that-has-dependencies-with-vendor-binaries-listed-
