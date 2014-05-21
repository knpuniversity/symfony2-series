Controlling Data / Fixtures in a Test
=====================================

If we try the full test, it works! But when we try it again, it fails. If we
did a little debugging we'd see that it fails the second time because the
username and email in the test are already taken. This is a *very* important
thing to note about testing. If we're using a database, we need to make sure 
it's in a predicable state before running the test.

To fix this, let's delete all of our users before running the test. Start
by grabbing the container object, which is stored statically on the parent
test class. Next, grab the Doctrine entity manager by getting the ``doctrine``
service and calling ``getManager``. If you're not totally comfortable with
how I just used the container, don't worry. I'll give you some more detail
on this in a moment. Once we have the entity manager we can get the ``UserRepository``.
Build a query from the repository that deletes all of the users::

    public function testRegister()
    {
        $client = static::createClient();

        $container = self::$kernel->getContainer();
        $em = $container->get('doctrine');
        $userRepo = $em->getRepository('UserBundle:User');
        $userRepo->createQueryBuilder('u')
            ->delete()
            ->getQuery()
            ->execute()
        ;

        // ... the actual test
    }

While we're here, let's also verify that the ``User`` object was successfully
created at the end of the test. By querying for the object, we can just test
to make sure that it's not null and that a few properties have normal values::

    public function testRegister()
    {
        // ...

        // check some basic data about our user in the database
        $user = $userRepo->findOneBy(array(
            'username' => 'user5',
        ));

        $this->assertNotNull($user);
        $this->assertNotNull($user->getPassword());
        $this->assertEquals('user5@user.com', $user->getEmail());
    }

Re-run the test again to see that everything passes. Now this is a good-looking
test. Notice that we're not testing for every possible detail of our form,
that would be a lot of work and maintenance. Instead, we just want to see
that the form can be submitted both successfully and unsuccessfully. The
important thing is that if we break something major later, our test will
let us know.

Separating the dev and test Databases
-------------------------------------

One unfortunate thing about running the tests is that it affects the database
that we're using for development. Instead of using the same database, let's
use two different ones: one for development and one for testing. Open
up the main ``config.yml`` file and find the doctrine database configuration.
Copy and paste it into the ``config_test.yml`` file. Finally, remove
everything except for the ``dbname`` and add an ``_test`` to it:

.. code-block:: yaml

    # app/config/config_test.yml
    # ...

    doctrine:
        dbal:
            dbname:   %database_name%_test

.. tip::

    The ``database_name`` is a parameter, which lives in ``app/config/parameters.yml``.

This little trick takes advantage of how Symfony environments work. The ``test``
environment uses all of the normal Doctrine configuration, except that it overrides
this one parameter.

Don't forget to setup the new database by running ``doctrine:database:create``.
Pass an extra ``--env=test`` option so that the command runs in the ``test``
environment. Use the same idea to insert the schema:

.. code-block:: bash

    php app/console doctrine:database:create --env=test
    php app/console doctrine:schema:create --env=test

.. tip::

    By default, all commands run in the ``dev`` environment.

You can now re-run the tests knowing that our main database isn't being affected:

.. code-block:: bash

    phpunit -c app
