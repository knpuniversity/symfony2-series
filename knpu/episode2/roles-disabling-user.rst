Adding Dynamic Roles to each User
---------------------------------

Right now, all users get just one role: ``ROLE_USER``, because it's what
we're returning from the ``getRoles()`` function inside the ``User`` entity.

Add a ``roles`` field and make it a ``json_array`` type::

    // src/Yoda/UserBundle/Entity/User.php
    // ...
    
    /**
     * @ORM\Column(type="json_array")
     */
    private $roles = array();

``json_array`` allows us to store an array of strings in one field. In the
database, these are stored as a JSON string. Doctrine takes care of converting
back and forth between the array and JSON.

Now, just update the ``getRoles()`` method to use this property and add a
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

Cool, except the way it's written now, a user could actually have *zero* roles. 
Don't let this happen: that user will become the undead and cause a zombie
uprising. They can login, but they won't actually be authenticated. You've
been warned.

Be a hero by adding some logic to ``getRoles()``. Let's just guarantee that
*every* user has ``ROLE_USER``::

    public function getRoles()
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }


Update the SQL for the new field and then head back to the fixture file:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Let's copy the code here and make a second, ``admin`` user. Give this powerful
Imperial user ``ROLE_ADMIN``::

    // src/Yoda/UserBundle/DataFixtures/ORM/LoadUsers.php
    // ...

    public function load(ObjectManager $manager)
    {
        // ...
        $manager->persist($user);

        $admin = new User();
        $admin->setUsername('wayne');
        $admin->setPassword($this->encodePassword($admin, 'waynepass'));
        $admin->setRoles(array('ROLE_ADMIN'));
        $manager->persist($admin);

        $manager->flush();
    }

Let's reload the fixtures!

.. code-block:: bash

    php app/console doctrine:fixtures:load

Now, when we login as admin, the web debug toolbar shows us that we have
``ROLE_USER`` *and* ``ROLE_ADMIN``.

Using the AdvancedUserInterface for Inactive Users
--------------------------------------------------

Could we disable users, like if they were spamming our site? Well, we could
just delete them and send a strongly-worded email, but yea, we can also
disable them!

Add an ``isActive`` boolean field to User. If the field is false, it will
prevent that user from authenticating. Don't forget to add the getter and
setter methods either by using a tool in your IDE or by re-running the
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

So the ``isActive`` field *exists*, but it's not actually used during login.
To make this work, change the ``User`` class to implement
:symfonyclass:`Symfony\\Component\\Security\\Core\\User\\AdvancedUserInterface`
instead of ``UserInterface``::

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

Logging in now is less fun: we're blocked with a helpful message.

Each of these methods does the exact same thing: they block login. Each will
give the user a different message, which you can :ref:`translate <symfony-ep2-login-error-translation>`
if you want. Set each to return true, except for ``isEnabled``. Let's have
it return the value for our ``isActive`` property::

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

Next, reload your fixtures:

.. code-block:: bash

    php app/console doctrine:fixtures:load

When we try to login, we're automatically blocked. Cool! Let's remove the
``setIsActive`` call we just added and reload the fixtures to put everything
back where it started.
