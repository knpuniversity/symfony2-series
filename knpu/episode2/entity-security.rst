Entity Security
===============

Repeat after me, "We're really great." And our security system is almost 
as cool as we are. So let's keep up the pace and load users from the database 
instead of the little list in ``security.yml``.

What we're about to do is similar to what the awesome open source `FOSUserBundle`_
gives you. We're going to build this all ourselves so that we *really* understand
how things work. Later, if you *do* use `FOSUserBundle`_, you'll be a lot
more dangerous with it.

Generating the User Entity
--------------------------

Ok, forget about security! Seriously! Just think about the fact that we want
to store some user information in the database. To do this, we'll need a
``User`` entity class.

That sounds like a lot of work, so let's just use the ``doctrine:generate:entity``
``app/console`` command:

.. code-block:: bash

    php app/console doctrine:generate:entity

For entity shortcut name, use ``UserBundle:User``. Remember, Doctrine uses
this shortcut syntax for entities.

Give the class just 2 fields:

* ``username`` as a string
* ``password`` as a string

And of course, choose "yes" to generating the :ref:`repository class<symfony-ep2-repository-intro>`.
I'll explain why these are so fabulous in a second.

Once the robots are done writing the code for us, we should have a new ``User``
class in the ``Entity`` directory of ``UserBundle``. Let's change the table
name to be ``yoda_user``::

    // src/Yoda/UserBundle/Entity/User.php
    namespace Yoda\UserBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;

    /**
     * @ORM\Table(name="yoda_user")
     * @ORM\Entity(repositoryClass="Yoda\UserBundle\Entity\UserRepository")
     */
    class User
    {
        // ... the generated properties and getter/setter functions
    }

Implementing UserInterface
--------------------------

Right now, this is just a plain, regular Doctrine entity that has nothing
to do with security. But, our goal is to load users from this table on login.
The first step is to make your class implement a ``UserInterface``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...
    
    use Symfony\Component\Security\Core\User\UserInterface;

    class User implements UserInterface
    {
        // ...
    }

This interface requires us to have 5 methods and hey! We already have 2 of
them: ``getUsername()`` and ``getPassword()``::

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

Cool! So let's add the other 3.

First, ``getRoles()`` returns an array of roles that the user should get.
For now, we'll hardcode a single role, ``ROLE_USER``::

    // src/Yoda/UserBundle/Entity/User.php
    // ...

    public function getRoles()
    {
        return array('ROLE_USER');
    }

Second, add ``eraseCredentials``. Keep this method blank for now. We will
add some logic to this later::

    public function eraseCredentials()
    {
        // blank for now
    }

Finally, add ``getSalt()`` and just make it return ``null``::

    public function getSalt()
    {
        return null;
    }

I'll talk more about this method in a second.

Now that the ``User`` class implements ``UserInterface``, Symfony's authentication
system will be able to use it. But before we hook that up, let's add the
``yoda_user`` table to the database by running the ``doctrine:schema:update``
command:

.. code-block:: bash

    php app/console doctrine:schema:update --force

Loading Users from Doctrine: security.yml
-----------------------------------------

And for the grand finale, let's tell the security system to use our entity
class!

In ``security.yml``, replace the encoder entry with *our* user class and
set its value to ``bcrypt``:

.. code-block:: yaml

    # app/config/security.yml
    security:
        encoders:
            Yoda\UserBundle\Entity\User: bcrypt
        # ...

This tells Symfony that the ``password`` field on our ``User`` will be encoded
using the `bcrypt`_ algorithm.

Installing password_compat
~~~~~~~~~~~~~~~~~~~~~~~~~~

The one catch is that bcrypt isn't supported until PHP 5.5. So if you're
using PHP 5.4 or lower, you'll need to install an extra library via Composer.
No problem! Head to your terminal and use the composer ``require`` command
and pass it ``ircmaxell/password-compat``:

.. code-block:: bash

    php composer.phar require ircmaxell/password-compat

When it asks, use the ``~1.0.3`` version. By the way, this ``require`` command
is just a shortcut that updates our ``composer.json`` *for* us and then runs
the Composer ``update``:

.. code-block:: json

    "require": {
        "...": "..."
        "ircmaxell/password-compat": "~1.0.3"
    },

Using the entity Provider
~~~~~~~~~~~~~~~~~~~~~~~~~

Now for the Jedi magic! In ``security.yml``, remove the single ``providers`` entry
and replace it with a new one:

.. _symfony-ep2-providers-config:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...

        providers:
            our_database_users:
                entity: { class: UserBundle:User, property: username }

I'm just inventing the ``our_database_users`` part, that can be anything.
But the ``entity`` key is a special built-in provider that knows how to load
users via a Doctrine entity.

Yea, and that's really it! Ok, let's try it.

When you refresh, you *may* get an error:

.. code-block:: text

    There is no user provider for user "Symfony\Component\Security\Core\User\User".

Don't panic, this is just because we're still logged in as one of the hard-coded
users... even though we just deleted them from ``security.yml``. It's a one-time
error - just refresh and it'll go away.

Creating and Saving Users
-------------------------

.. _`FOSUserBundle`: https://github.com/FriendsOfSymfony/FOSUserBundle
.. _`bcrypt`: http://docs.php.net/manual/en/function.password-hash.php
