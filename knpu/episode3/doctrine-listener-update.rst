Doctrine Listeners on Update
============================

But what if a user *updates* their password? Hmm, our listener isn't called
on updates, so the encoded password can *never* be updated. Crap!

Add a second tag to ``services.yml`` to listen on the ``preUpdate`` event
and create the ``preUpdate`` method by copying from ``prePersist``:

.. code-block:: yaml

    # src/Yoda/UserBundle/Resources/config/services.yml
    services:
        doctrine.user_listener:
            class: Yoda\UserBundle\Doctrine\UserListener
            arguments: ["@security.encoder_factory"]
            tags:
                - { name: doctrine.event_listener, event: prePersist }
                - { name: doctrine.event_listener, event: preUpdate }

Add a ``die`` statement so we can test things::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    // ...

    public function preUpdate(LifecycleEventArgs $args)
    {
        die('UUPPPPPDDAAAAAATING!');
     
        $entity = $args->getEntity();
        if ($entity instanceof User) {
            $this->handleEvent($entity);
        }
    }


Also, if the ``plainPassword`` field isn't set, don't do any work. This will
happen if a ``User`` is being saved, but their password isn't being changed::

    // src/Yoda/UserBundle/Doctrine/UserListener.php
    // ...

    private function handleEvent(User $user)
    {
        if (!$user->getPlainPasword()) {
            return;
        }

        // ...
    }

Testing the Update
------------------

We can't test this easily because we don't have a way to update users yet.
No worries. Just open up the play script from episode 1. We already have
a user here - just change his plain password and save::

    // play.php
    // ...

    use Doctrine\ORM\EntityManager;

    $em = $container->get('doctrine')
        ->getEntityManager()
    ;

    $wayne = $em
        ->getRepository('UserBundle:User')
        ->findOneByUsernameOrEmail('wayne');
    
    $wayne->setPlainPassword('new');
    $em->persist($user);
    $em->flush();

Ok, run the play script:

.. code-block:: bash

    php play.php

Hmm, it didn't hit our ``die`` statement. Our listener function wasn't called.

Gotcha 1: Event Listeners don't fire on Unchanged Objects
---------------------------------------------------------

It's a gotcha! The ``plainPassword`` property isn't saved to Doctrine,
but we do *use* it to set the ``password`` field, which *is* persisted.

The problem is that when we change *only* the ``plainPassword`` field, the
``User`` looks "unmodified" to Doctrine. So, instead of calling our listener,
it does nothing.

To fix the issue, let's nullify the ``password`` field whenever ``plainPassword``
is set::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        $this->setPassword(null);

        return $this;
    }

Since ``password`` *is* persisted to Doctrine, this is enough to trigger
all the normal behavior. Our listener should make sure ``password`` is set
to the encoded value, and not left blank.

Now run the play script again. Great, it hits the ``die`` statement. Remove
that and try it again.

No errors, so let's try to login. Yes!

We just saw prePersist and preUpdate and Doctrine has several other events
you can find on their website. Symfony also has events, which are fired at
different points during the request-handling process.

Fortunately, Symfony's event system is *very* similar to Doctrine's. Don't
you love it when good ideas are shared?
