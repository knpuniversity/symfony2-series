Testing Forms
=============

One of the most common things to do in a functional test is to test a form.
Start by using the crawler to select our submit button and create a
:symfonyclass`Symfony\\Component\\DomCrawler\\Form` object::

    public function testRegister()
    {
        // ...

        // the name of our button is "Register!"
        $form = $crawler->selectButton('Register!')->form();
    }

In the browser, if we submit the form blank, we should see the form again with
some errors. Try this by passing this ``Form`` object to the client. Let's test
that the status code of the error page is 200 and that we at least see an
error::

    public function testRegister()
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

    public function testRegister()
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

    public function testRegister()
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