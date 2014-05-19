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
