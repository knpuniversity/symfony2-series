Entity Security
===============

Now that we have a working security system, we just need to go one step
further and start loading users from the database instead of from a list
in ``security.yml``.

Generating the User Entity
--------------------------

To start, let's create a User entity that will store users in the database.
Don't think about security yet, just imagine that we have some need to store
users. Use the ``doctrine:generate:entity`` to generate the User entity
into the new ``UserBundle``.

.. code-block:: bash

    php app/console doctrine:generate:entity

.. tip::

    For entity shortcut name, use ``UserBundle:User``. Also, choose "yes"
    to generating the :ref:`repository class<symfony-ep2-repository-intro>`.

For now, give it 3 fields:

* username: string
* password: string
* salt: string

After it generates, find the new class and give it a specific table name::

    // src/Yoda/UserBundle/Entity/User.php
    namespace Yoda\UserBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;

    /**
     * @ORM\Table(name="yoda_user")
     * @ORM\Entity(repositoryClass="Yoda\UserBundle\Entity\UserRepository")
     */
    class User
    {
        // private fields for id, username, password and salt
        // along with the getter and setter methods
    }

Implementing UserInterface
--------------------------

At this point, this is just a regular Doctrine entity that has nothing to
do with security. The goal is for users to be loaded from this table on login.
The first step is to make your class implement a ``UserInterface``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...
    
    use Symfony\Component\Security\Core\User\UserInterface;

    class User implements UserInterface
    {
        // ...
    }

This interface requires us to have 5 methods. We already have three: ``getUsername()``,
``getPassword()`` and ``getSalt()``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function getUsername()
    {
        return $this->username;
    }

    public function getPassword()
    {
        return $this->password;
    }

    public function getSalt()
    {
        return $this->salt;
    }

Let's add the other 2. First, ``getRoles()`` returns an array of roles that
this user should get. For now, we'll hardcode a single role, ``ROLE_USER``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function getRoles()
    {
        return array('ROLE_USER');
    }

Finally, add ``eraseCredentials``. This method can stay blank for now, we'll
add some logic to this later::

    public function eraseCredentials()
    {
        // blank for now
    }

.. note::

    For more details about the purpose behind each of these methods, see
    :symfonyclass:`Symfony\\Component\\Security\\Core\\User\\UserInterface`.

Let's add one more detail to the class. The ``salt`` is a string that's unique
to each user and used to help encode their password. To make sure it's generated,
add a constructor to the User object and initialize the value. I'll copy
in a fancy line of code that's copied from the open source `FOSUserBundle`_.

Now that the User class implements ``UserInterface``, it can be used and
understood by the authentication system. But before we hook it up, make sure
to update your database by running the ``doctrine:schema:update`` task:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Loading Users from Doctrine: security.yml
-----------------------------------------

At this point, we have a working User entity but we haven't told Symfony's
security system to use it. In ``security.yml``, replace the encoder entry
with *our* user class and set its value to ``sha512``:

.. code-block:: yaml

    # app/config/security.yml
    security:
        encoders:
            Yoda\UserBundle\Entity\User: sha512

This tells Symfony that the ``password`` field on our User will be encoded
using sha512. Next, add the magic that makes everything happen. Remove the
single "providers" entry and replace it with a new one:

.. _symfony-ep2-providers-config:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...

        providers:
            our_database_users:
                entity: { class: UserBundle:User, property: username }

A "provider" is like a pool of users and we can use the built-in "entity" type
to pull from the user table.

.. tip::

    The ``our_database_users`` key is just a name and can be anything.

And that's it! Let's try it. When you refresh, you *may* get an error. This
is because we were logged in with one of the hard-coded users, which don't
exist anymore. This is a one-time error and the fix is just to clear out
your session data.

Creating and Saving Users
-------------------------

Now that the error is gone, try logging in! The system appears to work, but
we can't actually log in. That's because we don't have any users in the database
yet! To fix this, copy the event fixtures class (``LoadEvents.php``) into
the ``UserBundle``, rename it, and update the namespaces::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    namespace Yoda\UserBundle\DataFixtures\ORM;

    use Doctrine\Common\DataFixtures\FixtureInterface;
    use Doctrine\Common\Persistence\ObjectManager;
    use Yoda\UserBundle\Entity\User;

    class LoadUsers implements FixtureInterface
    {
        public function load(ObjectManager $manager)
        {
            // todo
        }
    }

For the most part, adding users is pretty straightforward. The tricky part
is the password, which must be combined with the value of our ``salt`` and
encoded before being stored to the database::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('user');
        // todo - fill in this encoded password... ya know... somehow...
        $user->setPassword('');
        $manager->persist($user);

        // the queries aren't done until now
        $manager->flush();
    }

Fortunately, Symfony gives us an object that can do this for us. To get the
object, first implement :symfonyclass:`Symfony\\Component\\DependencyInjection\\ContainerAwareInterface`::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...
    
    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    
    class LoadUsers implements FixtureInterface, ContainerAwareInterface
    {
        // ...
    }

This requires one new method - ``setContainer`` - which we use to store the container
as a property::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    use Symfony\Component\DependencyInjection\ContainerAwareInterface;
    use Symfony\Component\DependencyInjection\ContainerInterface;

    class LoadUsers implements FixtureInterface, ContainerAwareInterface
    {
        private $container;

        // ...

        public function setContainer(ContainerInterface $container = null)
        {
            $this->container = $container;
        }
    }

Because we implement this interface, Symfony will call this method and pass
us the container object before calling ``load``. Remember that the container
is the `array-like object that holds all the useful objects in the system`_.
We can see a list of those object by running the ``container:debug`` console
task:

.. code-block:: bash

    php app/console container:debug

Let's create a helper function to do the encoding. This step may look strange,
but stay with me. Because of the ``encoders`` key in ``security.yml``,
we can ask Symfony for a special "encoder" object that's pre-configured for our 
User object. After we grab the encoder, we can call ``encodePassword()``
to do the work::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    private function encodePassword($user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user)
        ;

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }

Behind the scenes, it combines the plain-text password and the random salt
value and then encodes the result multiple times. The result is an unrecognizable
string that's stored on the ``password`` property::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('user');
        $user->setPassword($this->encodePassword($user, 'user'));
        $manager->persist($user);

        // the queries aren't done until now
        $manager->flush();
    }

Add a second admin user, which we'll give special access in a moment::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        
        $admin = new User();
        $admin->setUsername('admin');
        $admin->setPassword($this->encodePassword($admin, 'admin'));
        $manager->persist($admin);

        // the queries aren't done until now
        $manager->flush();
    }

Reload the fixtures from the command line:

.. code-block:: bash

    php app/console doctrine:fixtures:load

Let's use the query console task to look at what each user looks like:

.. code-block:: bash

    php app/console doctrine:query:sql "SELECT * FROM yoda_user"

.. code-block:: text

    array(
        0 => array(
            'id' => string '1',
            'username' => string 'user',
            'password' => string '15zoihb9sPYPgk6SMQ+JZ9x4poQiQMxBXlTUoNIwk4F=ABg+RmOzml8G9MRW0q9TEZTipgE4pGJI+0aGiOz08g=='
            'salt' => string 'elas694q83wookwskgcgw4scw8ksgos'
        )
        // ...
    )

As expected, each has a random salt and an encoded password. Back at the browser,
we can now login. To make this work, several things just happened in the background:

1. A User entity was loaded from the database for the given username

2. The plain-text password we entered is encoded using the same algorithm
   from when we created the user.

3. This encoded version of the password is compared with the User's password property. 
   If they match, then logging in is a success!

Adding Dynamic Roles to each User
---------------------------------

Now that this is all working, let's add more flexibility. Right now, every
user has the same single role: ``ROLE_USER``. Add a ``roles`` field as an
``array`` type::

    // src/Yoda/UserBundle/Entity/User.php
    // ...
    
    /**
     * @ORM\Column(type="array")
     */
    private $roles = array();

This type allows us to store an array of strings which are serialized into a 
single column. Update the ``getRoles`` method for the change and create a
``setRoles`` method::

    public function getRoles()
    {
        return $this->roles;
    }

    public function setRoles(array $roles)
    {
        $this->roles = $roles;

        // allows for chaining
        return $this;
    }

The way its written now, a user could actually have zero roles. 
Be careful to not let this happen: the user system won't act right if a valid
user has zero roles. So, to prevent this, add some logic to the ``getRoles``
method that guarantees all users have ``ROLE_USER``::

    public function getRoles()
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

.. tip::

    Never allow a valid user to have zero roles, or they will become the
    undead and cause a zombie uprising. You've been warned.

Update the SQL for the new field and then head back to the fixture file:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Give ``admin`` the ``ROLE_ADMIN`` role and then reload the fixtures::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        $admin->setRoles(array('ROLE_ADMIN'));
        // ...
    }

.. code-block:: bash

    php app/console doctrine:fixtures:load

Now, when we login as admin, we can see that we have two roles.

Using the AdvancedUserInterface for inactive Users
--------------------------------------------------

Next, let's add an ``isActive`` boolean field to User. If this field is false,
it will prevent that user from authenticating. Don't forget to add the getter
and setter methods either by using a tool in your IDE or by re-running the
``doctrine:generate:entities`` command::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $isActive = true;
    
    // ...
    // write or generate your getIsActive and setIsActive methods...

After that, update our schema to add the new field:

.. code-block:: bash

    php app/console doctrine:schema:update --force

So far, the ``isActive`` field exists, but isn't actually used during login.
To make this work, we'll modify our ``User`` class, replacing ``UserInterface``
with :symfonyclass:`Symfony\\Component\\Security\\Core\\User\\AdvancedUserInterface`::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    use Symfony\Component\Security\Core\User\AdvancedUserInterface;

    class User implements AdvancedUserInterface
    {
        // ...
    }

.. tip::

    For the OO geeks, ``AdvancedUserInterface extends UserInterface``.

The new interface is a stronger version of ``UserInterface`` that requires
four additional methods. I'll use my IDE to generate these. If *any* of these
methods return false, Symfony will block the user from logging in. To prove
this, let's make them all return true except for ``isAccountNonLocked``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return false;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return true;
    }

Now, when we try to login, we're automatically blocked with a helpful message.
By default, each of these methods does the same thing: they block login and
give the user a message. Set each to return true, except for ``isEnabled``,
which will return the value for our ``isActive`` property::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->getIsActive();
    }

If ``isActive`` is ``false``, this should prevent the user from logging in.

Head over to our user fixtures so we can try this. Set the admin user to
inactive::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        $admin->setIsActive(false);
        // ...
    }

Next, reload your fixtures. When we try to login, we're automatically
blocked. Perfect!

.. tip::

    To edit this message, use the :ref:`translation trick<symfony-ep2-login-error-translation>`
    we showed earlier.

.. note::

    Remove the ``setIsActive`` call before moving on - we added it just as
    an example.

User Serialization
------------------

We need to do one more little piece of homework before we're done. When a
user logs in, the ``User`` entity is stored in the user's session. In order
for this to work, PHP serializes the User object to a string at the end of
the request and stores it. At the beginning of the request, that string is
unserialized and turned back into the User object. This process is native 
to how PHP saves session data between requests.

This is all fine, except for a "gotcha" in Doctrine where, under certain
scenarios, Doctrine will stick some extra information into your entity, like
the entity manager itself. Normally, you don't care about this, but when
the User object is serialized, it fails. The entity manager contains a database
connection and other information that just can't be serialized. This is a
subtle shortcoming in Doctrine, but fortunately the fix is easy.

Start by adding the :phpclass:`Serializable` interface to the User class.
This is a core PHP interface that has two methods: ``serialize`` and ``unserialize``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    use Serializable;

    class User implements AdvancedUserInterface, Serializable
    {
        // ...

        public function serialize()
        {
            // todo - do some mad serialization
        }

        public function unserialize($serialized)
        {
            // todo - and some equally angry de-serialization
        }
    }

When the ``User`` object is serialized, it'll call the ``serialize`` method instead
of trying to do it automatically. When the string is unserialized, the ``unserialize``
method is called. This may seem odd, but let's just return the ``id`` inside
an array for ``serialize``. For ``unserialize``, just put that ``id`` value back on
the object::

    // src/Yoda/UserBundle/Entity/User.php
    // ..

    public function serialize()
    {
        return serialize(array(
            'id' => $this->getId(),
        ));
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        $this->id = $data['id'];
    }

In theory, this should kinda break things. Specifically, when Symfony grabs
the ``User`` object from the session, it will have lost all of its data except
for the ``id``. You can imagine how annoying it would be if you asked for
the ``User`` object for the current user and it was missing all of its data!

Fortunately, life is so much better than that! The security system is smart enough to
take that ``id`` and query for a full, fresh copy of the User object. We
can see this in the web debug toolbar: once a user is logged in, each request
has a query that grabs the current user from the database. The moral of the
story is this, we need to do this serialization trick to avoid some future
problems. But once we add it, everything works perfectly.

.. _`FOSUserBundle`: https://github.com/FriendsOfSymfony/FOSUserBundle/blob/20c2531805c40153112ecfdc65cddaf4a0f90f18/Model/User.php#L127
.. _`array-like object that holds all the useful objects in the system`: http://knpuniversity.com/screencast/symfony2-ep1/controller#symfony-ep1-what-is-a-service
