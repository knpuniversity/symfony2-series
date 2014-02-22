Autoloading: Where did require/include go?
==========================================

Autoading: it's like plumbing. You forget it's there, but when it's gone,
well, let's just say you have to go outside a bit more often.

Autoloading is the magic that lets us use classes without needing to ``require``
or ``include`` the file that holds them first. We used to have ``include``
statements everywhere, and well, it was terrible.

But an autoloader has a tricky job: given any class name, it needs to know
the exact location of the file that holds that class. In many modern projects,
including ours, Composer handles this, and there are two pieces to understanding
how it figures out what file a class lives in.

Directory Structure and Namespaces
----------------------------------

When we create an Event object, Composer's autoloader knows that this class
lives inside ``src/Yoda/EventBundle/Entity/Event.php``. How? It just takes
the full class name, flips the slashes, and adds ``.php`` to the end of it:

.. code-block:: text

    Yoda\EventBundle\Entity\Event
    
    src/Yoda/EventBundle/Entity/Event.php

As long as the namespace matches the directory and the class name matches
the filename plus ``.php``, autoloading just works. Let's mess this up - let's
rename the ``Entity`` directory to ``Entity2``::

    use Yoda\EventBundle\Entity\Event;

    // the src/Yoda/EventBundle/Entity/Event.php file is "included"
    // .. so the file better exist and "house" the Event class!
    new Event();

If we run ``play.php`` now, it fails big:

    Class 'Yoda\EventBundle\Entity\Event' not found

The autoloader is looking for an ``Entity`` directory. Rename the directory
back to ``Entity`` to fix things.

Library Directory Paths
-----------------------

Right now, it almost looks like the autoloader assumes that everything must
live in the ``src/`` directory. So how are vendor classes - like Symfony - loaded?

That's the second part. When we fetch a library with Composer, it configures
its autoloader to look for the new classes in the directory it just downloaded.

Open up the ``vendor/composer/autoload_namespaces.php`` file. This is generated
by Composer and it has a map of namespaces to the directories where those
classes can be found::

    // vendor/composer/autoload_namespaces.php
    // ...
    
    return array(
        'Symfony\\' => array($vendorDir . '/symfony/symfony/src'),
        // ...
        'Doctrine\\ORM' => $vendorDir . '/doctrine/orm/lib/',
        'Doctrine\\DBAL' => $vendorDir . '/doctrine/dbal/lib/',
        'Doctrine\\Common\\DataFixtures' => $vendorDir . '/doctrine/data-fixtures/lib/',
        // ...
    );

So when we reference a ``Symfony`` class, it does the slash-flipping trick,
and then looks for the file starting in ``vendor/symfony/symfony/src``:

    Symfony\Component\HttpFoundation\Response

    vendor/symfony/symfony/src/Symfony/Component/HttpFoundation/Response.php

Now you know *all* the secrets about the autoloader. And when you see a class
not found error, it's *your* fault. Sorry! The most common mistake is easily
a missing ``use`` statement. If it's not that, check for a typo in your class
and filename.
