ManyToOne Doctrine Relationships
================================

Right now, if I creat an Event, there's no database link back to my user.
We don't know which user created each Event.

To fix this, we need to create a ``OneToMany`` relationship from ``User``
to ``Event``. In the database, this will mean a ``user_id`` foreign key column
on the ``yoda_event`` table.

In Doctrine, relationships are handled by creating links between objects.
Start by creating an ``owner`` property inside Event::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...
    
    class Event
    {
        // ...

        protected $owner;
    }

For normal fields, we use the ``@ORM\Column`` annotation. But for relationships,
we use ``@ORM\ManyToOne``, ``@ORM\ManyToMany`` or ``@ORM\OneToMany``. This
is a ``ManyToOne`` relationship because many events may have the same *one*
``User``. I'll talk about the other 2 relationships later (:doc:`OneToMany <doctrine-inverse-relation>`,
:doc:`ManyToMany <many-to-many-relationship>`).

Add the ``@ORM\ManyToOne`` relationship and pass in the entity that forms
the other side::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="Yoda\UserBundle\Entity\User")
     */
    protected $owner;

Next, create the getter and setter for the the new property::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...
    
    use Yoda\UserBundle\Entity\User;
    
    class Event
    {
        // ...

        public function getOwner()
        {
            return $this->owner;
        }

        public function setOwner(User $owner)
        {
            $this->owner = $owner;
        }
    }

Notice that when we call ``setOwner``, we'll pass it an actual ``User`` object,
*not* the id of a user. But when you save an ``Event``, Doctrine will use
the owner's id value to populate an ``owner_id`` column on the ``yoda_event``
table. So we link objects to objects in PHP, and Doctrine takes care of setting
the foreign key id value for us. If you're newer to an ORM, this is one of
the toughest things to understand about Doctrine.

Updating the Database
---------------------

How can we update our database with the new column and foreign key? Why, with
the ``doctrine:schema:update`` command of course! I'll dump the SQL to the
terminal first to see it:

.. code-block:: bash

    php app/console doctrine:schema:update --dump-sql
    php app/console doctrine:schema:update --force

As expected, the SQL that's generated will add a new ``owner_id`` field to
``yoda_event`` along with the foreign key constraint.

ManyToOne Options
-----------------

Since I'm feeling fancy, let's configure a few things. Whenever you have
a ``ManyToOne`` annotation, you can optionally add an ``@ORM\JoinColumn``
annotation to control some database options.

JoinColumn onDelete
~~~~~~~~~~~~~~~~~~~

To add a database-level "ON DELETE" cascade behavior, add the ``onDelete``
option::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="Yoda\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $owner;

Now, let's run the ``doctrine:schema:update`` command again:

.. code-block:: bash

    php app/console doctrine:schema:update --dump-sql
    php app/console doctrine:schema:update --force

The SQL tells us that this actually re-creates the foreign key with the "on delete"
behavior. So if we delete a ``User``, the database will automatically delete
all rows in the ``yoda_event`` table that link to that user and ship them off into
hyper space.

The cascade Option
~~~~~~~~~~~~~~~~~~

Another common option is ``cascade`` on the actual ``ManyToOne`` part::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="Yoda\UserBundle\Entity\User", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $owner;

This is like ``onDelete``, but in the opposite direction. With this, if we
delete an Event, it will *cascade* the remove onto the owner. In other words,
If I delete an Event, it wil also delete the User who is the owner.

Run ``doctrine:schema:update`` again:

.. code-block:: bash

    php app/console doctrine:schema:update --dump-sql

Now, it doesn't want to change our database at all. Unlike ``onDelete``,
this behavior is enforced entirely by Doctrine in PHP, not in the database layer.

.. tip::

    You can also cascade ``persist``, which is useful at times with ``ManyToMany``
    relationship where you're creating new items in the relationship.

Remove the ``cascade`` option because it's dangerous in our situation::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="Yoda\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $owner;

If we delete an Event, we definitely don't want that to delete the Event's
owner. Darth would be so angry.

Linking an Event to its owner on creation
-----------------------------------------

Time to put our shiny relationship to the test. When a new ``Event`` object
is created, let's associate it with the ``User`` object for whoever is logged
in::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    public function createAction(Request $request)
    {
        // ...

        if ($form->isValid()) {
            $user = $this->getUser();

            // ...
        }
    }

To complete the link, just call ``setOwner`` on the Event and pass in the *whole*
``User`` object::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function createAction(Request $request)
    {
        // ...

        if ($form->isValid()) {
            $user = $this->getUser();

            $entity->setOwner($user);

            // ... the existing save logic
        }
    }

Yep, that's it. When we save the Event, Doctrine will automatically grab
the id of the ``User`` object and place it on the ``owner_id`` field.

Time to test! Login as Wayne. Remember, he has ``ROLE_ADMIN``, which also
means he has ``ROLE_EVENT_CREATE`` because of the ``role_hierarchy`` section
in ``security.yml``.

Now, fill in some basic data and submit it. To see the result, use the query
tool to list the events:

.. code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM yoda_event"

Sure enough, our newest event is linked back to our user! #Winning
