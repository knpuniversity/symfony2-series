Sharing Data between Fixture Classes
====================================

Let's update the fixtures so that each event has an owner.

We have two fixture classes: one that loads events and one that loads users.

Ordering how Fixtures are Loaded
--------------------------------

Start in the ``LoadUsers`` class. Now that events depend on users, we'll want
this fixture class to be executed *before* the events class. To force this,
implement a new interface called ``OrderedFixtureInterface``. This requires
one method called ``getOrder``. Let's return 10::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

    class LoadUsers implements FixtureInterface, ContainerAwareInterface, OrderedFixtureInterface
    {
        // ...

        public function getOrder()
        {
            return 10;
        }
    }

Head over to ``LoadEvents`` and make the same change, except returning 20
so that the class is run second::

    // src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php
    // ...

    use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

    class LoadEvents implements FixtureInterface, OrderedFixtureInterface
    {
        // ...

        public function getOrder()
        {
            return 20;
        }
    }

Assigning Owners in Fixtures
----------------------------

Now, we just need to get our new User objects inside ``LoadEvents``. DoctrineFixturesBundle
has a standard way of sharing data between fixtures, but a much easier way
is just to query for our wayne user::

    // src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php
    // ...

    class LoadEvents implements FixtureInterface, OrderedFixtureInterface
    {
        $wayne = $manager->getRepository('UserBundle:User')
            ->findOneByUsernameOrEmail('wayne');
    
        // ...
    }

All we need to do now is call ``setOwner`` on both events so that it looks
like wayne created them::

    // src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php
    // ...
    public function load(ObjectManager $manager)
    {
        $wayne = $manager->getRepository('UserBundle:User')
            ->findOneByUsernameOrEmail('wayne');
        // ...
        
        $event1->setOwner($wayne);
        $event2->setOwner($wayne);
        
        // ...
        $manager->flush();
    }

Ok! Reload the fixtures!

.. code-block:: bash

    php app/console doctrine:fixtures:load

Now use ``app/console`` to check that each event has an owner:

.. code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM yoda_event"

