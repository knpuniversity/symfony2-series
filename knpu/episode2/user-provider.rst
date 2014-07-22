The UserProvider: Custom Logic to Load Security Users
=====================================================

Hey there repository expert. So our *actual* goal was to let the user login
using a username *or* email. If we could get the security system to use our
shiny new ``findOneByUsernameOrEmail`` method to look up users at login, we'd
be done. And back to our real job of crushing the rebel forces.

Open up ``security.yml`` and remove the ``property`` key from our entity
provider:

.. code-block:: yaml

    # app/config/security.yml
    security:
        # ...

        providers:
            our_database_users:
                entity: { class: UserBundle:User }

Try logging in now! Ah, a great error:

>
The Doctrine repository "Yoda\UserBundle\Entity\UserRepository" must implement UserProviderInterface.

The UserProviderInterface
-------------------------

Without the property, Doctrine has no idea how to look up the User. Instead
it tries to call a method on our ``UserRepository``. But for that to work,
our ``UserRepository`` class must implement
:symfonyclass:`Symfony\\Component\\Security\\Core\\User\\UserProviderInterface`.

So let's open up ``UserRepository`` and make this happen::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    use Symfony\Component\Security\Core\User\UserProviderInterface;

    class UserRepository extends EntityRepository implements UserProviderInterface
    {
        // ...
    }

As always, don't forget your ``use`` statement! This interface requires 3
methods: ``refreshUser``, ``supportsClass`` and ``loadUserByUsername``. I'll
just paste these in::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

    use Symfony\Component\Security\Core\User\UserProviderInterface;
    use Symfony\Component\Security\Core\User\UserInterface;
    use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
    use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;

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

            if (!$refreshedUser = $this->find($user->getId())) {
                throw new UsernameNotFoundException(sprintf('User with id %s not found', json_encode($user->getId())));
            }

            return $refreshedUser;
        }

        public function supportsClass($class)
        {
            return $this->getEntityName() === $class
                || is_subclass_of($class, $this->getEntityName());
        }
    }

.. tip::

    You can get this code from the ``resources`` directory of the code download.

Filling in loadUserByUsername
-----------------------------

The really important method is ``loadUserByUsername`` because Symfony calls
it when you login to get the ``User`` object for the given username. So we
can use any logic we want to find or not find a user, like never returning
User's named "Jar Jar Binks"::

    public function loadUserByUsername($username)
    {
        if ($username == 'jarjarbinks') {
            // nope!
            return;
        }
    }

We can just re-use the ``findOneByUsernameOrEmail`` method we created earlier.
If no user is found, this method should throw a special ``UsernameNotFoundException``::

    // src/Yoda/UserBundle/Entity/UserRepository.php
    // ...

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

Try logging in again using the email address. It works! Behind the scenes,
Symfony calls the ``loadUserByUsername`` method and passes in the username
we submitted. We return the right ``User`` object and then the authentication
just keeps going like normal. We don't have to worry about checking the password
because Symfony still does that for us.

Ok, enough about security and Doctrine! But give yourself a high-five because
you just learned some of the most powerful, but difficult stuff when using
Symfony and Doctrine. You now have an elegant form login system that loads
users from the database and that gives you a lot of control over exactly
how those users are loaded. 

Now for a registration page!
