Functional Testing
==================

We've made a lot of progress on our project, but how can we be sure that
we haven't broken anything along the way? The answer is through testing.
There are two types of tests you can write: unit tests and functional tests.
Unit tests are used to test individual classes. We'll save that topic for
another screencast. Functional tests are more like a browser that surfs to
different pages on your site and checks for specific things.

Your First Functional Test
--------------------------

When we created the ``EventBundle`` in the last screencast, it created a stub
functional test for us. Copy the test into the ``UserBundle`` and rename it
``RegisterControllerTest``::

    // src/Yoda/UserBundle/Tests/Controller/RegisterControllerTest.php
    namespace Yoda\UserBundle\Tests\Controller;

    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

    class RegisterControllerTest extends WebTestCase
    {
        public function testIndex()
        {
            $client = static::createClient();
            // ...
        }
    }

.. tip::

    It would be even better to rename this method to ``testRegister``, since
    we're testing the ``registerAction`` (but this has no technical purpose).

Functional tests are pretty simple. Use the :symfonyclass:`Symfony\\Bundle\\FrameworkBundle\\Client`
object like a browser to access different pages on your site. Let's start
small by testing that the ``/register`` page returns a 200 status code and
that the word "Register" appears::

    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($crawler->filter('html:contains("Register")')->count() > 0);
    }

The ``assertTrue`` method is provided by PHPUnit, the library that Symfony
uses for testing. PHPUnit has lot's of nice testing methods and we'll see a
few more in a moment.

Running Tests
~~~~~~~~~~~~~

To run the test, you'll need to have PHPUnit installed. If you don't have
it, follow the instructions on the `PHPUnit site`_. From the command line,
run ``phpunit`` passing it a ``-c`` option:

.. code-block:: bash

    phpunit -c app

.. tip::

    Depending on how you installed phpunit, the above command will change
    slightly. For example, if you downloaded the phar file, say ``php phpunit.phar -c app``.

This tells PHPUnit to look for a configuration file in the ``app/`` directory.
It finds and loads the ``phpunit.xml.dist`` file, which tells it how to bootstrap
Symfony as well as a few other things.

When we execute the command, we see several errors show up. If you look closely,
you'll see that it's executing our two stub tests. Delete these and try again:

.. code-block:: bash

    rm src/Yoda/EventBundle/Tests/Controller/*Test.php

Success! PHPUnit runs our test, which verifies that the `/register` page
has a 200 status code and has the word "Register".

To see what a failed test looks like, change the test and re-run it::

    $this->assertTrue($crawler->filter('html:contains("xxxx-----Register----xxxx")')->count() > 0);

In this case, it doesn't find anything matching our text. A good way to debug
is to print out the response content before the failing test::

    var_dump($client->getResponse()->getContent());
    $this->assertTrue($crawler->filter('html:contains("xxxx-----Register----xxxx")')->count() > 0);

Re-run the test again to see the content from the registration page. The ``h1``
tag contains the word "Register", so let's put that back in our test::

    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($crawler->filter('html:contains("Register")')->count() > 0);
    }

Traversing the Dom with the Crawler
-----------------------------------

After calling ``request`` to load a page, we get back a
:symfonyclass:`Symfony\\Component\\DomCrawler\\Crawler` object. This is a
great object, which works a lot like the jQuery object in JavaScript. For
example, to find the value of the username field, we can search by its ``id``
and use the ``attr`` function::

    public function testIndex()
    {
        // ...

        $usernameVal = $cralwer
            ->filter('#user_register_username')
            ->attr('value')
        ;
        var_dump($usernameVal);
    }

Re-run the test to see the result.

.. tip::

    To see everything about the crawler, check out `The DomCrawler Component`_.

Testing Forms
-------------

One of the most common things to do in a functional test is to test a form.
Start by using the crawler to select our submit button and create a
:symfonyclass`Symfony\\Component\\DomCrawler\\Form` object::

    public function testIndex()
    {
        // ...

        // the name of our button is "Register!"
        $form = $crawler->selectButton('Register!')->form();
    }

In the browser, if we submit the form blank, we should see the form again with
some errors. Try this by passing this ``Form`` object to the client. Let's test
that the status code of the error page is 200 and that we at least see an
error::

    public function testIndex()
    {
        // ...

        // the name of our button is "Register!"
        $form = $crawler->selectButton('Register!')->form();

        $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegexp(
            '/This value should not be blank/',
            $client->getResponse()->getContent()
        );
    }

Run the test again to see that everything passes.

Let's create another Form object using the new Crawler. But this time, let's
give each field some data. This is done by treating the form like an array
and putting data in each field. These names come right from the HTML source
code itself, so check there to see what they look like::

    public function testIndex()
    {
        // ...

        $form['user_register[username]'] = 'user5';
        $form['user_register[username]'] = 'user5';
        $form['user_register[email]'] = 'user5@user.com';
        $form['user_register[plainPassword][first]'] = 'P3ssword';
        $form['user_register[plainPassword][second]'] = 'P3ssword';

        $client->submit($form);

Let's submit the form again. This time, the actual response we're expecting
back is a redirect. We can check this by calling the ``isRedirect`` method
on the response. Next, use the ``followRedirect`` method to tell the client
to follow the redirect like a standard browser. let's also make sure that
our success flash message shows up after the redirect::

    public function testIndex()
    {
        // ...

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertRegexp(
            '/Registration went super smooth/',
            $client->getResponse()->getContent()
        );
    }

Controlling Data / Fixtures in a Test
-------------------------------------

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

    public function testIndex()
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

    public function testIndex()
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

More about Container, the "doctrine" Service and the Entity Manager
-------------------------------------------------------------------

Quickly, let's talk about how we used the container at the top of the class.
Remember from the `first episode in this series`_ that the "container" in
Symfony is basically just a big array filled with useful objects. To get a
list of all of the objects in the container, run the ``container:debug`` console
command:

.. code-block:: bash

    php app/console container:debug

We can see that there's an object called ``doctrine`` and that it's a class
called Registry:

.. code-block:: text

    ...
    doctrine            container       Doctrine\Bundle\DoctrineBundle\Registry

If your editor can open files by class or filename, then you can open this
quickly to look inside. When you do, you'll see the ``getManager`` method.

Look now at how we normally get the entity manager from a controller by calling
the ``getDoctrine`` method::

    $em = $this->getDoctrine()->getManager();

Now that we know a little bit more about the container, let's open up the
base controller class to see what this method does::

    // vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
    public function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

Sweet! The ``getDoctrine`` method is just a shortcut to get out the service
called ``doctrine``. No matter where we are, the process to get the entity
manager is always the same: get the container and then find the service you
need. Life is easy from inside a controller or a functional test because we
have the container at our fingertips. Of course, sometimes you won't have
access to the container, but we'll cover that later.

.. _`PHPUnit site`: http://www.phpunit.de/manual/current/en/installation.html
.. _`The DomCrawler Component`: http://bit.ly/sf2-crawler
.. _`first episode in this series`: http://knpuniversity.com/screencast/symfony2-ep1/controller#symfony-ep1-what-is-a-service
