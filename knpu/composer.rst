Adding Outside Bundles with Composer
====================================

We got rid of the ugly, but the site looks a little empty. We'll improve things
by loading fixtures, which are dummy data we put into the database.

When we started the project, we downloaded the Symfony Standard edition:
our pre-started project that came with Symfony and other tools like Doctrine.
Unfortunately, it didn't come with any tools for handling fixtures.

But we're smart enough: let's just add a fixtures library ourselves. And
by using Composer, doing this won't suck!

Head over to `KnpBundles.com`_ and search for "fixtures". Click on ``DoctrineFixturesBundle``,
yea, the one with the high quality score. Now click again to read `its documentation`_.

Installing a Bundle via Composer
--------------------------------

Composer is a PHP dependency management library. It downloads different libraries
into our project and makes sure that their versions are all compatible with
each other.

It works by reading the ``composer.json`` file inside your project. It downloads
all of the libraries under the ``require`` key, *and* any libraries that
*they* may depend on. To get the ``DoctrineFixturesBundle``, copy the line
from the documentation and paste it at the end of your ``require`` key:

.. code-block:: json

    {
        "require": {
            " ... ",
            "doctrine/doctrine-fixtures-bundle": "dev-master"
        }
    }

Each library has two parts: its name and the version you want. The name comes
from a site called `Packagist.org`_. You can find almost any PHP library
here and the versions available.

Finding the right Version
~~~~~~~~~~~~~~~~~~~~~~~~~

But using ``dev-master`` stinks. This tells Composer to grab the latest commit
to the master branch, whatever craziness that may be.

Go back to the `library's page on Packagist`_: anything without the ``dev``
at the end is a stable version. For me, the latest is ``2.2.0``. Let's use
that, but add a ``~`` to the front of it:

.. code-block:: json

    {
        "require": {
            " ... ",
            "doctrine/doctrine-fixtures-bundle": "~2.2.0"
        }
    }

With the tilde, this really means ``2.2.*``. Composer explains the different
version formats really well on their site (`Package Versions`_).

Installing with Composer
------------------------

Ok, let's download this library! We'll need the ``composer.phar`` file from
earlier - just move it into the project:

.. code-block:: bash

    $ cp ../composer.phar .

And remember, this is just a normal file, so you can download as many of
these as you want at `GetComposer.org`_.

Now, run ``php composer.phar update`` and pass it the name of the library:

.. code-block:: bash

    $ php composer.phar update doctrine/doctrine-fixtures-bundle

This may work for a little while as Composer things really hard about dependencies.
Eventually, it'll download ``DoctrineFixturesBundle`` *and* its dependent
``doctrine-data-fixtures`` library into the ``vendor/`` directory.

Composer update, install and composer.lock
------------------------------------------

While we wait, let's look at a small mystery. We know that Composer reads
information from ``composer.json``. So what's the purpose of the ``composer.lock``
file that's at the root of our project and how did it get there?

Composer actually has 2 different commands for downloading vendor stuff.

composer update
~~~~~~~~~~~~~~~

The first is update. It says "read the composer.json file and update everything
to the latest versions specified in there". So if today we have Symfony
2.4.1 but 2.5.0 gets released, a Composer update would upgrade us to the
new version. That's because our Symfony version constraint of ``~2.4`` allows
for anything greater than 2.4, but less than 3.0.

Hold up. That could be a big issue. What happens if you deploy right as Symfony
2.5.0 comes out? Will your production server get that version, even though
you were testing on 2.4.1? That would be *lame*.

Because Composer is *not* lame, each time the ``composer.phar update`` command
is run, it writes a ``composer.lock`` file. This records the exact versions
of all of your vendors at that moment.

composer install
~~~~~~~~~~~~~~~~

And that's where the second command - install - comes in. It *ignores* the
``composer.json`` file and reads entirely from the ``composer.lock`` file,
assuming one exists. So as long as you run ``install`` on your deploy, you'll
get the exact versions you expected.

So unless you're adding a new library or intentionally upgrading something,
always use ``composer.phar install``.

And when you *do* need to add or update something, you can be more precise
by calling ``composer.phar update`` and passing it the name of the library
you're updating like we did. With this, Composer will only update *that*
library, instead of everything.

.. _`KnpBundles.com`: http://knpbundles.com/
.. _`GetComposer.org`: http://getcomposer.org/
.. _`its documentation`: http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html
.. _`Package Versions`: https://getcomposer.org/doc/01-basic-usage.md#package-versions
.. _`Packagist.org`: https://packagist.org/
.. _`library's page on Packagist`: https://packagist.org/packages/doctrine/doctrine-fixtures-bundle
