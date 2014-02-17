Controllers
===========

Now that we've gone through routing, let's talk about the second step of
creating a page: the controller. The controller is the PHP function where
*your* code goes. It's where we do whatever work we need to in order to build
the page, whether that be a full HTML page, a JSON string, or a redirect.
A controller might query the database, send an email, process a form submission,
or anything else.

In our example, the controller is the ``indexAction`` method, which is also
sometimes called an "action". As a word of warning, some people will use
the word "controller" both to refer to a controller class, like ``DefaultController``,
as well as the action inside that class. When I say controller, I'll be
talking about the actual method that Symfony executes.

Returning a Response
--------------------

It's time for you to see just how simple a controller can be. A controller
has just one rule: it must return a Symfony Response object. To create a
new Response, first add its namespace at the top of the controller
class. The namespace is kinda long, but if you have an editor like PHPStorm,
it can help out. If you're new to PHP 5.3 namespaces, check out our
`free screencast on the topic`_::

    // src/Yoda/EventBundle/Controller/DefaultController.php
    namespace Yoda\EventBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Symfony\Component\HttpFoundation\Response;

    class DefaultController extends Controller
    {
        // ...
    }

Now, let's create a new Response object, give it some text, and return
it::

    public function indexAction($count, $firstName)
    {
        return new Response('It\'s a traaaaaaaap!');
    }

When the page renders, it's got our text and nothing else. The beauty is
that no matter how many tools you use, the goal of your controller is always
the same: generate your content, put it into a Response, and return it.

Returning a JSON Response
~~~~~~~~~~~~~~~~~~~~~~~~~

Next, Instead of returning text, let's return a JSON string with the 
``firstName`` and ``count`` values. All we need to do is create an array,
turn it into a string with ``json_encode``, and then pass it to a new Response
object. When you refresh, you'll see the JSON string::

    public function indexAction($count, $firstName)
    {
        $arr = array(
            'firstName' => $firstName,
            'count'     => $count,
            'status'    => 'It\'s a traaaaaaaap!',
        );

        return new Response(json_encode($arr));
    }

.. tip::

    There is also a `JsonResponse`_ object that makes this even easier.

But there *is* one slight problem. By using my browser's developer tools, I
can see that our application is returning JSON, but telling my browser that
it's HTML code. To fix this, all we need to do is set the ``Content-Type``
header on the Response object to ``application/json``::

    public function indexAction($count, $firstName)
    {
        // ...

        $response = new Response(json_encode($arr));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }

When I refresh, the page comes back with the right content type.

The point is that no matter what you need to do, you have full control over
the response that your controller returns.

Rendering a Template
--------------------

Now, I realize that life isn't always as simple as "hello Luke" and
simple JSON strings. In the real world, we're usually returning rich HTML
pages. We could try putting the HTML right in the controller, but that would
be a Trap! Fortunately, Symfony offers you an optional tool that renders
template files, which are the perfect place for all your beautiful ``div``
and ``span`` tags.

Stick with me through this next section, because I'm about to show you some
really *cool* things about the way Symfony works.

.. _symfony-ep1-what-is-a-service:

Symfony is basically a wrapper around a bunch of objects that do helpful
things. These objects are called "services", which is a fancy name for an
object that performs a task. This is important: when you hear service, you
should think "PHP object".

Symfony has a ton of services, including one that delivers emails, another
that queries the database, and even one that translates text. Symfony puts
all of the services into a "container" object, and if you have access to
the container, you can get out any service and start using it. The "service
container", as its called, is the most fundamental part of Symfony. Everything
that you think "Symfony" does, is actually done by some service that lives
in the container. This all gets really cool later when you learn how to add
tweak, and even replace services. But we'll get to that.

Back in our controller, this is great news because the container object is
available via ``$this->container`` if you extend Symfony's base controller.
In fact, we can use it to get out a really cool service called ``templating``
which as the name might hint, is able to render templates::

    public function indexAction($count, $firstName)
    {
        $templating = $this->container->get('templating');

        // ...
    }

To do that, call render and pass it a template name. The template name is
a shortcut that points to a specific file in our application. The name always
has three parts: the **bundle name**, a **directory name**, and the **template's filename**.
This format looks a lot like the ``_controller`` string used in routes. 
But seriously, do not forget these are not the same thing: one points to a
controller class & method. The other points to a template file::

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

Now, let's look at the template file:

.. code-block:: html+jinja

    {# src/Yoda/EventBundle/Resources/views/index.html.twig #}

    Hello {{ name }}

If it looks weird, that's ok. The template is written in Twig instead of
PHP. Stick around for the next chapter  to hear more on Twig. For now, let's
at least get fancy by adding a strong tag.

.. code-block:: html+jinja

    Hello <strong>{{ name }}</strong>

Back in the controller, the ``render`` method returns a string. We'll take
that string, pass it to a new ``Response``, and return it. When we refresh
the page, we'll see our rendered template. We still don't have a fancy layout,
but we'll get there::

    public function indexAction($count, $firstName)
    {
        $templating = $this->container->get('templating');

        $content = $templating->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );

        return new Response($content);
    }

Controller Shortcut Methods
---------------------------

Since rendering a template and returning its contents is such a common thing
to do, there are a few shortcuts for us. First, the templating service
has a ``renderResponse`` method. Instead of returning a string result, it returns
a new ``Response`` filled with the content from the template. This means
we can remove the ``new Response`` line as well as the ``use`` statement we added
earlier::

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

This is better, but we can and want to do even less work. By default, our
controller class extends Symfony's own base controller. You don't *have* to
extend it, but the base class gives you lots of shortcuts. 

Open up the base class, I'm using a "go to file" shortcut in my editor to
search for the controller.

One of those shortcuts is a ``render`` method. The ``render`` method does
exactly the same thing we're doing: it grabs the ``templating`` service and
calls ``renderResponse`` on it::

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

This means that from our controller, we can kick back and just call the ``render``
method on Symfony's own controller class and return the result. This is the
most common way to render a template from inside a controller::

    public function indexAction($count, $firstName)
    {
        return $this->render(
            'EventBundle:Default:index.html.twig',
            array('name' => $firstName)
        );
    }

We took the long route initially only because I wanted you to understand
what was really going on behind the scenes.

Ideally this example shows you both the power and simplicity of a controller.
The controller is where *your* code goes - you can do anything you need to
as long as you return a ``Response``. With the service container available
via ``$this->container``, you've got access to every service in your app. If
you're curious about what services are available, check out the ``container:debug``
console command. It lists every single service available, as well as what
type of object it returns:

.. code-block:: bash

    php app/console container:debug

As you develop, you'll start using more of the shortcuts methods in Symfony's
base controller. It would be brilliant if you would look to see what each
of these methods *actually* does. 

.. tip::

    Symfony base Controller is located at:
    ``vendor/symfony/src/Symfony/Bundle/FrameworkBundle/Controller/Controller.php``.

Congrats! You've already covered some pretty important and advanced topics.
Now it's time to explore the world of TWIG!

.. _`free screencast on the topic`: http://knpuniversity.com/screencast/php-namespaces-in-120-seconds
.. _`JsonResponse`: http://symfony.com/doc/current/components/http_foundation/introduction.html#creating-a-json-response