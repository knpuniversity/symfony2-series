Functional Testing
==================

Our site is looking cool. But how can we be sure that we haven't broken anything
along the way? Right now, we can't!

Let's avoid the future angry phone calls from clients by adding some tests.
There are two main types: unit tests and functional tests. Unit tests test
individual PHP classes. We'll save that topic for another screencast. Functional
tests are more like a browser that surfs to pages on your site, fills out
forms and checks for specific things.

Your First Functional Test
--------------------------

When we generated the ``EventBundle`` in the last screencast, it created
2 stub functional tests for us. How nice!

Create a ``Tests/Controller`` directory in ``UserBundle``, copy one of the
test files and rename it to ``RegisterControllerTest``::

    // src/Yoda/UserBundle/Tests/Controller/RegisterControllerTest.php
    namespace Yoda\EventBundle\Tests\Controller;

    use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

    class RegisterControllerTest extends WebTestCase
    {
        public function testRegister()
        {
            $client = static::createClient();
            // ...
        }
    }

Rename the method to ``testRegister``::

    // src/Yoda/UserBundle/Tests/Controller/RegisterControllerTest.php
    // ...

    public function testRegister()
    {
        $client = static::createClient();
        // ...
    }

The idea is that each controller, like ``RegisterController`` will have its
own test class, like ``RegisterControllerTest``. Then, each action method,
like ``registerAction``, will have its own test method, like ``testRegister``.
There's no technical reason you need to organize things like this. The only
rule is that you need to start each method with the word test.

Using the Client object
-----------------------

That :symfonyclass:`$client <Symfony\\Bundle\\FrameworkBundle\\Client>` variable
is like a browser that we can use to surf to pages on our site. Start small
by testing that the ``/register`` page returns a 200 status code and that
the word "Register" appears somewhere::

    public function testRegister()
    {
        $client = static::createClient();

        $client->request('GET', '/register');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Register', $response->getContent());
    }

The ``assertEquals`` and ``assertContains`` methods come from PHPUnit, the
library that will actually run the test.

Installing PHPUnit
------------------

To run the test, we need PHPUnit: the de-facto tool for testing. You
can install it globally or locally in this project via Composer. For the
global option, check out their docs.

Let's use Composer's ``require`` command and search for phpunit:

.. code-block:: bash

    php composer.phar require

Choose the ``phpunit/phpunit`` result. For a version, I'll go to `packagist.org`_
and find the library. Right now, it looks like the latest version is ``4.1.3``.
I'll use the constraint ``~4.1``, which basically means 4.1 or higher.

.. tip::

    Want to know more about the ~ version constraint? Read `Next Significant Release`_
    on Composer's website.

This added ``phpunit/phpunit`` to the ``require`` key in ``composer.json``
and it ran the ``update`` command in the background to download it.

.. tip::

    Since PHPUnit isn't actually needed to make our site work (it's only
    needed to run the tests), it would be even better to put it in the
    ``require-dev`` key of ``composer.json``. Search for ``require-dev``
    on `this post`_ for more details.

Running the Tests
-----------------

We now have a ``bin/phpunit`` executable, so let's use it! Pass it a ``-c app``
option:

.. code-block:: bash

    php bin/phpunit -c app

.. tip::

    If you're on Windows (or a VM running in Windows), the above command
    won't work for you (it'll just spit out some text). Instead, run:
    
    .. code-block:: bash
    
        php vendor/phpunit/phpunit/phpunit -c app

This tells PHPUnit to look for a configuration file in the ``app/`` directory.
And hey! There's a ``phpunit.xml.dist`` file there already for it to read. This
tells phpunit how to bootstrap and where to find our tests.

But we see a few errors. If you look closely, you'll see that it's executing
the two test files that were generated automatically in ``EventBundle``.
Git rid of these troublemakers and try again:

.. code-block:: bash

    rm src/Yoda/EventBundle/Tests/Controller/*Test.php
    php bin/phpunit -c app

Green! PHPUnit runs our test, where we make a request to ``/register`` and
check the status code and look for the word "Register".

To see what a failed test looks like, change the test to check for Ackbar instead
of Resgister and re-run it::

    $this->assertContains('Ackbar', $response->getContent());

It doesn't find it, but it does print out the page's content, which we could
use to debug. It's a trap! Change the test back to look for ``Register``::

    $this->assertContains('Register', $response->getContent());

Traversing the Dom with the Crawler
-----------------------------------

When we call the ``request()`` function, it returns a 
:symfonyclass:`Symfony\\Component\\DomCrawler\\Crawler` object, which works
a lot like the jQuery object in JavaScript. For example, to find the value
of the username field, we can search by its ``id`` and use the ``attr`` function.
It should be equal to "Leia"::

    public function testRegister()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/register');
        $response = $client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains('Register', $response->getContent());

        $usernameVal = $crawler
            ->filter('#user_register_username')
            ->attr('value')
        ;
        $this->assertEquals('Leia', $usernameVal);
    }

Re-run the test to see the result:

.. code-block:: bash

    php bin/phpunit -c app

.. tip::

    To see everything about the crawler, check out `The DomCrawler Component`_.

.. _`Next Significant Release`: https://getcomposer.org/doc/01-basic-usage.md#next-significant-release-tilde-operator-
.. _`this post`: http://daylerees.com/composer-primer
.. _`The DomCrawler Component`: http://bit.ly/sf2-crawler
.. _`packagist.org`: https://packagist.org/
