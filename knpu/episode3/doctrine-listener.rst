Doctrine Listeners
==================

We've done a lot of work so its time to see one of the most powerful
features of Doctrine: events. Using an ORM gives us the flexibility to have
hooks that are executed whenever certain things are done, like when an entity
is first persisted to the database, updated, or deleted.

Lifecycle Callbacks
-------------------

To see how this works, remove the ``@Gedmo`` annotation above the ``created``
property in the ``Event`` entity::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

This line, along with the Doctrine extensions library that we installed earlier,
caused the ``created`` column to be automatically set. We could have also
done this by using our very own `lifecycle callbacks`_. A "lifecycle callback"
is a fancy term for a function that you create that Doctrine calls when certain
things happen, like when your entity is first added into the database.

To enable lifecycle callbacks on an entity, add the ``HasLifecycleCallbacks``
annotation::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...
    
    /**
     * @ORM\Table(name="yoda_event")
     * @ORM\Entity(repositoryClass="Yoda\EventBundle\Entity\EventRepository")
     * @ORM\HasLifecycleCallbacks
     */
    class Event
    {
        // ...
    }

Next, create a ``prePersist()`` method that sets the ``created`` column if
it's not already set::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (!$this->getCreated()) {
            $this->setCreated(new \DateTime());
        }
    }

The name of this method isn't important, but the ``PrePersist`` annotation
we put above it is. The "PrePersist" hook is called right before an entity
is saved to the database for the first time. There are several other lifecycle
events to handle when an entity is updated or deleted.

Let's try it! Reload your fixtures and then query to see the events:

.. code-block:: bash

    php app/console doctrine:fixtures:load
    php app/console doctrine:query:sql "SELECT * FROM yoda_event"

The fact that the ``created`` column is set is proof that our new lifecycle
callback is working.

The beauty of lifecycle callbacks is that they're really easy to setup and
use. The problem is that you don't have access to the container or any of
the useful services from inside a lifecycle callback. You have access to
the data in your entity, but not to the entity manager, the router or anything else.

Event Listeners
---------------

The solution is to use a slight spin on lifecycle callbacks: events. Events
work just like lifecycle callbacks except that the method that's executed
lives outside of your entity inside another class. As you may have guessed,
that "other class" will be a service.

Recall from an earlier screencast that when we create a new user, we manually
encoded the user's plain-text password before persisting it. This can be handled
automatically for us with a Doctrine event.

Start by creating a new ``Listener`` directory inside ``UserBundle`` with a class
called ``UserListener``::

    // src/Yoda/UserBundle/Listener/UserListener.php
    namespace Yoda\UserBundle\Listener;
    
    class UserListener
    {
        public function prePersist()
        {
            die('YAY TESTING!');
        }
    }

Keep the class empty except for a ``prePersist`` method. To prove that this
is called, I'll just put a `die` statement.

Next, register this new class as a service. Since we don't already have a
``services.yml`` file, create one inside a new ``config`` directory:

.. code-block:: yaml

    # src/Yoda/UserBundle/Resources/config/services.yml
    services:

Unlike our ``EventBundle``, we're missing the ``Extension`` class that might
automatically load the ``services.yml`` file for us. That's not a problem -
just import it manually from your main ``config.yml`` file:

.. code-block:: yaml

    imports:
        # ...
        - { resource: "@UserBundle/Resources/config/services.yml" }

.. tip::

    When we're importing another file, ``@UserBundle`` is a shortcut to the
    absolute path for the ``UserBundle`` directory.

Create the service like normal, but give it a special tag called ``doctrine.event_listener``:

.. code-block:: yaml

    # src/Yoda/UserBundle/Resources/config/services.yml
    services:
        yoda_user.listener.user_listener:
            class: Yoda\UserBundle\Listener\UserListener
            tags:
                - { name: doctrine.event_listener, event: prePersist }

Also specify ``prePersist`` as the event you want to listen to. When Doctrine
loads, it looks for all services tagged with ``doctrine.event_listener`` and
makes sure those services are notified on whatever event is specified.

Try this all out by loading the fixtures:

.. code-block:: bash

    php app/console doctrine:fixtures:load

When the users are saved, our event listener is notified and the ``die`` statement
is hit. Great!

To encode the password, copy in the ``encodePassword`` from our user fixtures
(``LoadUsers.php``) and rename it to ``handleEvent``. I'll also make a few
other changes, like getting the plain password value off of a ``plainPassword``
property and setting the encoded password on the user::

    // src/Yoda/UserBundle/Listener/UserListener.php
    // ...
    use Yoda\UserBundle\Entity\User;
    // ...

    private function handleEvent(User $user)
    {
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user)
        ;

        $password = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());
        $user->setPassword($password);
    }

The Helpful LifecycleEventArgs Callback Argument
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Doctrine calls the ``prePersist`` method whenever *any* object is persisted
to Doctrine, be it a ``User``, an ``Event`` or any other object. When Doctrine
calls ``prePersist``, it passes it a special ``LifecycleEventArgs`` object::

    // src/Yoda/UserBundle/Listener/UserListener.php
    // ...

    use Yoda\UserBundle\Entity\User;
    use Doctrine\ORM\Event\LifecycleEventArgs;
    
    class UserListener
    {
        public function prePersist(LifecycleEventArgs $args)
        {
            $entity = $args->getEntity();
            if ($entity instanceof User) {
                $this->handleEvent($entity);
            }
        }
    }

We can use it to get the actual object being saved. If that object is an instance
of ``User``, then we know we want to act on it. If anything else is being saved,
we'll just ignore it.

Unfortunately, the ``$this->container`` code inside ``handleEvent`` isn't
going to work. Our fixture classes have access to the container object, but
we don't. Again not a problem! Since the service that we ultimately need is
the ``security.encoder_factory``, just add a constructor to your class with
it as the first argument::

    // src/Yoda/UserBundle/Listener/UserListener.php
    // ...
    
    use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
    
    class UserListener
    {
        private $encoderFactory;

        public function __construct(EncoderFactoryInterface $encoderFactory)
        {
            $this->encoderFactory = $encoderFactory;
        }
    }

With some quick detective work, we can see that the ``EncoderFactoryInterface``
can be used to type-hint the argument, if we care to do that.

.. note::

    The "quick detective work" involves looking up the class behind the
    ``security.encoder_factory`` service with ``container:debug`` and then
    opening that class to find the final interface that we actually care
    about. It's an art, not a complete science.

Update the service configuration to pass the encoder factory as the first
argument:

.. code-block:: yaml

    # src/Yoda/UserBundle/Resources/config/services.yml
    services:
        yoda_user.listener.user_listener:
            class: Yoda\UserBundle\Listener\UserListener
            arguments:
                - "@security.encoder_factory"
            tags:
                - { name: doctrine.event_listener, event: prePersist }

Finally, update ``handleEvent`` to use the encoder factory that's now set
as a property on the object::

    // src/Yoda/UserBundle/Listener/UserListener.php
    // ...

    private function handleEvent(User $user)
    {
        $encoder = $this->encoderFactory->getEncoder($user);

        $password = $encoder->encodePassword($user->getPlainPassword(), $user->getSalt());
        $user->setPassword($password);
    }

Before we try it, remove all of the encoding logic inside ``LoadUsers``::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        // remove the password setting, and only set the plain password
        // $user->setPassword($this->encodePassword($user, 'user'))
        $user->setPlainPassword('user');

        // ...
        // $admin->setPassword($this->encodePassword($admin, 'admin'))
        $admin->setPlainPassword('admin');
    }

Now, reload the fixtures and try to log in. It works! As long as our new ``User``
has a ``plainPassword``, our new Doctrine listener will automatically handle
the encoding work for us. With this in place, we can also remove the encoding
logic from the ``RegisterController``.

EventListeners on Update
------------------------

Our current listener is called when a new object is created, but not when
an existing object is updated. Add a second tag to ``services.yml`` to listen
on the ``preUpdate`` event and create the ``preUpdate`` method by copying from
``prePersist``:

.. code-block:: yaml

    # src/Yoda/UserBundle/Resources/config/services.yml
    services:
        yoda_user.listener.user_listener:
            class: Yoda\UserBundle\Listener\UserListener
            arguments:
                - "@security.encoder_factory"
            tags:
                - { name: doctrine.event_listener, event: prePersist }
                - { name: doctrine.event_listener, event: preUpdate }

.. code-block:: php

    // src/Yoda/UserBundle/Listener/UserListener.php
    // ...
    
    public function preUpdate(LifecycleEventArgs $args)
    {
        die('UPDATE');

        $entity = $args->getEntity();
        if ($entity instanceof User) {
            $this->handleEvent($entity);
        }
    }

    private function handleEvent(User $user)
    {
        if (!$user->getPlainPassword()) {
            return;
        }

        // ...
    }

If the ``plainPassword`` field isn't set, don't do any work, since it means
that the ``User`` is being saved, but his/her password isn't being changed.
Temporarily add a ``die`` statement so we can check our progress.

Open up our play script. Query for one of the users in our fixture, change
the user's password, and flush the changes to Doctrine::

    // play.php
    // ...

    $em = $container->get('doctrine')
        ->getEntityManager()
    ;

    $user = $em
        ->getRepository('UserBundle:User')
        ->findOneBy(array('username' => 'user'))
    ;
    
    $user->setPlainPassword('new');
    $em->persist($user);
    $em->flush();

Now run the play script:

.. code-block:: bash

    php play.php

Hmm, it didn't hit our ``die`` statement.

Event Listeners don't fire on Unchanged Objects
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This goes back to the ``plainPassword`` property. This property is important
to us because we use it to populate the encoded ``password`` field. The problem
is that it's not actually persisted to the database. When we change *only*
the ``plainPassword`` field, our ``User`` looks "unmodified" to Doctrine. So, when
we save the user, Doctrine does nothing.

To fix the issue, let's nullify the ``password`` field manually whenever ``plainPassword``
is set::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        $this->setPassword(null);
    }

Since ``password`` *is* persisted to Doctrine, this is enough to trigger
the normal events.

Now run the play script again. Great, it hits the ``die`` statement. Remove
that and try it again.

Everything should have worked, so let's try logging in with the new password.
Hmm, it's not working. This is due to a special problem when dealing with
the ``preUpdate`` listener. The fix is odd, but just keep it in mind whenever
you need to update an object in the ``preUpdate`` event::

    // src/Yoda/UserBundle/Listener/UserListener.php
    // ...

    private function handleEvent(User $user)
    {
        if (!$user->getPlainPassword()) {
            return false;
        }

        // ...
        
        return true;
    }

First, return ``true`` from ``handleEvent`` so we know if the user's password
has been updated. If it has, add a few special lines of code in ``preUpdate``::

    // src/Yoda/UserBundle/Listener/UserListener.php
    // ...
    
    public function preUpdate(LifecycleEventArgs $args)
    {
        die('UPDATE');

        $entity = $args->getEntity();
        if ($entity instanceof User) {
            if ($this->handleEvent($entity)) {
                $em = $args->getEntityManager();
                $classMetadata = $em->getClassMetadata(get_class($entity));
                $em->getUnitOfWork()->recomputeSingleEntityChangeSet($classMetadata, $entity);
            }
        }
    }

Again, this code might look strange, but it basically tells Doctrine to re-check
the entity for changes so that the ``password`` change is noticed and included
in the update statement. This is *not* needed in ``prePersist`` - it's a special
hack needed just for ``preUpdate``.

Try running the play script again:

.. code-block:: bash

    php play.php

When we login this time, it works!

Doctrine events are a very powerful feature and several other events exist
and can be seen on Doctrine's website. As mentioned earlier, Symfony itself
has a number of events that you can listen to as Symfony boots and processes
each request. That event system is *very* similar to Doctrine's and also
very powerful. We won't talk about it here, but there is a cookbook article
that gives an example and a possible use for these events.

.. _`lifecycle callbacks`: http://docs.doctrine-project.org/en/latest/reference/events.html#lifecycle-callbacks
