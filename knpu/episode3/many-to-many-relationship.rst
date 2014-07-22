AJAX and JSON Responses
=======================

I want you to attend my event! So, you are going to need to be able to RSVP.

Adding a ManyToMany Relationship
--------------------------------

First, think about how this would be stored in the database. One user should
be able to attend many events, and one event will have many attendees. This
is a classic ``ManyToMany`` relationship between the ``Event`` and ``User``
entities.

We already added a :doc:`ManyToOne relationship <doctrine-relationship>`
earlier and adding a ``ManyToMany`` will be very similar.

To model this, create a new ``attendees`` property on ``Event`` that'll hold
an array of Users that can't wait to go::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    protected $attendees;

Like with a ``ManyToOne``, we just need an annotation that tells
Doctrine what type of association this is and what entity it relates to::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToMany(targetEntity="Yoda\UserBundle\Entity\User")
     */
    protected $attendees;

Whenever you have a relationship that holds multiple things, you need to
add a ``__construct`` method and initialize it to an ``ArrayCollection``::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    use Doctrine\Common\Collections\ArrayCollection;
    // ...
    
    public function __construct()
    {
        $this->attendees = new ArrayCollection();
    }

We saw this on the ``User.events`` property earlier when we added the
:ref:`OneToMany association<inverse-relation-array-collection>`.


Next, we'll add a ``getter`` method only - I'll explain why the ``setter`` isn't
needed in a moment::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    public function getAttendees()
    {
        return $this->attendees;
    }

And that's it! Let's dump the schema update to see how this will change our
database:

.. code-block:: bash

    php app/console doctrine:schema:update --dump-sql

.. code-block:: sql

    CREATE TABLE event_user (
        event_id INT NOT NULL,
        user_id INT NOT NULL,
        INDEX IDX_92589AE271F7E88B (event_id),
        INDEX IDX_92589AE2A76ED395 (user_id),
        PRIMARY KEY(event_id, user_id))
        DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB;
    ALTER TABLE event_user
        ADD CONSTRAINT FK_92589AE271F7E88B FOREIGN KEY (event_id)
        REFERENCES yoda_event (id) ON DELETE CASCADE;
    ALTER TABLE event_user
        ADD CONSTRAINT FK_92589AE2A76ED395 FOREIGN KEY (user_id)
        REFERENCES yoda_user (id) ON DELETE CASCADE;

Doctrine is smart enough to know that we need a new "join table" that has 
``event_id`` and ``user_id`` properties. When we relate an ``Event`` to a 
``User``, it'll insert a new row in this table for us. Doctrine will handle 
all of those ugly details.

Re-run the command with ``--force`` to add the table:

.. code-block:: bash

    php app/console doctrine:schema:update --force

The Optional JoinTable
~~~~~~~~~~~~~~~~~~~~~~

With a ``ManyToMany``, you can *optionally* add a ``JoinTable`` annotation.
Add this only if you want to customize something about the join table. For
example, you can control the onDelete behavior that happens if a User or
Event is deleted::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToMany(targetEntity="Yoda\UserBundle\Entity\User")
     * @ORM\JoinTable(
     *      joinColumns={@ORM\JoinColumn(onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(onDelete="CASCADE")}
     * )
     */
    protected $attendees;

Run the ``doctrine:schema:update`` command again.

.. code-block:: bash

    php app/console doctrine:schema:update --dump-sql

Actually, no changes are needed: Doctrine uses this onDelete behavior by
default.
