Using PHPDoc for Auto-Completion
================================

With the base Controller, we can give ourselves shortcuts to develop faster
and faster.

Inside ``RegisterController``, my IDE recognizes the ``setToken`` method
on the security context automatically. Actually, this only works because
I'm using an awesome Symfony2 plugin for PHPStorm. The ``getSecurityContext``
method doesn't have any PHPDoc, so any other editor will have no idea what
type of object this method returns.

To fix this, and because PHPDoc is a good practice, let's add some to our
new method::

    // src/Yoda/EventBundle/Controller/Controller.php
    // ...

    /**
     * @return \Symfony\Component\Security\Core\SecurityContext
     */
    public function getSecurityContext()
    {
        return $this->container->get('security.context');
    }

Because of the Symfony2 plugin, the ``@return`` tag was filled in automatically.
That's awesome! But if it hadn't, we could figure out what type of object
``security.context`` is by using the ``container:debug`` console command:

.. code-block:: bash

    php app/console container:debug security.context

If you use PHPStorm, install the `Symfony Plugin`_. If not, rely on this
console command to help you find out more about a service.

Re-Running the Tests
--------------------

It's like you read my mind! Now is a prefect time to re-run the test suite
to make sure we haven't broken anything. I know I know, we're missing tests
for some important parts, like event creation, but it's better than nothing.

But first, update your test database for our latest schema changes:

.. code-block:: bash

    php app/console doctrine:schema:update --force --env=test

We need this because we configured our project in episode 2 to use an entirely
different database for testing.

.. code-block:: bash

    ./bin/phpunit -c app/

.. _`Symfony Plugin`: http://knpuniversity.com/screencast/symfony2-ep1/bundles#the-phpstorm-symfony-plugin
