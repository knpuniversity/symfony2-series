Repository Security
===================

Now, let's have our users provide an email and let them login using it or their username.

Giving the User an Email
------------------------

Let's start like we always do, by adding the property to the ``User`` class
with some Doctrine annotations::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

Next, generate or write a getter and a setter for the new property.
As a reminder, I'll use the ``doctrine:generate:entities`` command to do
this:

.. code-block:: bash

    php app/console doctrine:generate:entities UserBundle --no-backup

That little ``--no-backup`` prevents the command from creating a little backup
version of the file. You're using version control, so we don't need to be
overly cautions. You are using version control, right!!??

Next update the database schema to add the new field:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Finally, update the fixtures so that each user has an email::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        $user->setEmail('darth@deathstar.com');

        // ...
        $admin->setEmail('wayne@deathstar.com');

        // ...
    }

Reload everything to refresh the database:

    php app/console doctrine:fixtures:load

Doctrine Repositories
---------------------

When a user logs in right now, the security system queries for it using the
``username`` field. That's because we told it to in our 
:ref:`security.yml configuration<symfony-ep2-providers-config>`.
We could change it here to be ``email`` instead, but there's no way to say
``email`` *or* ``username``. We *can* make this more flexible. But first,
we need to learn about Doctrine repositories.

.. _symfony-ep2-repository-intro:

Find and open ``UserRepository``::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    namespace Yoda\UserBundle\Entity;

    use Doctrine\ORM\EntityRepository;

    class UserRepository extends EntityRepository
    {
    }

This is a Doctrine repository, and it was generated for us. Every entity,
has its own repository class and it knows that *this* is the repository class
for ``User`` because of an annotation on that class::

    /**
     * ...
     *
     * @ORM\Entity(repositoryClass="Yoda\UserBundle\Entity\UserRepository")
     */
    class User implements AdvancedUserInterface, Serializable

.. note::

    Actually, if you *don't* set the ``repositoryClass`` option, Doctrine
    just gives you a base repository class for that entity. 

Repositories are where query logic should live. We could create methods like
``findActiveUsers``, which would query the database for users that have a
value of ``1`` for the ``isActive`` field.

Using Repositories
------------------

And actually, we've already been using repositories in our project. Open
up ``EventController`` and check out the ``indexAction`` method::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('EventBundle:Event')->findAll();

        return array(
            'entities' => $entities,
        );
    }

The base EntityRepository and its Shortcuts
-------------------------------------------

To query for Events, we call ``getRepository`` on the entity manager. The
``getRepository`` method actually returns an instance of our very own
``EventRepository``. But when we open up that class, it's empty::

    // src/Yoda/EventBundle/Entity/EventRepository.php
    namespace Yoda\EventBundle\Entity;

    use Doctrine\ORM\EntityRepository;

    class EventRepository extends EntityRepository
    {
        // nothing here... boring!
    }

So where does the ``findAll`` method live? The answer is Doctrine's base
`EntityRepository`_ class, which we're extending. If we `open it`_, you'll
find some of the helpful methods that we talked about in the previous screencast,
including ``findAll()``. So *every* repository class comes with a few helpful
methods to begin with.

To prove that ``getRepository`` returns *our* ``EventRepository``, let's
override the ``findAll()`` method and just ``die`` to see if our code is triggered::

    // src/Yoda/EventBundle/Entity/EventRepository.php
    // ...

    class EventRepository extends EntityRepository
    {
        public function findAll()
        {
            die('NOOOOOOOOO!!!!!!!!!!');
        }
    }

And when we go to the events page, our page gives us an epic cry.

The repositoryClass Option
~~~~~~~~~~~~~~~~~~~~~~~~~~

Now, open up the Event entity. Above the class, you'll see an ``@ORM\Entity``
annotation::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\Entity(repositoryClass="Yoda\EventBundle\Entity\EventRepository")
     */
    class Event

Ah-hah! The ``repositoryClass`` is what's telling Doctrine to use ``EventRepository``. 
Let's remove that part and see what happens::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * ...
     * 
     * @ORM\Entity()
     */
    class Event

When we refresh, there's no epic cry. In fact, everything works perfectly!
We didn't tell Doctrine about our custom repository, so when we call ``getRepository``
in the controller, it just gives us an instance of the base ``EntityRepository``
class. That was nice! Our overridden ``findAll`` method is bypassed and the
real one is used.

Let's undo our damage by re-adding the ``repositoryClass`` option and remove
the dummy ``findAll`` method::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\Entity(repositoryClass="Yoda\EventBundle\Entity\EventRepository")
     */
    class Event

.. code-block:: php

    // src/Yoda/EventBundle/Entity/EventRepository.php
    // ...

    class EventRepository extends EntityRepository
    {
        public function findAll()
        {
            die('NOOOOOOOOO!!!!!!!!!!');
        }
    }

So every entity has its own repository with helpful methods like ``findAll``
for returning objects of that type. And when those shortcut methods won't
work, we'll add our own methods. All of our query logic *should* live inside
repositories - it'll make your life much more organized later.


.. _`EntityRepository`: http://www.doctrine-project.org/api/orm/2.3/class-Doctrine.ORM.EntityRepository.html
.. _`open it`: http://www.doctrine-project.org/api/orm/2.3/source-class-Doctrine.ORM.EntityRepository.html#25-244
