Fixtures: For some dumb data
============================

We have the bundle! Plug it in! Open up the ``AppKernel`` class and add it
there::

    // app/AppKernel.php
    // ...
    
    $bundles = array(
        // ...
        new Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle(),
    );

To see if it's working, try getting help information on a new ``doctrine:fixtures:load``
console task that comes from the bundle:

.. code-block:: bash

    $ php app/console doctrine:fixtures:load --help

We see the help information, so we're ready to write some fixtures.

Writing Fixtures
----------------

A fixture is just a PHP class that puts some stuff into the database.

Create a new file in the ``DataFixtures\ORM`` directory of your bundle. Let's
call it ``LoadEvents.php``, though the name doesn't matter.

    Create a src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php file.

To breathe life into this, copy and paste `the example from the docs`_. Change
the namespace above the class to match our project. Notice that the namespace
always follows the directory structure of the file::

    // src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php
    namespace Yoda\EventBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;

    class LoadEvents implements FixtureInterface
    {
        public function load(ObjectManager $manager)
        {
            // .. todo
        }
    }

Now we just use normal Doctrine code to create and save events. This is the
``play.php`` file all over again::

    use Yoda\EventBundle\Entity\Event;
    // ...

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

Notice that we only need to call ``flush`` once. Doctrine prepares all of
its work and then sends the queries as efficiently as possible all at once.

Loading the Fixtures
~~~~~~~~~~~~~~~~~~~~

Ok, let's load some fixtures. Go back to the console and try the new ``doctrine:fixtures:load``
command:

.. code-block:: bash

    $ php app/console doctrine:fixtures:load

When we look at the site, we've got fresh dummy data to play with. Re-run
the command whenever you want to start over: it deletes everything and
inserts the fixtures in a fresh state.

.. tip::

    If you'd rather add to the existing data, just pass the ``--append`` option.

.. _`the example from the docs`: http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html#writing-simple-fixtures
