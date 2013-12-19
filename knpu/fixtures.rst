Fixtures and External Libraries
===============================

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
            "doctrine/doctrine-fixtures-bundle": "dev-master"
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

    cp ../composer.phar .

Remember that you can download ``composer.phar`` at any time by going to
`GetComposer.org`_ and following the directions.

With the new line in our ``composer.json`` file, run ``php composer.phar update``
and pass it the name of the library:

.. code-block:: bash

    php composer.phar update doctrine/doctrine-fixtures-bundle

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

Activating and Using the Bundle
-------------------------------

Step 2 is to activate the new ``DoctrineFixturesBundle`` in your AppKernel
class::

    // app/AppKernel.php
    // ...
    
    $bundles = array(
        // ...
        new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
    );

To see if the fixtures bundle is working, try getting help information on
a new ``doctrine:fixtures:load`` task that the bundle provides:

.. code-block:: bash

    php app/console doctrine:fixtures:load --help


If you see the help information, you're ready to go! If you run the command now,
it'll complain - because we haven't written any fixtures yet!

Writing Fixtures
----------------

A fixture is just a PHP class that puts data into the database.

To create a fixture, create a new file in the ``DataFixtures\ORM``
directory of any of your bundles. We'll call our class ``LoadEvents.php``

    Create a src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php file.

The easiest way to give life to the file is to copy and paste
`the example from the docs`_. Change the namespace on the class to match
our project. Notice that the namespace always follows the directory structure
of the file. Also, import the ``Event`` class namespace::

    // src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php
    namespace Yoda\EventBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Yoda\EventBundle\Entity\Event;

    class LoadUserData implements FixtureInterface
    {
        public function load(ObjectManager $manager)
        {
            // .. todo
        }
    }

To create the events, we just use normal Doctrine code, which I'll paste in::

    public function load(ObjectManager $manager)
    {
        $event1 = new Event();
        $event1->setName('Darth\'s Birthday Party!');
        $event1->setLocation('Deathstar');
        $event1->setTime(new \DateTime('tomorrow noon'));
        $event1->setDetails('Ha! Darth HATES surprises!!!');
        $manager->persist($event1);

        $event2 = new Event();
        $event2->setName('Rebellion Fundraiser Bake Sale!');
        $event2->setLocation('Endor');
        $event2->setTime(new \DateTime('Thursday noon'));
        $event2->setDetails('Ewok pies! Support the rebellion!');
        $manager->persist($event2);

        // the queries aren't done until now
        $manager->flush();
    }

Notice that I'm only calling ``flush`` once on the entity manager. Doctrine
prepares all of its work, then sends the queries all at once. This is cool
because it's super fast.

Loading the Fixtures
~~~~~~~~~~~~~~~~~~~~

To load in the fixtures, run the ``doctrine:fixtures:load`` command. Since we
put the class in ``DataFixtures\ORM``, it finds our fixture and runs it:

.. code-block:: bash

    php app/console doctrine:fixtures:load

When we look at the site, we've got some fresh data to play with. Re-run the
command whenever you want: it deletes the existing data and inserts the fixtures
in a fresh state. If you want to add to the existing data, just pass the
`--append`` option.

Autoloading
-----------

Before we move on, I want to say a quick word on autoloading. Composer packages
a special "class loader", which makes it possible to use classes without worrying
about calling ``include`` on the file that holds them.

There are two major parts to understanding how Composer's autoloader works.

First, in order for it to work, the namespace of each of your classes must follow
the directory structure of the file it lives in. For example, in our play script,
if I create a new Event object, Composer silently autoloads the class for us. But
if I change the directory name to `Entity2`, Composer can't find the file anymore.

.. code-block:: text

    use Yoda\EventBundle\Entity\Event;
    
    // the src/Yoda/EventBundle/Entity/Event.php file is "included"
    // .. so the file better exist and "house" the Event class!
    new Event();

The moral of part one is this: if each class name matches its directory
structure, you'll be fine. And if you see a "Class not found" exception,
check the spelling on your class name and file path.

The second thing to understand is that when you bring in a new library via
Composer, it automatically configures its autoloader to look in the new directory.
Open up the ``vendor/composer/autoload_namespaces.php`` file, which
is generated by Composer. This contains a map of namespaces to the directories
where that namespace can be found::

    // vendor/composer/autoload_namespaces.php
    // ...
    
    return array(
        // ...
        'Doctrine\\ORM' => $vendorDir . '/doctrine/orm/lib/',
        'Doctrine\\DBAL' => $vendorDir . '/doctrine/dbal/lib/',
        'Doctrine\\Common\\DataFixtures' => $vendorDir . '/doctrine/data-fixtures/lib/',
        // ...
    );

Notice that our fixtures namespaces are in here - this file is updated every
time you update with Composer.

Composer's actual autoloader is located at ``vendor/autoload.php``, and we're
including it in our project in the ``app/autoload.php`` file. You probably
won't need to worry too much about these files, but it's important to understand
that autoloading is happening, and it's being handled by Composer.

.. _`KnpBundles.com`: http://knpbundles.com/
.. _`GetComposer.org`: http://getcomposer.org/
.. _`the example from the docs`: http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html#writing-simple-fixtures