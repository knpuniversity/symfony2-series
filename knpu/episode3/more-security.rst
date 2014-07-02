More Security
=============

We've already setup a beautiful security system that loads users from the
database. Let's use it now to really make our application shine.

Restricting more paths with Access Controls
-------------------------------------------

First, let's make sure that all of our pages are properly locked down. Earlier,
in ``security.yml`` we restricted the ``/new`` and ``/create`` pages to users
who have ``ROLE_USER``. Since we've set things up so that every user has this role,
this allows all authenticated users to create events. Run the ``router:debug``
task:

.. code-block:: bash

    php app/console router:debug

You'll see that we missed a few important pages: the event edit, update
and delete pages. We can protect these pages in the same way by using a regular
expression access control:

.. code-block:: yaml

    # app/config/security.yml
    # ...

    access_control:
        - { path: ^/new, roles: ROLE_USER }
        - { path: ^/create , roles: ROLE_USER }
        - { path: ^/(\d+)/(edit|update|delete), roles: ROLE_USER }

Remember that you can also run this check manually inside the controller.
We'll revisit that method a bit later.

Linking an Event to a User: ManyToOne Database Relationship
-----------------------------------------------------------

Right now, events are "anonymous". Even if I'm the one that creates the event,
there's no database link back to my user to record it. To fix this, we need
to create a OneToMany relationship from User to Event. In the database, this
will mean a ``user_id`` foreign key column on the ``yoda_event`` table.

In Doctrine, relationships are handled by creating links between objects.
Start by creating an ``owner`` property inside Event::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...
    
    class Event
    {
        // ...

        protected $owner;
    }

The Doctrine annotation for a relationship is special, but simple. Just specify
that we have a ``ManyToOne`` relationship and pass in the entity that forms
the other side::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="Yoda\UserBundle\Entity\User")
     */
    protected $owner;

Next, create the getter and setter for the this new property::

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

One key thing to notice is that the value of the owner property will be an
actual User object, not just a user id.

ManyToOne Options
~~~~~~~~~~~~~~~~~

Quickly, let's look at the ``ManyToOne`` annotation again and see a few more
options. The optional ``JoinColumn`` annotation can be added to control some
database options. To add a database-level "ON DELETE" cascade, add the ``onDelete``
option::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="Yoda\UserBundle\Entity\User")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $owner;

Now, when we delete a user, all of the related events will also be deleted.

Another important option is ``cascade``::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(targetEntity="Yoda\UserBundle\Entity\User", cascade={"remove"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $owner;

Setting this to ``remove`` tells Doctrine to "cascade" the removal of an
Event down to the related User. In other words, when an Event id deleted,
that delete should "cascade" through this relationship and also remove the
User. This is similar to the database-level cascade, except that it happens
at the Doctrine level operates in the opposite direction. If this doesn't
totally make sense yet, don't worry young jedi - just be aware that these
options exist. For more details on all of this, see the `Working with Associations`_
section of Doctrine's documentation.

Head to the console to update the schema:

.. code-block:: bash

    php app/console doctrine:schema:update --dump-sql
    php app/console doctrine:schema:update --force

As expected, the SQL that's generated will add a new ``owner_id`` field to
``yoda_event`` along with the foreign key constraint.

Linking an Event to its owner on creation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

With this in place, let's relate the current user to a new ``Event`` object
when it's created. Get the ``User`` object for the current authenticated user
by getting the ``security.context`` service and then getting the user from
it::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    public function createAction(Request $request)
    {
        // ...

        if ($form->isValid()) {
            $user = $this->get('security.context')
                ->getToken()
                ->getUser()
            ;

            // ...
        }
    }

There's actually a shorter way to get the user, which you'll see in a few minutes.
To complete the link, just call ``setOwner`` on the Event and pass in the *whole*
``User`` object::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function createAction(Request $request)
    {
        // ...

        if ($form->isValid()) {
            $user = $this->get('security.context')
                ->getToken()
                ->getUser()
            ;

            $entity->setOwner($user);

            // ... the existing save logic
        }
    }

When the event saves, Doctrine will automatically grab the id of the ``User``
and place it on the ``owner_id`` field.

Let's try it out. Fill in some basic data and submit it. To see the result,
use the query tool to list the events:

.. code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM yoda_event"

Sure enough, our newest event is linked back to our user! Now that is really
cool!

Sharing Data between Fixture Classes
------------------------------------

Next, we need to update our fixtures so that each event has an owner. This
is easy, but a bit wordy, so we'll push through it quickly. Right now, we
have two fixture classes: one that loads events and one that loads users.
Start in the ``LoadUsers`` class. Now that events depend on users, we'll want
this fixture class to be executed before the events. To force this, add a
new interface called ``OrderedFixtureInterface``. This requires one method
called ``getOrder``, which will return 10::

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
        public function getOrder()
        {
            return 20;
        }
    }

Now that the ordering is right, head back to ``LoadUsers`` and replace the
standard ``FixtureInterface`` with a new ``AbstractFixture`` base class::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    use Doctrine\Common\DataFixtures\AbstractFixture;

    class LoadUsers extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
    {
        // ...
    }

This class allows us to store objects that we create here so that other fixture
classes can use them. Store the ``user`` by calling ``addReference``::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        $this->addReference('user-user', $user);
    }

.. note::

    The key ``user-user`` is just an arbitrary name. We will use it to grab
    this object in a second.

Make the same change in ``LoadEvent``::

    // src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php
    // ...

    use Doctrine\Common\DataFixtures\AbstractFixture;

    class LoadEvents extends AbstractFixture implements OrderedFixtureInterface
    {
        // ...
    }

.. note::

    The only purpose of extending ``AbstractFixture`` is to share objects
    between fixtures.

To get the stored user back out, just call ``getReference``. Once we have
the ``User``, we can set it as the owner for both new Events::

    // src/Yoda/EventBundle/DataFixtures/ORM/LoadEvents.php
    // ...
    public function load(ObjectManager $manager)
    {
        $user = $this->getReference('user-user');
        // ...
        
        $event1->setOwner($user);
        $event2->setOwner($user);
        
        // ...
        $manager->flush();
    }

After all this work, let's reload the fixtures and check to make sure things
look ok. Relating objects that live in different fixture classes is easy,
but still can be a bit of a pain. My recommendation is to create only a few
fixture classes to minimize the issue. I'd also recommend copying the `fixture setup`_
from the documentation instead of writing it by hand. This all may be a little
shorter in the future, but it's still doable now.

Restricting Edit Access to Owners
---------------------------------

Now that every ``Event`` has an owner, let's prevent non-owners from editing
or deleting events that aren't theirs. The easiest way to do this is just to
compare the current ``User`` with the event's owner and deny access if they
don't match. Remember, you can deny access at any point in your application by
throwing the special ``AccessDeniedException``. Since we'll need to include
this little bit of code in ``editAction``, ``updateAction`` and ``deleteAction``,
let's create a private function that does the work::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    use Symfony\Component\Security\Core\Exception\AccessDeniedException;
    // ...

    private function checkOwnerSecurity(Event $event)
    {
        $user = $this->get('security.context')
            ->getToken()
            ->getUser()
        ;

        if ($user != $event->getOwner()) {
            throw new AccessDeniedException('You are not the owner!!!');
        }
    }

It's now pretty simple to deny access to non-owners anywhere we need to::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('EventBundle:Event')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Event entity.');
        }

        $this->checkOwnerSecurity($entity);
        // ...
    }

We can try this out by logging in as the admin user and trying to edit the
page. Remember that if we were in the production environment, we'd see the
"Access Denied" page.

.. tip::

    There is an even cleaner, but more advanced, approach to restricting
    access to specific objects called "voters". You can learn more about
    these from our :ref:`Question and Answer Day<symfony2-acl-voters>`. An
    even more advanced approach is available called `ACLs`_.

Since only owners can edit events, add an ``if`` statement around the edit
link that hides it for all other users:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/Event/show.html.twig #}
    {# ... #}

    {% if app.user == entity.owner %}
        <a class="button" href="{{ path('event_edit', {'id': entity.id}) }}">edit</a>
    {% endif %}

To get the current authenticated user object, just use `app.user global variable`_.
If you ever need access to the current User object, ``app.user`` is the key.
But be careful where and how you use it. For example, calling ``app.user.username``
will *only* work if the user is actually logged in. If the user is anonymous,
``app.user`` will be null and calling ``username`` on it will break your page.
Wrapping it in an if statement would make this safe.

Using a shortcut Base Controller Class
--------------------------------------

Everything works perfectly, but I do have a few concerns. For one, getting
the security context inside a controller is too much work. To fix this, create
a new class called ``Controller`` inside the ``EventBundle``. This class should
extend Symfony's standard base controller. But be careful, since both classes have
the same name, we need to alias Symfony's class to ``BaseController``::

    // src/Yoda/EventBundle/Controller/Controller.php

    namespace Yoda\EventBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller as BaseController;

    class Controller extends BaseController
    {
        // ...
    }

Inside this class, create a function that returns the security context from
the service container::

    // src/Yoda/EventBundle/Controller/Controller.php
    // ...
    
    public function getSecurityContext()
    {
        return $this->container->get('security.context');
    }

Head back to the ``EventController``. Right now, this extends Symfony's controller,
which means that we get access to all of its shortcuts. Remove the ``use``
statement for Symfony's controller and replace it with a ``use`` statement
for the new class we just created::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Yoda\EventBundle\Controller\Controller;

    class EventController extends Controller
    {
        // ...
    }

Now that we're extending our own base class, we have access to all of Symfony's
shortcut methods *plus* the new ``getSecurityContext`` method we just created.
Actually, the ``use`` statement is optional since ``EventController`` and
the new ``Controller`` class live in the same namespace. Use the new ``getSecurityContext``
method to shorten things in the controller::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function createAction(Request $request)
    {
        // ...

        if ($form->isValid()) {
            $user = $this->getSecurityContext()
                ->getToken()
                ->getUser()
            ;
            // ...
        }
    }

    // ...

    private function checkOwnerSecurity(Event $event)
    {
        $user = $this->getSecurityContext()
            ->getToken()
            ->getUser()
        ;
        // ...
    }    

Now go to RegisterController and make the same change::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    use Yoda\EventBundle\Controller\Controller;

    class RegisterController extends Controller
    {
        // ...

        private function authenticateUser(UserInterface $user)
        {
            // ...

            $this->getSecurityContext()->setToken($token);
        }
    }

Using PHPDoc for Auto-Completion
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Using your own base controller is a great way to allow yourself to write faster
and faster code. If you're using an IDE, you'll notice that it doesn't recognize
the ``setToken`` method on the security context object. To fix this, let's
add some PHPDoc to our new method::

    // src/Yoda/EventBundle/Controller/Controller.php
    // ...

    /**
     * @return \Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->container->get('security.context');
    }

The ``@return`` tag lets us tell our editor what type of object this method
returns. To find out what the ``security.context`` object is, use the ``container:debug``
task:

.. code-block:: bash

    php app/console container:debug security.context

Copy the class name from the command. Now, our editor recognizes the ``setToken``
method and can suggest any other methods on that class.

Let's keep going by adding a ``getUser`` shortcut method. Actually, in Symfony 2.1,
the base controller already has this method. I'll override that method here,
not because I need to change it's behavior, but because I want to be able
to tell my IDE exactly what type of object to expect::

    // src/Yoda/EventBundle/Controller/Controller.php
    // ...

    /**
     * @return \Yoda\UserBundle\Entity\User
     */
    public function getUser()
    {
        return parent::getUser();
    }

We can use this immediately in the EventController to make our life easier::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function createAction(Request $request)
    {
        // ...

        if ($form->isValid()) {
            $entity->setOwner($this->getUser());
            // ...
        }
    }

    // ...

    private function checkOwnerSecurity(Event $event)
    {
        $user = $this->getUser();
        // ...
    }

Also open up the ``DefaultController`` class and remove the ``use`` statement
there so that it uses our new base controller.

.. note::

    Remember, this works because ``DefaultController`` and the new ``Controller``
    class are in the same namespace. Without a ``use`` statement, PHP assumes
    that ``Controller`` is in the same namespace, which in this case, it is!

It's like you read my mind! Now is a prefect time to re-run the test suite
to make sure we haven't broken anything. Of course, we don't have any tests
for the event creation process yet, but it's better than nothing. Before you
run the test, make sure you update your test database for the schema changes:

.. code-block:: bash

    php app/console doctrine:schema:update --force --env=test

    phpunit -c app/

OneToMany: The Inverse Side of a Relationship
---------------------------------------------

Earlier in this section, we associated a ``User`` with an ``Event``. This
allows us to call ``$event->getOwner()`` to return the owner for that one event.
But what about the opposite direction, can we start with a ``$user`` object
and call ``getEvents()``? I hope we find out :)

Open up the play script we created in episode one to test this out. Grab the
entity manager from the container and then query for our user object::

    // play.php
    // ...
    // all our setup is done!!!!!!

    $em = $container->get('doctrine')
        ->getEntityManager()
    ;

    $user = $em
        ->getRepository('UserBundle:User')
        ->findOneBy(array('username' => 'user'))
    ;

    var_dump($user->getEvents());

Dump it out and then run the command:

.. code-block:: bash

    php play.php

It blows up!

.. highlights::

    Call to undefined method Yoda\UserBundle\Entity\User::getEvents()

This actually shouldn't surprise us. The ``User`` object is a plain PHP object
and we've never added a ``getEvents`` method to it. So how can we easily get
all of the Events for a given user?

Setting this up is easy, but can be tricky to understand. Our application
works beautifully right now and the change we're about to make is only necessary
if you need to access objects from the ``OneToMany`` side of the relationship.
In this case that means user to events.

Start by adding an ``events`` property to ``User`` and giving it the ``OneToMany``
annotation::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\OneToMany(targetEntity="Yoda\EventBundle\Entity\Event", mappedBy="owner")
     */
    protected $events;

This looks just like the ``ManyToOne`` annotation we used inside ``Event``,
except for the extra ``mappedBy`` property, which tells Doctrine which field
on Event this maps to. Now that we have the ``OneToMany``, you also need
to go to ``Event`` and add an ``inversedBy`` option pointing back to the ``events``
property on ``User``::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\ManyToOne(
     *      targetEntity="Yoda\UserBundle\Entity\User",
     *      cascade={"remove"},
     *      inversedBy="events"
     * )
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $owner;

Back in ``User``, find the constructor and set the ``events`` property to a
special ``ArrayCollection`` object::

    // src/Yoda/UserBundle/Entity/User.php
    // ...
    use Doctrine\Common\Collections\ArrayCollection;

    public function __construct()
    {
        // ...
        $this->events = new ArrayCollection();
    }

The ``events`` property *should* just be an array of ``Event`` objects. But
due to some shortcomings in PHP's native array, Doctrine requires us to use
the ``ArrayCollection`` object. This object looks and feels just like an
array, so just think of it like an array. Complete things by adding the getter
and setter for the new property.

Try the play script again:

.. code-block:: bash

    php play.php

It works! Doctrine automatically queries for the two event objects owned
by this user and puts them on the ``events`` property. Notice that we didn't
have to make any database changes for this to work. That's because adding
this side of the relationship is purely for convenience. Our database already
has all the information it needs to link Users and ``Events``. The ``OneToMany``
side of a relationship is always optional, and called the inverse side. Add
it when you need it.

Caution: Don't "set" the Inverse Side
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The inverse side is special for another reason. If we called ``setEvents()`` on
a ``User`` and saved, the new events would be ignored. Only the main, or "owning"
side of the relationship is used when saving. In this example, this means that
you should always call ``setOwner`` on an Event to establish the relationship.

.. code-block:: php

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    // this works
    $entity->setOwner($this->getUser());

    // this does nothing
    // if we *only* had this part, the relationship would not save
    $events = $this->getUser()->getEvents();
    $events[] = $entity;
    $this->getUser()->setEvents($events);

The problem of not being able to set the relationship from both sides can
be particularly tricky when working a form that embeds many sub-forms. If
you run into this, check out the `cookbook entry on the topic at symfony.com`_.
Fortunately, Symfony 2.1 has a few new tricks to make this process easier.
Also check out the reference manual for the `collection form type`_.

.. _`fixture setup`: http://bit.ly/d2-fixtures-sharing
.. _`working with associations`: http://docs.doctrine-project.org/en/latest/reference/working-with-associations.html
.. _`ACLs`: http://symfony.com/doc/current/cookbook/security/acl.html
.. _`app.user global variable`: http://symfony.com/doc/current/reference/twig_reference.html#global-variables
.. _`cookbook entry on the topic at symfony.com`: http://symfony.com/doc/current/cookbook/form/form_collections.html
.. _`collection form type`: http://symfony.com/doc/current/reference/forms/types/collection.html
