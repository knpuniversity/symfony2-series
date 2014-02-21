Adding Outside Bundles with Composer
====================================

With a working event area, our project is coming along nicely! But the site
looks a little empty and lonely. Let's fix that by adding some interesting
events.

Instead of adding them by hand, we'll create "fixture data", which is
test data that we can load into our database over and over. This means we'll
always have a set of data to work with while developing.

Back when we started the project, we downloaded the Symfony Standard edition,
which is a pre-started project that comes with Symfony and some other tools, like
Doctrine. One thing it didn't come with is a library to handle data fixtures.
But no problem! We can add the library ourselves. Whenever you bring
in a new library, you'll always start with the same two steps.

First, we need to download the library. To install the fixtures bundle, I'll
use `KnpBundles.com`_ to find it and then click to read its documentation.

Installing a Bundle via Composer
--------------------------------

Remember the Composer library from earlier? Composer is a PHP dependency management
library, which helps to download different PHP libraries into your project
and to make sure that their versions are all compatible with each other.

Composer works by reading the ``composer.json`` file from inside your project.
It downloads all of the libraries under the ``require`` key, as well as any
libraries that they may depend on. To get the ``DoctrineFixturesBundle``, copy
the line from the documentation to the end of your ``require`` key:

.. code-block:: json

    {
        "require": {
            " ... ",
            "doctrine/doctrine-fixtures-bundle": "~2.2.0"
        }
    }

Each entry has two parts: the name of the library you want and its version.
The name comes from a central repository called Packagist. You can use it
to search for any libraries and find out what versions are available.

For the version, we're using ``dev-master``, which means the "latest and greatest". 
This isn't typically a good idea, since you might receive non-stable features.
But sometimes, like in this case, the last tagged version of the library we
need isn't compatible with our project. If you're ever in doubt, you can
try to install an older version of a library. Composer will throw an error
if it's not compatible.

To use composer, copy in the ``composer.phar`` file we downloaded earlier into
our project:

.. code-block:: bash

    $ cp ../composer.phar .

Remember that you can download ``composer.phar`` at any time by going to
`GetComposer.org`_ and following the directions.

With the new line in our ``composer.json`` file, run ``php composer.phar update``
and pass it the name of the library:

.. code-block:: bash

    $ php composer.phar update doctrine/doctrine-fixtures-bundle

This will download the ``DoctrineFixturesBundle`` and its dependent ``doctrine-data-fixtures``
library into your project.

Composer update, install and composer.lock
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

But let's back up and learn just a little bit more about Composer. As we
already know, Composer reads information from your ``composer.json`` file.
But if you look at the root of your project, there's also a ``composer.lock``
file. What's that?

In fact, Composer has 2 different commands for updating vendor libraries,
and each should be used in different situations. First, ``composer.phar update``
says "read the composer.json file and update everything to the latest version
specified". For example, suppose today we're using Symfony 2.1.0, but our
``composer.json`` file specifies simply 2.1.* - meaning, the last 2.1.x version.
If Symfony 2.1.1 were released and we ran ``composer.phar update``, it would
upgrade us to Symfony 2.1.1.

But this could be a huge headache! Imagine you have 5 developers on a project.
When someone clones the project or updates vendors for a new library you added, 
they might get surprised with a new version of some other library!

To handle this problem, each time you run ``composer.phar update``, it writes
a ``composer.lock`` file, which records the exact versions of all of your
vendors. Now, if any developer runs ``composer.phar install``, the ``composer.json``
file is ignored, and vendors are downloaded based on the exact directions
in the lock file.

What this ultimately means is that you should use a simple workflow. Unless
you're adding a new library or intentionally upgrading something, always use
``composer.phar install``. When you do need to add a new library or upgrade
something,  you can be even more precise by calling ``composer.phar update``
and passing it the name of the library you're updating. By doing this, Composer
will only update *that* library, instead of all of them.

Great - step 1 was to download the library by adding it to composer and updating.

.. _`KnpBundles.com`: http://knpbundles.com/
.. _`GetComposer.org`: http://getcomposer.org/
