OneToMany: The Inverse Side of a Relationship
=============================================

Earlier, we gave every ``Event`` an owner. This was our first Doctrine relationship:
a ``ManyToOne`` from ``Event`` to ``User``.

This lets us do things like call ``$event->getOwner()``. Let's use this to
print the owner of an ``Event``:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}
    
    {{ entity.owner.username }}

But what about the opposite direction? Can we start with a ``$user`` object
and call ``getEvents()`` to get all the ``Event`` objects the User has created?

Trying User::getEvents()
------------------------

Open up the play script we made in episode one to test this out. Clear
out all the code below the setup, then query for a ``User`` object and call
``getEvents()`` on it::

    // play.php
    // ...
    // all our setup is done!!!!!!

    $em = $container->get('doctrine')->getManager();

    $user = $em
        ->getRepository('UserBundle:User')
        ->findOneBy(array('username' => 'wayne'))
    ;

    foreach ($user->getEvents() as $event) {
        var_dump($event->getName());
    }

Now run the script:

.. code-block:: bash

    php play.php

It blows up!

.. highlights::

    Call to undefined method Yoda\UserBundle\Entity\User::getEvents()

This shouldn't surprise us. The ``User`` object is a plain PHP object and
we've never added a ``getEvents`` method to it.

Setting up User::getEvents()
----------------------------

We *can* do this, and it's not hard, but it can be tricky to understand.
It involves 3 steps.

Step 1: Add the OneToMany annotation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Start by adding an ``events`` property to ``User``. Give it a ``OneToMany``
annotation::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\OneToMany(targetEntity="Yoda\EventBundle\Entity\Event", mappedBy="owner")
     */
    protected $events;

This looks just like the ``ManyToOne`` annotation we used inside ``Event``,
except for the extra ``mappedBy`` property, which tells Doctrine which property
inside ``Event`` this maps to.

Step 2: Add inversedBy to ManyToOne
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Second, now that we have the ``OneToMany``, you also need to go to ``Event``
and add an ``inversedBy`` option pointing back to the ``events`` property
on ``User``::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(
     *      targetEntity="Yoda\UserBundle\Entity\User",
     *      inversedBy="events"
     * )
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $owner;

I broke this onto multiple lines only to make things more readable.

.. _`inverse-relation-array-collection`:

Step 3: Initializing the ArrayCollection
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Finally, in ``User``, create a ``__construct`` method and set the ``events``
property to a special ``ArrayCollection`` object::

    // src/Yoda/UserBundle/Entity/User.php
    // ...
    use Doctrine\Common\Collections\ArrayCollection;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

In a perfect world, the ``events`` property would just be an array of ``Event``
objects. But for Doctrine to work its magic, we need it to be an ``ArrayCollection``
object instead. But no worries, this object looks and feels just like an
array, so just think of it like one.

Complete things by adding the getter and setter for the the ``events`` property::

    // src/Yoda/UserBundle/Entity/User.php
    // ..

    public function getEvents()
    {
        return $this->events;
    }

    public function setEvents($events)
    {
        $this->events = $events;
    }

Now try the play script:

.. code-block:: bash

    php play.php

It works! And we see both event names, since wayne owns both of them.

Behind the scenes, Doctrine automatically queries for the two event objects
owned by this wayne dude and puts them on the ``events`` property.

Owning Versus Inverse Side
~~~~~~~~~~~~~~~~~~~~~~~~~~

Notice that we didn't have to make any database schema changes for this to
work. That's really important. because adding this side of the relationship
is purely for convenience. Our database already has all the information it
needs to link ``Users`` and ``Events``.

The ``OneToMany`` side of a relationship is always optional, and called the
"inverse" side. If you need the convenience, add it. If you don't, don't bother
with it.

The ``ManyToOne`` side of the relationship is where the foreign key actually
lives in the database, and it's known as the "owning" side. You'll *always*
need to specify the owning side of a relationship.

Caution: Don't "set" the Inverse Side
-------------------------------------

The inverse side is special for another important reason. If we called ``setEvents()``
on a ``User`` and saved, the new events would be ignored. Only the "owning"
side of the relationship is used when saving.

For example, in ``createAction`` of ``EventController``, we're currently
calling ``setOwner`` on Event::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    // this works
    $entity->setOwner($this->getUser());

This is perfect because ``owner``, coincidentally, is the *owning* side of
the relationship. In a ``ManyToOne`` and ``OneToMany`` association, the *owning*
side is always the singular side. We are talking about *one* owner, so it's
the owning side.

If instead we decided to call ``setEvents()`` on the ``User``, we'd be setting
the inverse side, and Doctrine would completely ignore it::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    // this does nothing
    // if we *only* had this part, the relationship would not save
    // $events = $this->getUser()->getEvents();
    // $events[] = $entity;
    // $this->getUser()->setEvents($events);

In fact, let's just remove ``setEvents`` from ``User``, so that nobody calls
this method on accident::

    // src/Yoda/UserBundle/Entity/User.php
    // ..

    public function getEvents()
    {
        return $this->events;
    }

    // setEvents() has been removed

The problem of not being able to set the relationship from both sides can
be particularly tricky when working with a form that embeds many sub-forms.
If you run into this, check out the `cookbook entry on the topic at symfony.com`_.
Also check out the reference manual for the `collection form type`_.

.. _`Symfony Plugin`: http://knpuniversity.com/screencast/symfony2-ep1/bundles#the-phpstorm-symfony-plugin
.. _`cookbook entry on the topic at symfony.com`: http://symfony.com/doc/current/cookbook/form/form_collections.html
.. _`collection form type`: http://symfony.com/doc/current/reference/forms/types/collection.html
