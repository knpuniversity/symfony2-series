Routing
=======

Alright - time for some routing! Every page needs a URL. In Symfony, all
the URLs of your application are configured in a single place: the ``routing.yml``
file inside the ``app/config`` directory. Whenever you need to create a page,
you'll start by creating a route.

Head back to your browser and put ``/hello/skywalker`` after ``app_dev.php``:

  http://localhost:8000/app_dev.php/hello/skywalker

This is a really simple page that was generated automatically in the new
bundle. You can change the last part of the URL to anything you want and
the page updates.

Let's see how this works. The fact that this page exists means that
there's a route somewhere that defines this URL pattern. Since I mentioned
earlier that all routes live in the ``app/config/routing.yml`` file, the route
*should* be in that file.

Route Importing
---------------

But it's *not*. Instead of the route we're looking for, there's an ``event``
entry that was added when the bundle was generated:

.. code-block:: yaml

    # app/config/routing.yml
    event:
        resource: "@EventBundle/Resources/config/routing.yml"
        prefix:   /

This works a bit like a PHP include: just specify another routing file via
the ``resource`` key and Symfony will pull it in. So, even though Symfony
only really loads this one routing file, you can pull in routes from anywhere.
With a little extra work, you could even do cool stuff like loading routes
from a custom database table.

.. tip::

    The ``event`` key has no significance when importing other routing files.

Let's check out the resource string, which looks a little magical. This
*should* be the full path to another routing file. In this case, the file we're
importing lives inside the ``EventBundle``. Instead of hardcoding its path,
Symfony let's use a special ``@EventBundle`` syntax. Since the ``EventBundle``
lives at ``src/Yoda/EventBundle``, that's where we'll find the imported routing
file.

Basic Routing
-------------

As expected, the file holds the route that makes the ``/hello/skywalker``
page work:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    event_homepage:
        pattern:  /hello/{name}
        defaults: { _controller: EventBundle:Default:index }

The ``pattern`` is the URL for the route. The curly braces around the ``name``
part of the pattern is like a wildcard. It means that any URL that looks like
``/hello/*`` is going to match this route. If we change ``hello`` to
``there-is-another``, then the URL to the page changes. Change the URL in
your browser to see the moved page (and then change the ``pattern`` back
to ``/hello/{name}``):

  http://localhost:8000/app_dev.php/there-is-another/skywalker

path versus pattern: no difference
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If you have a ``path`` key instead of ``pattern``, awesome! Both ``path``
and ``pattern`` do the exact same thing, and actually, ``path`` is the newer
and preferred name. I'll change my routing to use ``path``:

    # src/Yoda/EventBundle/Resources/config/routing.yml
    event_homepage:
        path:  /hello/{name}
        defaults: { _controller: EventBundle:Default:index }

The defaults ``_controller`` key is the second thing you'll see on every route.
It tells Symfony which controller to execute when the route is matched. A
controller is a fancy word for a PHP function. You write the controller and
Symfony executes it when the route is matched. In Symfony, a controller is
usually a public method on a PHP class, instead of a flat PHP function.

The _controller Syntax
~~~~~~~~~~~~~~~~~~~~~~

The ``_controller`` string uses a funny, but simple syntax. It has three different
parts: the bundle name, the controller class name, and the method name. Internally,
this maps to a controller class and a method. Notice that Symfony adds the
word ``Controller`` to the end of the class, and ``Action`` to the end of
the method name. The method name will commonly be called an "action".

_controller: **EventBundle**:**Default**:**index**

src/Yoda/**EventBundle**/Controller/**Default**Controller::**index** Action()

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

The first thing to notice is the ``$name`` variable that's passed as an argument
to the method. This is really cool because the value of this argument comes
from the ``{name}`` wildcard back in our route. In other words, when I go to
``/hello/edgar``, the name variable is ``edgar``. When I go to ``/hello/skywalker``,
it's skywalker. If we change ``name`` in the route to something else (e.g.
``firstName``), we'll see an error:

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

The name of the argument needs to match up with the name used in the route
(e.g. ``/hello/{firstName}``). The route still has the same URL, but we've
given the routing wildcard a different name internally::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    public function indexAction($firstName)
    {
        return $this->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );
    }

You can also add more wildcards to your route. For example, let's add a ``count``
wildcard after name:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    event_homepage:
        path:  /hello/{firstName}/{count}
        defaults: { _controller: EventBundle:Default:index }

If you refresh, you'll get a "No route found" error. That's because you need
to put *something* for the ``count`` wildcard to match the route. Add ``/5``
to the end of the URL to see the page:

  http://localhost:8000/app_dev.php/hello/skywalker/5

Now that we have a ``count`` wildcard in the route, we can add a ``$count``
argument to the action::

    // src/Yoda/EventBundle/Controller/DefaultController.php

    // ...
    public function indexAction($firstName, $count)
    {
        var_dump($firstName, $count);die;
        // ...
    }

To prove everything's working, let's dump the two arguments. One neat thing
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

Symfony matches the routing wildcards to action arguments by name, not order.

Remove the ``var_dump`` code so our page works again.

There are a bunch of other *really* cool things you can do with routes, and
we'll show them off along the way.

Debugging Routes
----------------

Before we talk about controllers, let's check out a really handy tool for
visualizing all the routes in your app. From the command line, run your console
script and execute the ``router:debug`` command:

.. code-block:: bash

    php app/console router:debug

You'll see a list of every route in your app, including the one we just created
and some others that are internal to Symfony and help debugging.