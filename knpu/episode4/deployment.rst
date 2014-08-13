Deployment: The Art of Uploading your Code
==========================================

This wouldn't be much of a tutorial if we didn't at least help show you how
to share your project with the world! There are a lot of neat deployment
tools out there and I'm sorry, we're not going to show you any of them. At
least not in this screencast. Instead, we'll go through the exact steps you'll
need for deployment. If you want to automate them, awesome!

To keep things simple, I'm going to "deploy" to a different directory right
on my local machine. So, just pretend this is our server and I've already
ssh'ed into it.

We already have MySQL, PHP and Apache up and running.

Step 1) Upload the Files
------------------------

First, we've gotta get the files up to the server! The easiest way is just
to clone your git repository right on the server. To do this, you'll need
to push your code somewhere accessible, like GitHub. The finished code for
this tutorial already lives on `GitHub`_, under a branch called ``episode4-finish``.

Let's clone this repository:

.. code-block:: bash

    git clone

Move into the directory. If your code lives anywhere other than the master
branch, you'll need to switch to that branch:

.. code-block:: bash

    git checkout -b episode4-finish origin/episode4-finish

GitHub *might* ask you to authenticate yourself or give you some public key
error. If that happens, you'll need to register the public key of your server
as a `deploy key`_ for your repository. This is what gives your server permission
to access the code.

GitHub has great articles on deploy keys and generating a public key.

Step 2) Configuring the Web Server
----------------------------------

Code, check! Next, let's configure the web server. I'm using Apache, but
Symfony has a `cookbook article about using Nginx`_. Find your Apache configuration
and add a new VirtualHost that points to the ``web/`` directory of our project.
In our case, ``/var/www/knpevents.com/web``:

.. code-block:: apache

    <VirtualHost *:80>
        ServerName knpevents.com
        DocumentRoot /var/www/knpevents.com/web

        <Directory /var/www/knpevents.com/web>
            Options Indexes FollowSymlinks
            AllowOverride All
            
            # Use these 2 lines for Apache 2.3 and below
            Order allow,deny
            allow from all

            # Use this line for Apache 2.4 and above
            Require all granted
        </Directory>
        
        ErrorLog /var/log/apache2/events_error.log
        CustomLog /var/log/apache2/events_access.log combined
    </VirtualHost>

The VirtualHost is pretty simple and needs ``ServerName``, ``DocumentRoot``
and ``Directory`` keys.

Restart your webserver. For many servers, this is done by calling service
restart apache:

.. code-block:: bash

    sudo service restart apache2

Project: First-Time Setup
-------------------------

Code, check! VirtualHost, check!

Since this is the first time we've deployed, we need to do some one-time setup.

First, `download Composer`_ and use it to install our vendor files:

.. code-block:: bash

    curl -sS https://getcomposer.org/installer | php
    php composer.phar install

At the end, it'll ask you for values to fill into your ``parameters.yml``
file. You'll need to have a database user and password ready.

Speaking of, let's create the database and insert the schema. I'll even run
the fixtures to give our site some starting data:

.. code-block:: bash

    php app/console doctrine:database:create
    php app/console doctrine:schema:create
    php app/console doctrine:fixtures:load

In this pretend scenario, I've already pointed the DNS for knpevents.com
to my server. So let's try it:

    http://knpevents.com

It's alive! And with a big error, which might just show up as the white
screen of death on your server. Symfony can't write to the cache directory.
We need to do a one-time ``chmod`` on it and the ``logs`` dir:

.. code-block:: bash

    sudo chmod -R 777 app/cache/ app/logs/

Let's try again. Ok, we have a site, and we can even login as Wayne.
But it's missing all the styles. Ah, right, dump the assetic assets:

.. code-block:: bash

    php app/console assetic:dump --env=prod

Crap! Scroll up. This failed when trying to run uglifycss. I don't
have Uglifycss installed on this machine yet. To get ugly Just run 
``npm install`` to fix this.

.. code-block:: bash

    php app/console assetic:dump --env=prod

Now, the dump works, AND the site looks great!

Things to do on each Deploy
---------------------------

On your next deploy, things will be even easier. Here's a simple guide:

1. Update your Code. With our method, that's as simple as running a git pull:

.. code-block:: bash

    git pull origin

2. Just in case we added any new libraries to Composer, run the install command:

.. code-block:: bash

    php composer.phar install

3. Update your database schema. The easy, but maybe dangerous way is with
   the schema update console command:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Why dangerous? Let's say you rename a property from ``name`` to ``firstName``.
Instead of renaming the column, this task may just ``drop`` name and add
``firstName``. That would mean that you'd lose all that data!

There's a library called `Doctrine Migrations`_ that helps do this safely.

4. Clear your production cache:

.. code-block:: bash

    php app/console cache:clear --env=prod

5. Dump your Assetic assets:

.. code-block:: bash

    php app/console assetic:dump --env=prod

That's it! As your site grows, you may have more and more things you need
to setup. But for now, it's simple.

Performance Setup you Need
--------------------------

One more thing. There are a few really easy wins to maximize
Symfony's performance.

First, when you deploy, dump Composer's optimized autoloader:

.. code-block:: bash

    php composer.phar dump-autoload --optimize

This helps Composer's autoloader find classes faster, sometimes much faster.
And hey, there's no downside at all to this!

.. tip::

    If you add the `--optimize-autoloader` flag, Composer will generate a
    class map, which will give your whole application a performance boost.
    Using the `APC ClassLoader`_ may give you an even bigger boost.

Next, make sure you have a byte code cache installed on your server. For PHP 5.4
and earlier, this was called APC. For 5.5 and later, it's called OPcache.
In the background, these cache the compiled PHP files, making your site
*much* faster. Again, there's no downside here - make sure you have one of
these on your server.

And on that note, PHP typically gets faster from version to version. So staying
on the latest version is good for more than just security and features. Thanks
PHPeeps!

Ok, that's it! Now google around for some deployment tools to automate this!

.. _`deploy key`: https://help.github.com/articles/managing-deploy-keys
.. _`download composer`: http://getcomposer.org/download/
.. _`APC ClassLoader`: http://symfony.com/doc/current/book/performance.html#caching-the-autoloader-with-apc
.. _`cookbook article about using Nginx`: http://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html#nginx
.. _`Doctrine Migrations`: http://symfony.com/doc/current/bundles/DoctrineMigrationsBundle/index.html
.. _`GitHub`: https://github.com/knpuniversity/symfony2-series/tree/episode4-finish
