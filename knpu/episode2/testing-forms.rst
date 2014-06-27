Testing Forms
=============

One of the most common things you'll want to do in a test is fill out a form.
Start by using the crawler to select our submit button and get a
:symfonyclass:`Symfony\\Component\\DomCrawler\\Form` object. Notice that
we're selecting it by the actual text that shows up in the button, though
you can also use the ``id`` or ``name`` attributes::

    public function testRegister()
    {
        // ...

        // the name of our button is "Register!"
        $form = $crawler->selectButton('Register!')->form();
    }

In the browser, if we submit the form blank, we should see the form again with
some errors. We can simulate this by calling ``submit()`` on the client
and passing it the ``$form`` variable.

.. tip::

    Both ``request()`` and ``submit()`` return a Crawler object that represents
    the DOM after making that request. Be sure to always get a new ``$crawler``
    variable each time you call one of these methods.

Let's test that the status code of the error page is 200 and that we at least
see an error::

    public function testRegister()
    {
        // ...

        // the name of our button is "Register!"
        $form = $crawler->selectButton('Register!')->form();

        $crawler = $client->submit($form);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertRegexp(
            '/This value should not be blank/',
            $client->getResponse()->getContent()
        );
    }

Run the test again!

.. code-block:: bash

    php bin/phpunit -c app

Beautiful!

Filling out the Form with Data
------------------------------

Let's submit the form again, but this time with some data! Use ``selectButton``
to get another ``$form`` object.

Now, give each field some data. This is done by treating the form like an
array and putting data in each field. These names come right from the HTML
source code, so check there to see what they look like::

    public function testRegister()
    {
        // ...

        // submit the form again
        $form = $crawler->selectButton('Register!')->form();

        $form['user_register[username]'] = 'user5';
        $form['user_register[email]'] = 'user5@user.com';
        $form['user_register[plainPassword][first]'] = 'P3ssword';
        $form['user_register[plainPassword][second]'] = 'P3ssword';

        $crawler = $client->submit($form);
    }

Now when we submit, the response we get back should be a redirect. We can
check that by calling the ``isRedirect`` method on the response. Next, use
the ``followRedirect()`` method to tell the client to follow the redirect
like a standard browser. Finally, let's make sure that our success flash message
shows up after the redirect::

    public function testRegister()
    {
        // ...

        $crawler = $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertContains(
            'Welcome to the Death Star! Have a magical day!',
            $client->getResponse()->getContent()
        );
    }

Run the tests!

.. code-block:: bash

    php bin/phpunit -c app

Success! We now have proof that we can visit the registration form and fill
it out with and without errors. If we accidentally break that later, our
test will tell us.
