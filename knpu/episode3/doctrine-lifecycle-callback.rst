Doctrine is in your Lifecycle (with Callbacks)
==============================================

Remember when we used StofDoctrineExtensions to set the Event's ``slug``
for us? That magic works by leveraging one of the most powerful features of
Doctrine: events. Doctrine gives us the flexibility to have hooks that are
called whenever certain things are done, like when an entity is first persisted,
updated, or deleted.

Open up ``Event`` and remove the ``@Gedmo`` annotation above ``createdAt``.
Let's see if we can set this ourselves::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

Replace this with a new function called ``prePersist`` that sets the value::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    public function prePersist()
    {
        if (!$this->getCreatedAt()) {
            $this->setCreatedAt(new \DateTime());
        }
    }

Hey, don't get too excited! This won't work yet, but if we could tell Doctrine
to call this before inserting an Event, we'd be golden!

The secret is a called `lifecycle callbacks`_: a fancy word for a function
that Doctrine will call when something happens, like when an entity is first
inserted.

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

Now just put a ``PrePersist`` annotation above our function::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (!$this->getCreated()) {
            $this->createdAt = new \DateTime();
        }
    }

``PrePersist`` is called only when an entity is first inserted, and there
are other lifecycle events like ``PreUpdate`` and ``PreRemove``.

Cool, let's give it a test! Reload your fixtures and then query to see the
events:

.. code-block:: bash

    php app/console doctrine:fixtures:load
    php app/console doctrine:query:sql "SELECT * FROM yoda_event"

The ``createdAt`` column is set so this must be working.

Lifecycle callbacks are brilliant because they're just so easy to setup.

But they have one big limitation. Because the callback is inside an entity,
we don't have access to the container or any services. This wasn't a problem
here, but what if we needed to access the ``router`` or the ``logger``?

The solution is to use a slight spin on lifecycle callbacks: events.

.. _`lifecycle callbacks`: http://symfony.com/doc/current/book/doctrine.html#lifecycle-callbacks
