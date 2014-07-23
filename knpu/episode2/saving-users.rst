Saving Users
============

Now that the error is gone, try logging in! Wait, but our user table is empty.
So we can see the bad password message, but we can't *actually* log in yet.

But we're pros, so it's no problem. Let's copy the ``LoadEvents`` fixtures
class (``LoadEvents.php``) into the ``UserBundle``, rename it to ``LoadUsers``, 
and update the namespaces::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    namespace Yoda\UserBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Yoda\UserBundle\Entity\User;

    class LoadUsers implements FixtureInterface
    {
        public function load(ObjectManager $manager)
        {
            // todo
        }
    }

Saving users is *almost* easy: just create the object, give
it a username and then persist and flush it.

The tricky part is that darn ``password`` field, which needs
to be encoded with ``bcrypt``::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...
    use Yoda\UserBundle\Entity\User;
    // ...

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('darth');
        // todo - fill in this encoded password... ya know... somehow...
        $user->setPassword('');
        $manager->persist($user);

        // the queries aren't done until now
        $manager->flush();
    }

ContainerAwareInterface for Fixtures
------------------------------------

But what's *cool* is that Symfony gives us an object that can do all that
encoding for us. To get it, first make the fixture implement the
:symfonyclass:`Symfony\\Component\\DependencyInjection\\ContainerAwareInterface`::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...
    
    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    
    class LoadUsers implements FixtureInterface, ContainerAwareInterface
    {
        // ...
    }

This requires one new method - ``setContainer``. In it, we'll store the
``$container`` variable onto a new ``$container`` property::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    use Symfony\Component\DependencyInjection\ContainerInterface;

    class LoadUsers implements FixtureInterface, ContainerAwareInterface
    {
        private $container;

        // ...

        public function setContainer(ContainerInterface $container = null)
        {
            $this->container = $container;
        }
    }

Because we implement this interface, Symfony calls this method and passes
us the container object before calling ``load``. Remember that the container
is the `array-like object that holds all the useful objects in the system`_.
We can see a list of those object by running the ``container:debug`` console
task:

.. code-block:: bash

    php app/console container:debug

Encoding the Password
---------------------

Let's create a helper function called ``encodePassword`` to you know encode the password! 
This step may look strange, but stay with me. First, we ask Symfony for a 
special "encoder" object that knows how to encrypt our passwords. Remember 
the ``bcrypt`` config we put in ``security.yml``? Yep, this object will use that.

After we grab the encoder, we just call ``encodePassword()``, grab a sandwich and let it do
all the work::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    private function encodePassword(User $user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user)
        ;

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }

Behind the scenes, it takes the plain-text password, generates a random salt,
then encrypts the whole thing using bcrypt. Ok, so let's set this onto the
``password`` property::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('darth');
        $user->setPassword($this->encodePassword($user, 'darthpass'));
        $manager->persist($user);

        // the queries aren't done until now
        $manager->flush();
    }

Try it! Reload the fixtures from the command line:

.. code-block:: bash

    php app/console doctrine:fixtures:load

Let's use the query console task to look at what the user looks like:

.. code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM yoda_user"

.. code-block:: text

array (size=1)
  0 => 
    array (size=3)
      'id' => string '1' (length=1)
      'username' => string 'user' (length=4)
      'password' => string '$2y$13$BoVE3I5dmVkBjRp.l6uwyOI8Z8Ngokiaa.OUUuHoDbGDBdMRMUrmC' (length=60)

Nice! We can see the encoded password, which for ``bcrypt``, also includes
the randomly-generated ``salt``. You *do* need to store the ``salt`` for each
user, but with ``bcrypt``, it happens automatically. Symfony requires us
to have a ``getSalt`` function on our ``User``, but it's totally not needed
with ``bcrypt``.

Back at the browser, we can login! Behind the scenes, here's basically what's
happening:

1. A User entity is loaded from the database for the given username;

2. The plain-text password we entered is encoded with bcrypt;

3. The encoded version of the submitted password is compared with the saved
   password field. If they match, then you now have access to roam about this
   fully armed and operational battle station!

.. _`array-like object that holds all the useful objects in the system`: http://knpuniversity.com/screencast/symfony2-ep1/controller#symfony-ep1-what-is-a-service
