Controlling Data / Fixtures in a Test
=====================================

When we try running the test again, it fails! What the heck?

If we did a little debugging we'd see that it fails the second time because
the username and email in the test are already taken. So instead of success,
we see a validation error.

This is a *very* important detail about testing. Before running any test,
we need to make sure the database is in a predictable state. Our test will
pass... unless there *happens* to already be a ``user5`` in the database.

Deleting Users
--------------

To fix this, let's empty the user table before running the test. Start by
grabbing the container object, which is stored statically on the parent
test class::

    // src/Yoda/UserBundle/Tests/Controller/RegisterControllerTest.php
    // ...

    public function testRegister()
    {
        $client = static::createClient();

        $container = self::$kernel->getContainer();
        // ...
    }

Now, grab the Doctrine entity manager by getting the ``doctrine`` service
and calling ``getManager``. If you're not comfortable with what I just did,
don't worry. I'll demystify this in a moment::

    // src/Yoda/UserBundle/Tests/Controller/RegisterControllerTest.php
    // ...

    public function testRegister()
    {
        $client = static::createClient();

        $container = self::$kernel->getContainer();
        $em = $container->get('doctrine');

        // ...
    }

Once we have the entity manager we can get the ``UserRepository``. Build
a query from the repository that deletes all of the users::

    // src/Yoda/UserBundle/Tests/Controller/RegisterControllerTest.php
    // ...

    public function testRegister()
    {
        // ...
        $em = $container->get('doctrine');
        $userRepo = $em->getRepository('UserBundle:User');
        $userRepo->createQueryBuilder('u')
            ->delete()
            ->getQuery()
            ->execute()
        ;

        // ... the actual test
    }

Re-run the test again to see that everything passes. Now this is a good-looking
test!

Notice that we're not testing for every tiny detail. That would be a ton
of work and functional tests aren't meant to replace us as developers from
making sure things work when we build them.

Instead, we just want to see that the form can be submitted successfully
and unsuccessfully. The important thing is that if we break something major
later, our test will let us know.

Separating the dev and test Databases
-------------------------------------

When we run our tests, it's emptying the same user table we're using for
development! That's kind of annoying! Instead, let's use 2 different databases:
one for development and one that's used only by the tests.

Open up the main ``config.yml`` file and find the doctrine database configuration.
Copy and paste it into the ``config_test.yml`` file, but remove everything
except for the ``dbname`` option. Now, add an ``_test`` to the end of it:

.. code-block:: yaml

    # app/config/config_test.yml
    # ...

    doctrine:
        dbal:
            dbname:   %database_name%_test

.. tip::

    The ``database_name`` is a parameter, which lives in ``app/config/parameters.yml``.

This little trick takes advantage of how Symfony environments work. The ``test``
environment uses all of the normal Doctrine configuration, except that it
overrides this one option.

Don't forget to setup the new database by running ``doctrine:database:create``.
Pass an extra ``--env=test`` option so that the command runs in the ``test``
environment. Use the same idea to insert the schema:

.. code-block:: bash

    php app/console doctrine:database:create --env=test
    php app/console doctrine:schema:create --env=test

.. tip::

    By default, all ``app/console`` commands run in the ``dev`` environment.

You can now re-run the tests knowing that our main database isn't being affected:

.. code-block:: bash

    php bin/phpunit -c app

Behat
-----

As cool as this is, in reality we use a tool called Behat instead of Symfony's
built in functional testing tools. And you're in luck because everything you just
learned translates to Behat. Check out our tutorial on this to take your functional
testing into space!

Do this or risk an angry phone call from Darth Vader when the super laser doesn't fire because
you added a new espresso machine to the breakroom.
