Deployment
==========

Welcome to deployment! We now know a lot of great things about Symfony, but
it's all for nothing if we don't know how to deploy our code! Deployment can
be a complex topic and will vary depending on your requirements, whether or
not you run on multiple servers, and just plain personal taste. But any way
you go, you'll be accomplishing the same exact tasks. We'll do everything by
hand at first and then learn about some optional tools you can use later to
automate things.

.. note::

    There are a *lot* of options for deploying. In this entry, you'll learn
    the necessary steps behind deploying. But the best option for your project
    may be something more advanced than we see here.

The first step of deployment is simple: get your files up to the server. There
are a few really straightforward ways to do this. One is Rsync. By writing a
simple script, you can just rsync your entire project directory up onto another
server. Another good option is just to use your git repository. We'll try this
method. First, make sure you've committed all of your changes and pushed them
to some remote:

.. code-block:: bash

    git push origin master

My "master" branch usually holds my stable code, so I'll push and deploy
from there.

Next, ssh onto your server:

.. code-block:: bash

    ssh -p 12345 youruser@example.org

In order to pull from our GitHub repository, you'll need to get the public
key of your server and register it as a `deploy key`_ in your repo. Alternatively,
you can add your server's public key to your account on GitHub, or use something
called ssh-agent forwarding. GitHub has great articles on deploy keys and
generating a public key.

I've already setup my server's public key, so now I can pull down the code
by cloning it. First, find out where you server's webroot is or create a new
root if you need one. In this case, I've already created a new VirtualHost
that points to ``/var/www/knpevents.com/public_html``:

.. code-block:: apache

    <VirtualHost *:80>
        ServerName knpevents.com
        DocumentRoot /var/www/knpevents.com/public_html

        <Directory /var/www/knpevents.com/public_html>
            Options Indexes FollowSymlinks
            AllowOverride All
            Order allow,deny
            allow from all
        </Directory>
    </VirtualHost>

The VirtualHost is pretty simple and will have at least ``ServerName``, ``DocumentRoot``
and ``Directory`` keys. The ``AllowOverride All`` is important, and allows the
``.htaccess`` file in your project to rewrite URLs.

Once your VirtualHost is setup, go somewhere near - but not in - your webroot
and clone your code:

.. code-block:: bash

    $ cd /path/to/some/place/near/your/webroot
    $ git clone git@github.com:CoolOrg/CoolProject.git deploy

First-Time Setup
----------------

Now that we have our code on the server, we need to do some one-time setup.
First, copy the ``app/config/parameters.yml.dist`` file to ``parameters.yml``
and customize it:

.. code-block:: bash

    $ cd deploy
    $ cp app/config/parameters.yml.dist app/config/parameters.yml

You'll need to create a database, a user and a password to put in here, which
I have already prepared. Next, create empty ``app/logs`` and ``app/cache``
directories:

.. code-block:: bash

    $ mkdir app/cache app/logs
    $ chmod 777 app/cache app/logs

Just like with your local code, these both need to be writable by your web-server.
There are a number of ways to handle this, and you can see them in Symfony's
installation chapter. I'll ``chmod`` 777 them, because I'm on my own secured
server.

.. note::

    Actually, since in the ``prod`` environment Symfony doesn't try to delete
    and recreate existing cache files, your web server should not need write
    access to these directories in reality.

At this point, all of our code is on the server, the configuration is in place,
and the directories that need to be writable are. But since the project isn't
under my web server's document root, it's not yet accessible to the web. There
are a few ways to fix this, but the one that works everywhere is symlinks.
In my case, the web-root is already setup as ``/var/www/knpevents.com/public_html``.
Delete this directory and replace it with a symbolic link to the ``web/``
directory in your project:

.. code-block:: bash

    $ cd ..
    $ rm -rf public_html
    $ ln -s /path/to/some/place/near/your/webroot/deploy/web public_html

And just like that, you project is open to the world, just not *quite* working
yet! This trick works just as well on shared hosting: replace the normal
"public" directory with a symbolic link to wherever you've put your project.

Things to do on each Deploy
---------------------------

Finally, there are just 3 commands you should run each time you deploy.
First, because the "vendor" directory is ignored by git, we need to install
our vendor libraries:

    php composer.phar install --optimize-autoloader

.. tip::

    You'll probably also need to `download composer`_ again, since it's ignored
    in our repository.

Just like on your local computer, this reads from your ``composer.lock`` file
and makes sure that each vendor library is downloaded to the exact right version.

.. tip::

    If you add the `--optimize-autoloader` flag, Composer will generate a
    class map, which will give your whole application a performance boost.
    Using the `APC ClassLoader`_ may give you an even bigger boost.

Let's fast-forward through this thrilling process.

The second thing we need to do is clear our cache using the ``cache:clear``
command:

    php app/console cache:clear --env=prod

If we pushed any changes, this makes sure to rebuild the cache so that the
new changes work correctly. Remember from earlier that because we're using
Assetic, our CSS and JavaScript files don't exist until we ask Assetic to
physically dump them. This means that you *must* redump your assets on every
deploy or you won't see their latest changes. This is the third and final
command we need to run.

   php app/console assetic:dump --env=prod

Finally, we need an actual database! I'll create one from scratch and load
in our test fixtures:

.. code-block:: bash

    # DON'T NORMALLY RUN THIS ON DEPLOY!

    $ php app/console doctrine:database:create
    $ php app/console doctrine:schema:create
    $ php app/console doctrine:fixtures:load

Obviously, you won't re-create the database or reload your fixtures normally,
this is just a one-time thing.

Let's try it out! In the browser, the site loads from our newly pulled codebase
with our fresh configuration. The cache directory is written just fine and
the application uses all the vendor libraries we downloaded. Not bad!

Future Deploys
--------------

Now that we've deployed for the first time, let's talk about updates! Let's
make a small, but noticeable change to the codebase, commit it, and then push
that change. Since the change is to a Twig template, we'll definitely need
to clear our cache to see it.

To handle this update, let's make a checklist!

Step 1: Get the new code. We'll do this by calling ``git fetch`` and then merging
down the latest changes.

.. code-block:: bash

    $ git fetch origin
    $ git merge origin/master

Step 2: Run the ``composer.phar install`` script. You only technically need to
call this if you added a vendor or updated its version. But, as long as you
have all of your vendors properly locked, it's best to run this on every
deploy to be sure.

.. code-block:: bash

    php composer.phar install --optimize-autoloader

Step 3: Clear your cache by using the cache clear command.

.. code-block:: bash

    php app/console cache:clear --env-prod

Step 4: Dump your Assetic assets

.. code-block:: bash

    php app/console assetic:dump --env-prod

When we check out the site, the change is there. Deploying your code is as
simple as those 4 steps!

.. tip::

    Be aware that the ``composer.phar install`` command runs the ``assets:install web``
    command for you. This command should be run on every deploy, but it's
    done automatically for you.

Deploying with Rsync
--------------------

And you can really deploy your code in any way that satisfies these three
requirements. In fact, let's try a totally different approach: a little shell
script that uses rsync:

.. code-block:: text

    #!/bin/sh
    # deploy.sh

    DEPLOY_USER=youruser
    DEPLOY_HOST=example.org
    DEPLOY_PORT=1234
    DEPLOY_DIR=/path/to/some/place/near/your/webroot/deploy/

    rsync --archive --force --delete --progress --compress --checksum --exclude-from=app/config/rsync_exclude.txt -e "ssh -p $DEPLOY_PORT" ./ $DEPLOY_USER@$DEPLOY_HOST:$DEPLOY_DIR
    ssh -p $DEPLOY_PORT $DEPLOY_USER@$DEPLOY_HOST "cd $DEPLOY_DIR && \
    export SYMFONY_ENV=prod && \
    rm -rf app/cache/$SYMFONY_ENV/* && \
    php app/console --env=prod --symlink assets:install web" && \
    /usr/bin/php app/console cache:clear --env=prod
    /usr/bin/php app/console assetic:dump --env=prod

The beauty of this approach is that you can deploy with just one command
and the script is simple. Rsync is used to transfer the code, and then the
script ssh's onto the server to run a few commands. In this case, we're actually
Rsync'ing our entire vendor directory, so running the ``composer.phar install``
isn't needed.

.. note::

    You could choose to ignore the vendor directory via an ``rsync_exclude.txt``
    file and then run ``composer.phar install`` on the server instead.

But we do need to call ``assets:install``, which is normally run for us when
we execute the ``composer.phar install`` command.

Since we're not cloning from Git, we will need to create an ``rsync_exclude.txt``
file, which tells Rsync which files to ignore:

.. code-block:: text

    # app/config/rsync_exclude.txt
    app/config/parameters.yml
    app/cache
    app/logs
    .git
    web/uploads
    web/bundles

This will look somewhat like your ``.gitignore`` file and will include our parameters
file, cache, logs, uploaded files, and the ``.git`` directory.

This all looks great, so let's make a small change and try it:

.. code-block:: bash

    ./deploy.sh

In this case, we don't need to commit the change since Rsync is just transferring
our local code directly. Be aware of what uncommitted changes you have locally
so that you don't deploy anything you didn't mean to!

More Advanced Options
---------------------

In my development, I deploy either with the shell script we just saw or via
a library called Capifony. Capifony is a Symfony-specific extension of a
library written in Ruby, which is awesome at deploying! Yes, I did say Ruby,
and yes, Capifony can be a pain to setup. Check out its documentation at
capifony.org and if you're curious, try it out. I usually use deployment
method "a", where you tell Capifony to pull from your repository.

.. tip::

    Other common approaches include build systems like Ant, which can be
    quite advanced. For example, your system might clone a branch of your
    repository, prepare the code, run the needed commands, zip up the final
    package, upload it to your production server, unzip it, and run any other
    necessary steps. 

But if you don't have extra time to wrestle with a new library right now,
don't worry! Just find a simple deployment method that works for you.

.. _`deploy key`: https://help.github.com/articles/managing-deploy-keys
.. _`download composer`: http://getcomposer.org/download/
.. _`APC ClassLoader`: http://symfony.com/doc/current/book/performance.html#caching-the-autoloader-with-apc