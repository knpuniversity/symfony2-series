Doctrine Event Listeners
========================

In episode 2, we created a registration form and manually encoded the user's
plain-text password before persisting it. We even duplicated this logic
in our fixtures. Shame!

Our goal is to encode the user's password automatically using a Doctrine
event listener. These are exactly like a lifecycle callback except that the
function that's executed lives *outside* of your entity and inside some other
class. Do you think this "other class" will be a service? Of course it will :).

Creating the Event Listener
---------------------------

Since I *love* classes so much, create one called ``UserListener`` in a new
``Doctrine`` directory of UserBundle::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    namespace Yoda\UserBundle\Doctrine;

    class UserListener
    {

    }

We're going to register this as a service, so the name and location don't
matter at all.

Add a ``prePersist`` method. To prove that this is called, just add a ``die``
statement::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    // ...

    class UserListener
    {
        public function prePersist()
        {
            die('Something is being inserted!');
        }
    }

Registering the Listener as a Service
-------------------------------------

Next, let's register this as a service. Hmm, we don't already have a ``services.yml``
file in UserBundle. Technically, we could just register this in ``services.yml``
in EventBundle. But to keep things organized, create a new file in UserBundle
and configure the service there.

.. code-block:: yaml

    # src/Yoda/UserBundle/Resources/config/services.yml
    services:
        doctrine.user_listener:
            class: Yoda\UserBundle\Doctrine\UserListener

If you think Symfony is going to automatically find this file, you're
nuts! Import it manually from your main ``config.yml`` file:

.. code-block:: yaml

    imports:
        # ...
        - { resource: "@UserBundle/Resources/config/services.yml" }

Just like with the Twig Extension, our listener *is* a service, but Doctrine
doesn't automagically know about it. Let's use another tag, this time called
``doctrine.event_listener``:

.. code-block:: yaml

    # src/Yoda/UserBundle/Resources/config/services.yml
    services:
        doctrine.user_listener:
            class: Yoda\UserBundle\Doctrine\UserListener
            arguments: []
            tags:
                - { name: doctrine.event_listener, event: prePersist }

The ``name`` says we're a listener and ``event`` tells Doctrine *which* event
we're listening to. When Doctrine loads, it looks for all services tagged
with ``doctrine.event_listener`` and makes sure those services are notified
on whatever event is specified.

It's the moment of truth! Reload the fixtures:

.. code-block:: bash

    php app/console doctrine:fixtures:load

Yes! Our ``die`` function is hit! 

To encode the password, copy in the ``encodePassword`` from our user fixtures
(``LoadUsers.php``) and rename it to ``handleEvent``. I'll also make a few
other changes, like getting the plain password value off of a ``plainPassword``
property and setting the encoded password on the user::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    // ...
    use Yoda\UserBundle\Entity\User;
    // ...

    private function handleEvent(User $user)
    {
        $plainPassword = $user->getPlainPassword();
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user);
        
        $password = $encoder->encodePassword($plainPassword(), $user->getSalt());
        $user->setPassword($password);
    }

This function is *almost* ready.

The Helpful LifecycleEventArgs Callback Argument
------------------------------------------------

Whenever Doctrine calls ``prePersist``, it passes us a special ``LifecycleEventArgs``
object. Add an argument for this::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    // ...

    use Doctrine\ORM\Event\LifecycleEventArgs;
    
    class UserListener
    {
        public function prePersist(LifecycleEventArgs $args)
        {
            die('Something is being inserted!');
        }
    }

We can use this to get the actual object being saved. If that object is an
instance of ``User``, then we know we want to act on it. If anything else
is being saved, we'll just ignore it. This is important because the function
is called when *any* entity is saved::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    // ...

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof User) {
            $this->handleEvent($entity);
        }
    }

Injecting the security.encoder_factory Dependency
-------------------------------------------------

We're *almost* done. You've probably already noticed that the ``$this->container``
line won't work here. We don't have a ``$container`` property - that's something
special to controllers and a few other places.

Again *not* a problem! The listener ultimately needs the ``security.encoder_factory``
service. So let's just inject it. Add a constructor with this as the first
argument::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    // ...
    
    use Symfony\Component\Security\Core\Encoder\EncoderFactory;
    
    class UserListener
    {
        private $encoderFactory;

        public function __construct(EncoderFactory $encoderFactory)
        {
            $this->encoderFactory = $encoderFactory;
        }
    }

Use the new property in ``handleEvent``::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    // ...

    private function handleEvent(User $user)
    {
        $plainPassword = $user->getPlainPassword();

        $encoder = $this->encoderFactory
            ->getEncoder($user)
        ;

        $password = $encoder->encodePassword($plainPassword, $user->getSalt());
        $user->setPassword($password);
    }

The listener is perfect. The last step is to tell the container about the
new constructor arugment in ``services.yml``:

.. code-block:: yaml

    # src/Yoda/UserBundle/Resources/config/services.yml
    services:
        doctrine.user_listener:
            class: Yoda\UserBundle\Doctrine\UserListener
            arugments: ["@security.encoder_factory"]
            tags:
                - { name: doctrine.event_listener, event: prePersist }

We're ready! Remove all the encoding logic from ``LoadUsers`` and just set
the plain password instead::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        // $user->setPassword($this->encodePassword($user, 'darthpass'));
        $user->setPlainPassword('darthpass');

        // ...
        // $admin->setPassword($this->encodePassword($admin, 'waynepass'));
        $user->setPlainPassword('waynepass');
    }

Reload the fixtures again!

.. code-block:: bash

    php app/console doctrine:fixtures:load

Woh, no errors! Ok, let's login. Hey, that works too!  As long as a new ``User``
has a ``plainPassword``, our listener will automatically handle the encoding
work for us. With this in place, remove the encoding logic from ``RegisterController``.

.. _`lifecycle callbacks`: http://docs.doctrine-project.org/en/latest/reference/events.html#lifecycle-callbacks
