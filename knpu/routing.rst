Routing: The URLs of the World
==============================

Let's face it, every page needs a URL. When you need a new page, we always
start by creating a route: a chunk of config that gives that page a URL.
In Symfony, all routes are configured in just one file: ``app/config/routing.yml``.

Head back to your browser and put ``/hello/skywalker`` after ``app_dev.php``:

  http://localhost:8000/app_dev.php/hello/skywalker

The code behind this impressive page was generated automatically in the new
bundle. You can change the last part of the URL to anything you want and
it greets you politely.

The fact that this page works means that there's a route somewhere that
defines this URL pattern. I already said that all routes live in ``routing.yml``,
so it *should* be there.

Route Importing
---------------

Surprise! It's not here. But there *is* an ``event`` entry that was added when
we generated the bundle:

.. code-block:: yaml

    # app/config/routing.yml
    event:
        resource: "@EventBundle/Resources/config/routing.yml"
        prefix:   /

The ``resource`` key works like a PHP include: point it at another routing
file Symfony will pull it in. So, even though Symfony only reads this one
routing file, we can pull in routes from anywhere.

.. note::

    With a little extra work, you could even do cool stuff like loading routes
    from a custom database table.

.. tip::

    The ``event`` key has no significance when importing other routing files.

So what's up with the ``@EventBundle`` magic? The ``resource`` should just
point to the path of another file, relative to this one. But if the file
lives in a bundle directory, we can use ``@`` and then the nickname we gave
that bundle. Since ``EventBundle`` lives at ``src/Yoda/EventBundle``, that's
where we'll find the imported file.

Basic Routing
-------------

Ah hah! We found the missing route, which makes the ``/hello/skywalker``
page work:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    event_homepage:
        pattern:  /hello/{name}
        defaults: { _controller: EventBundle:Default:index }

The ``pattern`` is the URL and the ``{name}`` of the pattern acts like a
wildcard. It means that any URL that looks like ``/hello/*`` will match this
route. If we change ``hello`` to ``there-is-another``, the URL to the page
changes:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    event_homepage:
        # you can change the URL (but change it back after trying this!)
        pattern:  /there-is-another/{name}
        defaults: { _controller: EventBundle:Default:index }

Update the URL in your browser to see the moved page (and then be cool and
change the ``pattern`` back to ``/hello/{name}``):

  http://localhost:8000/app_dev.php/there-is-another/skywalker

path versus pattern: no difference
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Ok, so when you generate your bundle, your route might have ``path`` instead
of ``pattern``. Scandal!

Here's the story. Once upon a time, the Symfony elders renamed ``pattern``
to ``path``, just because it's more semantically correct. And hey, it's
shorter anyways. But ``pattern`` still works and will until Symfony 3.0.
Sorry, that's about as scandalous as things get around Symfony.

To be with the new, I'll change my routing to use ``path``:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    event_homepage:
        path:  /hello/{name}
        defaults: { _controller: EventBundle:Default:index }

.. note::

    But why was it generated as ``pattern``? When we recorded this, the bundle
    that does the generation magic hadn't released their fix for this change.

The defaults ``_controller`` key is the second critical piece of every route.
It tells Symfony which controller to execute when the route is matched. But
a controller is just a fancy word for a PHP function. So you write this controller
function and Symfony executes it when the route is matched.

The _controller Syntax
~~~~~~~~~~~~~~~~~~~~~~

I know, the ``EventBundle:Default:index`` controller doesn't look like any
function name you've ever met.

In reality, it's a top-secret syntax with three different parts:

* the bundle name
* the controller class name
* and the method name.

Symfony maps this to a controller class and method:

.. code-block:: text

    _controller: **EventBundle**:**Default**:**index**

    src/Yoda/**EventBundle**/Controller/**Default**Controller::**index** Action()

Stop! Let's stare at this for a few seconds, because we're going to see it
a lot.

Notice that Symfony adds the word ``Controller`` to the end of the class,
and ``Action`` to the end of the method name. You'll probably hear the method
name referred to as an "action".

Open up the controller class and find the ``indexAction`` method::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    namespace Yoda\EventBundle\Controller;
    
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    
    class DefaultController extends Controller
    {
        public function indexAction($name)
        {
            return $this->render(
                'EventBundle:Default:index.html.twig',
                array('name' => $name)
            );
        }
    }

Routing Parameters and Controller Arguments
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

First, check out the ``$name`` variable that's passed as an argument to the
method. This is sweet because the value of this argument comes from the ``{name}``
wildcard in our route. So if I go to ``/hello/edgar``, the name variable
is ``edgar``. When I go to ``/hello/skywalker``, it's skywalker.

And if we change ``{name}`` in the route to something else like ``{firstName}``,
we'll see an error:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    event_homepage:
        path:  /hello/{firstName}
        defaults: { _controller: EventBundle:Default:index }

.. code-block:: text

    Controller "Yoda\EventBundle\Controller\DefaultController::indexAction()"
    requires that you provide a value for the "$name" argument (because there
    is no default value or because there is a non optional argument after
    this one).

Ah hah! So the name of the argument needs to match the name used in the route.
Now, the route still has the same URL, we've just given the routing wildcard
a different name internally::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    public function indexAction($firstName)
    {
        return $this->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );
    }

Let's get crazy by putting a second wildcard in the route path:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    event_homepage:
        path:  /hello/{firstName}/{count}
        defaults: { _controller: EventBundle:Default:index }

When we refresh, we get a "No route found" error. We need to put *something*
for the ``count`` wildcard, other wise it won't match our route. Add ``/5``
to the end to see the page:

  http://localhost:8000/app_dev.php/hello/skywalker/5

Now that we have a ``count`` wildcard in the route, we can of course add
a ``$count`` argument to the action::

    // src/Yoda/EventBundle/Controller/DefaultController.php

    // ...
    public function indexAction($firstName, $count)
    {
        var_dump($firstName, $count);die;
        // ...
    }

To prove everything's working, let's dump both arguments. One neat thing
is that the order of the arguments doesn't matter. To prove it, swap the order
of the arguments and refresh::

    // src/Yoda/EventBundle/Controller/DefaultController.php

    // ...
    public function indexAction($count, $name)
    {
        // still prints "skywalker" and then "5"
        var_dump($name, $count);die;
        // ...
    }

We've seen this twice now: Symfony matches the routing wildcards to method
arguments by matching their names.

Remove the ``var_dump`` code so our page works again.

Routing is full of lots of cool tricks and we'll discover them along the way.

Debugging Routes
----------------

Wondering what other URLs your app might have? Our friend console can help
you with that with the ``router:debug`` command:

.. code-block:: text

    $ php app/console router:debug

This shows a full list of every route in your app. Right now, that means
the one we've been playing with plus a few other internal Symfony debugging
routes. Remember this command: it's your Swiss army knife for finding your
way through a project.
