Downloading & Configuration
===========================

Start your project by going to symfony.com and clicking "Download Now". If
the site looks different when you try this, just find the download page, the
steps will be the same. We'll use an awesome tool called Composer to get
the project started.

Downloading Composer
--------------------

To get Composer, go to - you guessed it - GetComposer.org. Depending on whether
you have ``curl`` installed, copy one of the two install lines and execute
it in your terminal.

If you have Apache installed, run this from inside your web server's document
root. If you don't, but have PHP 5.4 installed - you can run this command
from anywhere. We'll use PHP's built-in web server to run the site.

.. code-block:: bash

    curl -s https://getcomposer.org/installer | php

Running this command downloads and prepares an executable PHP file called
``composer.phar``. Composer may give you warnings or errors when running this
command: make sure to follow its recommendations so that your system is ready
to work.

Composer works by downloading packages, many of which can be found at `Packagist.org`_.

Downloading the Standard Distribution
-------------------------------------

Back at the command line, tell Composer to create a new project with the
`create-project` command. Change the last part to say ``@stable`` - this
is the version number of Symfony you want and this makes sure you get the
latest and greatest:

.. code-block:: bash

    php composer.phar create-project symfony/framework-standard-edition starwarsevent @stable

This tells Composer to download the ``symfony/framework-standard-edition`` library
into the ``starwarsevents`` directory.

What we're downloading isn't actually Symfony. It's a fully-functional, pre-started
project that's built with Symfony. This is the actual place we'll be
putting our code into, and so far, it's just a few directories and a hand full
of files.

This first step may take some time to run. That's because Composer is doing
2 things at once. First, it's downloading a fully-functional, pre-started
project that's built with Symfony. If you open a new terminal tab, you can
move into this directory almost immediately. It contains a relatively small
number of files and directories:

.. code-block:: bash

    cd starwarsevent
    ls -l

The second thing Composer is doing is downloading all of the third-party
libraries that Symfony requires into the ``vendor/`` directory of that project.
As Composer continues to work, you can actually see more and more directories
being added to that ``vendor/`` directory. We'll use Composer more later when
we start bringing in more outside libraries.

.. tip::

    Not familiar with Composer? Get spun up by going through the free
    `The Wonderful World of Composer`_ tutorial.

As Composer finishes, it'll ask you a few configuraton questions. Just hit
enter through all of these. We'll tweak our configuration later.

Setup Checks and Web Server Config
----------------------------------

Next, we need to make sure that our computer is ready to run Symfony. Our
project came with a little PHP script that checks our computer and tells
us if anything is missing or misconfigured. The script is called ``config.php``
and it lives in the ``web/`` directory. If you downloaded the project into
your Apache document root, then you can go to "localhost" and find your way
to the ``config.php`` script:

  http://localhost/starwarsevents/web/config.php

Alternatively, if you have PHP 5.4 or higher, you can use the built-in PHP
web server. Honestly, it's a lot easier to setup. From the root of our project,
run:

.. code-block:: text

    $ php app/console server:run

The web server uses ``web/`` as its document root, so we can just go to
``http://localhost:8000/config.php``. If you get an error, then you don't
have PHP 5.4 and you'll need to configure Apache:

.. code-block:: text

    There are no commands defined in the "server" namespace.

We'll talk more about proper web server setups later.

If you see any scary "Major Problems", you'll need to fix those. But feel
free to ignore any "Minor Problems" for now.

Permissions Issues
~~~~~~~~~~~~~~~~~~

Unless you're on Windows, you'll probably see two major issues - permissions
problems with the ``cache`` and ``logs`` directories. You're going to hit this
problem every time you start a new project. It's easy to fix, but if you don't
do it right, it can give you a major headache.

The easiest way to fix permissions is to put the ``umask`` function at the
top of 2 files. So, pop open your project in your favorite editor,
we like PhpStorm.

The ``umask`` line is already there, so just comment it out - first in the
``app/console`` and next in ``web/app_dev.php``::

Start with the ``app/console`` file, next the ``web/app.php`` file, and then
the ``app_dev.php`` file::

    #!/usr/bin/env php
    <?php

    umask(0000);
    // ...

Once you're done, set the permissions on the two cache and logs directories:

.. code-block:: bash

    chmod -R 777 app/cache/* app/logs/*

You shouldn't have any more issues, but if you do, just set the permissions again.

This method *can* be a security issue if you're deploying to a shared server.
Check out Symfony's `installation chapter`_ for details on other ways to setup
your permissions.

Now we're ready to start using Symfony. Check out our first real Symfony
page, by hitting the ``app_dev.php`` file in your browser:

  http://localhost:8000/app_dev.php

If everything worked, you'll see a pretty welcome page. The project we downloaded
came with a few demo pages. This is one of them, and you can look inside
the ``src/Acme/DemoBundle`` directory to see the code behind it.

.. tip::

    If you're using Apache with the same setup as we've done, then the URL
    will be:

    .. code-block:: text

        http://localhost/starwarsevents/web/app_dev.php

To see all the demo pages, click the "Run The Demo" green button.

Directory Structure
-------------------

At this point, we already have a functioning project with some demo pages.
Let's take a quick look at the directories and files we have so far.

The ``app/`` directory consists mostly of configuration, and basically ties
all the different parts of your app together. If your app were a computer,
this would be the motherboard: it doesn't really do anything, but it controls
everything. The actual features of your app live somewhere else, in directories
called "bundles". The bundles are activated in the ``AppKernel`` class. Each
bundle is then configured inside the ``config.yml`` file in the ``app/config/``
directory.

For example, if you want to change the session timeout length used by the
core FrameworkBundle, you can do that under the ``framework`` config key:

.. code-block:: yaml

    # app/config/config.yml
    # ...

    framework:
        # ...
        session:
            cookie_lifetime: 1440

Routes - which represent the URLs of your application - live in the ``routing.yml``
file in the same directory. We'll talk more about routes in a second.

And that's really it for configuration. You can ignore everything else in
the ``app/config/`` directory - they're less important and we'll talk more
about them when we cover environments.

The ``app/`` directory also contains the base layout file (``app/Resources/views/base.html.twig``)
and a console script (``app/console``) that we'll use in a few minutes.

You can pretty much ignore the ``bin/`` directory. It holds some executable
files that relate to different vendor libraries. Right now, it has some Doctrine
executables, which we won't actually need to use.

The ``src/`` directory is where your actual code goes and where you'll spend
most of your time developing. The directory is organized into sub-directories,
called "bundles", and each bundle contains all the code for a single feature.
We already have one bundle, which contains all the code for the demo pages.

The ``vendor/`` directory holds third party libraries - we populated it earlier
via Composer.

Finally, the ``web/`` directory is where all your public files live, like CSS,
JS and images files. It also contains the two PHP files that actually execute
Symfony. One loads Symfony in the ``dev`` environment (``app_dev.php``) and
the other in the ``prod`` environment (``app.php``).

Removing Demo Code
------------------

Before we start building, let's get rid of the demo code that came with the
project we downloaded. Start by deleting the demo bundle, which contains
most of the demo code.

.. code-block:: bash

    rm -rf src/Acme

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

Now, when we refresh, we'll see Symfony's error page, telling us that the
page can't be found. The demo page that was here before is gone, meaning
we've got a completely fresh project.

Setting up git
--------------

This is a perfect time to setup our project with git and make our first commit.
If you don't use git, the same basic principles could be used to store a project
in Subversion or any other version control system.

First, delete the ``.git`` directory. Like us, you may not have this directory.
Just make sure it's gone so that we don't inherit the history from the standard
distribution.

.. code-block:: bash

    rm -rf .git

Next, initialize a new git repository with the ``git init`` command. Before
we make our initial commit, there are few files that we should tell git to
ignore. Fortunately, Symfony gives us a really good ``.gitignore`` file to
start with.

The ``web/bundles`` directory holds public assets - like CSS files - that
are copied from bundles whenever you run the ``bin/vendor`` command. I'll
tell you more about that later, but for now we can ignore the directory since
it's filled automatically.

The ``bootstrap.php.cache`` file is also generated when you run the ``bin/vendor``
script. The file *is* needed, but since it's created for us, we don't need
to commit it.

The ``cache`` and ``logs`` directories are the same way - they're generated,
so we can ignore them.

The ``app/config/parameters.yml`` file holds all server-specific config, like
your database username and password. By ignoring it, each developer can keep
their own version of the file.

To make life easier, we *will* commit an example version of the file called
``parameters.yml.dist`` so that a new developer knows exactly what their
``parameters.yml`` needs to look like.

We also want to ignore the ``vendor/`` directory. We can do this because Composer
populates this directory for us. When a new developer pulls down our code,
she can run ``php composer.phar install`` to download everything needed into
this directory. This saves us from needing to commit a lot of third-party
code. If it's not in this file already, also ignore the ``bin/`` directory
as this is also populated automatically by Composer.

Now that we've ignored the right files, let's add everything to git and make
our first commit. If any friends or co-workers are nearby, now's a great time
to celebrate the first commit to your awesome new project with jumping high
fives, a pint, or a chubacca cry.

.. _Packagist.org: https://packagist.org/
.. _`installation chapter`: http://symfony.com/doc/current/book/installation.html#configuration-and-setup
.. _`The Wonderful World of Composer`: http://knpuniversity.com/screencast/composer
