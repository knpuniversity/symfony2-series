Fixtures: For some dumb data
============================

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

    $ php app/console doctrine:fixtures:load --help

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

    class LoadEvents implements FixtureInterface
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

    $ php app/console doctrine:fixtures:load

When we look at the site, we've got some fresh data to play with. Re-run the
command whenever you want: it deletes the existing data and inserts the fixtures
in a fresh state. If you want to add to the existing data, just pass the
``--append`` option.

.. _`the example from the docs`: http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html#writing-simple-fixtures
