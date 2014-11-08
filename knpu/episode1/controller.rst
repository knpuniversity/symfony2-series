Controllers: Get to work!
=========================

3 steps. That's all that's behind rendering a page:

#. The URL is compared against the routes until one matches;
#. Symfony reads the ``_controller`` key and executes that function;
#. We build the page inside the function.

The controller is all about us, it's where we shine. Whether the page is
HTML, JSON or a redirect, we make that happen in this function. We might
also  query the database, send an email or process a form submission here.

.. tip::

    Some people use the word "controller" to both refer to a the class (like
    ``DefaultController``) *or* the action inside that class.

Returning a Response
--------------------

Controller functions are dead-simple, and there's just one big rule: it must
return a Symfony :symfonyclass:`Symfony\\Component\\HttpFoundation\\Response`
object.

To create a new Response, add its namespace to top of the controller class.
I know, the namespace is horribly long, so this is where having a smart IDE
like PHPStorm will make you smile::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    namespace Yoda\EventBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Symfony\Component\HttpFoundation\Response;

    class DefaultController extends Controller
    {
        // ...
    }

.. tip::

    If you're new to PHP 5.3 namespaces, check out our
    `free screencast on the topic`_.

Now create the new Response object and quote Admiral Ackbar::

    public function indexAction($count, $firstName)
    {
        return new Response('It\'s a traaaaaaaap!');
    }

Now, our page has the text and nothing else.

Again, controllers are simple. No matter how complex things seem, the goal
is always the same: generate your content, put it into a Response, and return
it.

Returning a JSON Response
~~~~~~~~~~~~~~~~~~~~~~~~~

How would we return a JSON response? Let's create an array that includes
the ``$firstName`` and ``$count`` variables and turn it into a string with
``json_encode``. Now, it's exactly the same as before: pass that to a ``Response``
object and return it::

    public function indexAction($count, $firstName)
    {
        $arr = array(
            'firstName' => $firstName,
            'count'     => $count,
            'status'    => 'It\'s a traaaaaaaap!',
        );

        return new Response(json_encode($arr));
    }

Now our browser displays the JSON string.

.. tip::

    There is also a `JsonResponse`_ object that makes this even easier.

Wait. There *is* one problem. By using my browser's developer tools, I can
see that the app is telling my browser that the response has a ``text/html``
content type.

That's ok - we can fix it easily. Just set the ``Content-Type`` header on
the ``Response`` object to ``application/json``::

    public function indexAction($count, $firstName)
    {
        // ...

        $response = new Response(json_encode($arr));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }

Now when I refresh, the response has the right ``content-type`` header.

I know I'm repeating myself, but this is important I promise! Every controller
returns a Response object and you have full control over each part of it.

Rendering a Template
--------------------

Time to celebrate: you've just learned the core of Symfony. Seriously, by
understanding the routing-controller-Response flow, we could do anything.

But as much as I love printing Admiral Ackbar quotes, life isn't always
this simple. Unless we're making an API, we usually build HTML pages. We
could put the HTML right in the controller, but that would be a Trap!

Instead, Symfony offers you an optional tool that renders template files.

Before that, we should take on another buzzword: services. These are even
trendier than bundles!

.. _symfony-ep1-what-is-a-service:

Symfony Services
~~~~~~~~~~~~~~~~

Symfony is basically a wrapper around a big bag of objects that do helpful
things. These objects are called "services": a techy name for an object that
performs a task. Seriously: when you hear service, just think "PHP object".

Symfony has a ton of these services - one sends emails, another queries the
database and others translate text and tie your shoelaces. Symfony puts the
services into a big bag, called the "mystical service container". Ok, I added
the word mystical: it's just a PHP object and if you have access to it, you
can fetch any service and start using it.

And here's the dirty secret: everything that you think "Symfony" does, is
actually done by some service that lives in the container. You can even tweak
or replace core services, like the router. That's really powerful.

In any controller, this is great news because, surprise, we have access
to the mystical container via ``$this->container``::

    public function indexAction($count, $firstName)
    {
        // not doing anything yet...
        $this->container;

        // ...
    }

.. note::

    This only works because we're in a controller *and* because we're exending
    the base :symfonyclass:`Symfony\\Bundle\\FrameworkBundle\\Controller\\Controller`
    class.

One of the services in the container is called ``templating``. I'll show
you how I knew that in a bit::

    public function indexAction($count, $firstName)
    {
        $templating = $this->container->get('templating');

        // ...
    }

This templating object has a ``render`` method on it. The first argument
is the name of the template file to use and the second argument holds the
variables we want to pass to the template::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    // ...

    public function indexAction($count, $firstName)
    {
        $templating = $this->container->get('templating');

        $content = $templating->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );

        // ...
    }

The template name looks funny because it's another top secret syntax with
three parts:

* the **bundle name**
* a **directory name**
* and the **template's filename**.

.. code-block:: text

    EventBundle:Default:index.html.twig

    src/Yoda/EventBundle/Resources/views/Default/index.html.twig

This looks like the ``_controller`` syntax we saw in routes, but don't mix
them up. Seriously, one points to a controller class & method. This one points
to a template file.

Open up the template.

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/index.html.twig #}

    Hello {{ name }}

Welcome to Twig! A curly-little templating language that you're going to
fall in love with. Right now, just get fancy by adding a strong tag:

.. code-block:: html+jinja

    Hello <strong>{{ name }}</strong>

Back in the controller, the ``render`` method returns a string. So just like
before, we need to put that into a new ``Response`` object and return it::

    public function indexAction($count, $firstName)
    {
        $templating = $this->container->get('templating');

        $content = $templating->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );

        return new Response($content);
    }

Refresh. There's our rendered template. We still don't have a fancy layout,
just relax - I can only go so fast!

Make this Shorter
-----------------

Since rendering a template is pretty darn common, we can use some shortcuts.
First, the ``templating`` service has a ``renderResponse`` method. Instead
of returning a string, it puts it into a new ``Response`` object for us.
Now we can remove the ``new Response`` line and its ``use`` statement::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    namespace Yoda\EventBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    class DefaultController extends Controller
    {
        public function indexAction($count, $firstName)
        {
            $templating = $this->container->get('templating');

            return $templating->renderResponse(
                'EventBundle:Default:index.html.twig',
                array('name' => $firstName)
            );
        }
    }

And even Shorter
~~~~~~~~~~~~~~~~

Better. Now let's do less. Our controller class extends Symfony's own base
controller. That's optional, but it gives us shortcuts.

`Open up the base class`_, I'm using a "go to file" shortcut in my editor to
search for the ``Controller.php`` file.

One of its shortcut is the ``render`` method. Wait, this does exactly what
we're already doing! It grabs the ``templating`` service and calls ``renderResponse``
on it::

    // vendor/symfony/symfony/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
    // ...
    
    public function render($view, array $parameters = array(), Response $response = null)
    {
        return $this->container->get('templating')->renderResponse(
            $view,
            $parameters,
            $response
        );
    }   

Let's just kick back, call this method and return the result::

    public function indexAction($count, $firstName)
    {
        return $this->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );
    }

I'm sorry I made you go the long route, but now you know about the container
and how services are working behind the scenes. And as you use more shortcut
methods in Symfony's base controller, I'd be so proud if you looked to see
what each method *actually* does.

Controllers are easy: put some code here and return a ``Response`` object.
And since we have the container object, you've got access to every service
in your app.

Oh right, I haven't told you what services there are! For this, go back to
our friend console and run the ``container:debug`` command:

.. code-block:: text

    $ php app/console container:debug

It lists every single service available, as well as what type of object it
returns. Color you dangerous.

Ok, onto the curly world of Twig!

.. _`free screencast on the topic`: http://knpuniversity.com/screencast/php-namespaces-in-120-seconds
.. _`JsonResponse`: http://symfony.com/doc/current/components/http_foundation/introduction.html#creating-a-json-response
.. _`Open up the base class`: https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php
