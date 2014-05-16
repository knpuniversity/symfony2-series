Repository Security
===================

Let's enhance things a bit more by giving our users an email. When we're done,
I want to allow a user to login using either their username or email.

Giving the User an Email
------------------------

Let's start like we always do, by adding the actual property to the User class
with the Doctrine annotations::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $email;

Like before, generate or write a getter and a setter for the new property.
As a reminder, I'll use the ``doctrine:generate:entities`` command to do
this:

.. code-block:: bash

    php app/console doctrine:generate:entities UserBundle

Remember that this command creates a backup of your entity, named with a
tilde. Delete it after generation:

.. code-block:: bash

    rm src/Yoda/UserBundle/Entity/User.php~

.. tip::

    Avoid the backup file by passing a ``--no-backup`` option.

Next update the database schema to add the new field:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Finally, update the fixtures so that each user has an email::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        $user->setEmail('user@user.com');

        // ...
        $admin->setEmail('admin@admin.com');

        // ...
    }

Reload everything to refresh the database.

Doctrine Repositories
---------------------

Right now, when a user logs in, the security system queries for him on
the ``username`` field. That makes sense because we
:ref:`configured it that way in security.yml<symfony-ep2-providers-config>`.
We can change it here to use the ``email`` field instead, but not the ``email``
*or* the ``username``. Making this more flexible is easy, but first, we need
to learn about Doctrine repositories.

.. _symfony-ep2-repository-intro:

Find and open ``UserRepository``::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    namespace Yoda\UserBundle\Entity;

    use Doctrine\ORM\EntityRepository;

    class UserRepository extends EntityRepository
    {
    }

.. note::

    This class was generated for you when we originally generated the entity.

This is a Doctrine repository. Each entity, like ``Event`` or ``User``, has
an associated repository. This is where you could create methods like ``findActiveUsers``,
which might query the database for users that have a value of ``1`` for the
``isActive`` field.

If this sounds abstract, don't worry! We're actually already using the repositories
in this project. Open up ``EventController`` and check out the ``indexAction``
method::

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

To get all of the event objects, we call ``getRepository`` on the entity manager.
The ``getRepository`` method actually returns an instance of our very own
``EventRepository``. But when we open up that class, it's empty::

    // src/Yoda/EventBundle/Entity/EventRepository.php
    namespace Yoda\EventBundle\Entity;

    use Doctrine\ORM\EntityRepository;

    class EventRepository extends EntityRepository
    {
        // nothing here... boring!
    }

So where is the ``findAll`` method coming from? The answer is Doctrine's
base `EntityRepository`_, which we're extending. If we `open it`_, you'll
see a bunch of helpful methods that we talked about in the previous screencast,
including ``findAll()``. This is really cool because it means that every repository
has a few helpful methods to begin with.

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

Sure enough, when we go to the events page, we can see that our code is
being triggered.

Open up the Event entity. Above the class, you'll see an ``@ORM\Entity``
annotation::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\Entity(repositoryClass="Yoda\EventBundle\Entity\EventRepository")
     */
    class Event

This does two things. First, it tells Doctrine that it should manage this
class and save it to the database. Second, the ``repositoryClass`` tells
Doctrine that we have our own custom repository. Let's remove that part and
see what happens::

    // src/Yoda/EventBundle/Entity/Event.php
    // ...

    /**
     * @ORM\Entity()
     */
    class Event

When we refresh, there's no error. In fact, everything works perfectly! Since
we haven't told Doctrine about our custom repository, it just uses the base
``EntityRepository`` itself. Our overridden ``findAll`` method is bypassed
and the real one is used.

Let's review. Every entity in your project, such as ``Event`` or ``User``,
has its own repository with helpful methods for returning objects of that type.
You can choose to have your own repository or just use Doctrine's default.

.. note::

    Remove the ``die`` statement before moving on to unbreak things!

Doctrine's QueryBuilder
-----------------------

Forgetting about security for a minute, let's add a new method to the ``UserRepository``
called ``findOneByUsernameOrEmail``::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    class UserRepository extends EntityRepository
    {
        public function findOneByUsernameOrEmail()
        {
            // ... todo - get your query on
        }
    }

In this method, we'll start to see Doctrine's query builder: an object-oriented
way to build queries. To create this object, call ``createQueryBuilder``
and pass it an "alias" for this object. Add the where clause with our ``OR``
logic::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    public function findOneByUsernameOrEmail($username)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

This should look a bit like standard SQL, except that we use "placeholders"
for the two variable values. To fill each of these in, call ``setParameter``.
The reason this is separated into two steps is to avoid `SQL injection attacks`_.
To finish the query, call ``getQuery`` and then ``getOneOrNullResult``, which,
as the name sounds, will return the ``User`` object if its found or null otherwise.

.. note::

    To learn more about the Query Builder, see `doctrine-project.org: The QueryBuilder`.

Just to see if this is working, let's try it! For simplicity, just reuse the
EventController's ``indexAction``. To get the ``UserRepository``, call ``getRepository``
on the entity manager. Remember, the argument you pass to ``getRepository``
is the entity's "shortcut name" or "alias", which is a bundle name followed
by the entity name::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        // temporarily abuse this controller to see if this all works
        $userRepo = $em->getRepository('UserBundle:User');

        // ...
    }

Now that we have the UserRepository, let's try our new method and dump the result::

    public function indexAction()
    {
        // ...
        $userRepo = $em->getRepository('UserBundle:User');
        var_dump($userRepo->findOneByUsernameOrEmail('user'));die;

        // ...
    }

When we refresh, we can see the user. If we try the email instead, we get
the same result::

    var_dump($userRepo->findOneByUsernameOrEmail('user@user.com'));die;

Great, our new method works!

.. note::

    Undo all the changes to ``indexAction`` before moving on.

This is a really common pattern which we'll see more and more of. Use the
repository object in a controller to fetch objects from the database. If
you need a special query, just add a new method to your repository
and use it here.

The UserProvider: Custom Logic to Load Security Users
-----------------------------------------------------

Now that we know a lot more about repositories, let's get back to security!
Remember that our goal is to let the user login using a username *or* email.
If we could get the security system to use our new ``findOneByUsernameOrEmail``
method when a user logs in, then we'd be done.

First, open up ``security.yml`` and remove the ``property`` key from our entity
provider:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...

        providers:
            our_database_users:
                entity: { class: UserBundle:User }

Without this, Doctrine doesn't know how to query for our User object when a
user logs in. To see the error this creates, let's try to login.

>
The Doctrine repository "Yoda\UserBundle\Entity\UserRepository" must implement UserProviderInterface.

The error says that our ``UserRepository`` class must implement
:symfonyclass:`Symfony\\Component\\Security\\Core\\User\\UserProviderInterface`.
Wait what? Behind the scenes, Symfony wants to call a method on our ``UserRepository``
to load the User. But in order for this to work, our repository has to implement
a special interface.

Open up ``UserRepository`` and make it implement Symfony's
:symfonyclass:`Symfony\\Component\\Security\\Core\\User\\UserProviderInterface`::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    use Symfony\Component\Security\Core\User\UserProviderInterface;

    class UserRepository extends EntityRepository implements UserProviderInterface
    {
        // ...
    }

As always, don't forget your `use` statement! This interface tells Symfony's
security system more information about how it should load users. It requires
three methods, two of which are kinda boring. I'll just paste those in.
The really important method is `loadUserByUsername`::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    use Symfony\Component\Security\Core\User\UserProviderInterface;

    use Symfony\Component\Security\Core\User\UserInterface;
    use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

    class UserRepository extends EntityRepository implements UserProviderInterface
    {
        // ...

        public function loadUserByUsername($username)
        {
            // todo
        }

        public function refreshUser(UserInterface $user)
        {
            $class = get_class($user);
            if (!$this->supportsClass($class)) {
                throw new UnsupportedUserException(sprintf(
                    'Instances of "%s" are not supported.',
                    $class
                ));
            }

            return $this->find($user->getId());
        }

        public function supportsClass($class)
        {
            return $this->getEntityName() === $class
                || is_subclass_of($class, $this->getEntityName());
        }
    }

On login Symfony will call this method to get the User object. This is awesome,
because we can use any logic we want in order to load a user for the submitted
username. Let's just reuse the ``findOneByUsernameOrEmail`` method we created
earlier. If no user is found, this method should throw a special `UsernameNotFoundException`::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    use Symfony\Component\Security\Core\User\UserProviderInterface;

    // add 3 more "use" statements
    use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
    use Symfony\Component\Security\Core\User\UserInterface;
    use Symfony\Component\Security\Core\Exception\UnsupportedUserException;

    class UserRepository extends EntityRepository implements UserProviderInterface
    {
        // ...

        public function loadUserByUsername($username)
        {
            $user = $this->findOneByUsernameOrEmail($username);

            if (!$user) {
                throw new UsernameNotFoundException('No user found for username '.$username);
            }

            return $user;
        }

        // ... refreshUser and supportsClass from above...
    }

Don't forget to add the ``use`` statement for this class and a few others
I pasted in.

Let's try logging in again, this time using our email address. It works!
Behind the scenes, Symfony calls the ``loadUserByUsername`` method and passes
in the username we submitted. We return the right ``User`` object and then
the authentication process continues. One nice detail is that we don't have
to worry about checking the password because Symfony still does this for us.

Ok, enough about security and Doctrine! If you're still with us, we've just
learned some of the most powerful, but difficult stuff when using Symfony
and Doctrine. You now have an elegant form login system that loads users from
the database and which gives you a lot of control over exactly how those users
are loaded. Now, we'll start making our application more interesting with
a registration page.

.. _`SQL injection attacks`: http://xkcd.com/327/
.. _`doctrine-project.org: The QueryBuilder`: http://bit.ly/d2-query-builder
.. _`EntityRepository`: http://www.doctrine-project.org/api/orm/2.3/class-Doctrine.ORM.EntityRepository.html
.. _`open it`: http://www.doctrine-project.org/api/orm/2.3/source-class-Doctrine.ORM.EntityRepository.html#25-244