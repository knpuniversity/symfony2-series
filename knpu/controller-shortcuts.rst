Do Less Work in the Controller
==============================

Wow. You've already almost finished with first episode on Symfony2. That's
not easy - Symfony2 has a learning curve, and I've been throwing a lot of
tough concepts at you.

As a reward, let's see a few shortcuts!

The @Template Rendering Shortcut
--------------------------------

Head to google and search for `SensioFrameworkExtraBundle`_. Click the link
on Symfony.com. This bundle is all about shortcuts, and it came standard
with our project.

On the docs, scroll down to the ``@Template`` link and click it. Copy the
``use`` statement from the code block and paste it into ``EventController``::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...
    
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

    class EventController extends Controller
    {
        // ...
    }

Ok, let's remove the ``render`` method and just return the array of variables
we were passing to the template. Finish up by adding an ``@Template`` annotation
above ``indexAction`` with the template name::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    /**
     * @Template("EventBundle:Event:index.html.twig")
     */
    public function indexAction()
    {
        // ...
        
        return array(
            'entities' => $entities,
        );
    }

When we check the page in our browser, it works perfectly. This is a bit
of magic: it tells Symfony to render the template for us.

Now remove the template name and refresh::

    /**
     * @Template()
     */
    public function indexAction()
    {
        // ...
    }

This works too! Now we're talking.

If we don't pass a template name, it guesses it from the controller and action
name:

    Controller: EventBundle:Event:index

    Template: EventBundle:Event:index.html.twig

Annotation use Statements
~~~~~~~~~~~~~~~~~~~~~~~~~

Let's talk about the ``use`` statement we pasted in. Whenever you use an
annotation, you *must* have a ``use`` statement for it. If you don't, you'll
see a nice exception:

    [SemanticalError] The annotation "@Template" in method .. was never imported.
    Did you maybe forget to add a "use" statement for this annotation?

We actually saw this already in our ``Event`` entity. It was generated for
us, but it has a ``use`` statement for its ``ORM`` annotation.

Annotation Routing
------------------

What else can we do with annotations? How about routing?

On the docs, go back and click the `@Route and @Method`_ link. Copy the ``use``
statement into the controller and put an ``@Route`` annotation above ``indexAction``::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

    class EventController extends Controller
    {
        /**
         * @Template()
         * @Route("/")
         */
        public function indexAction()
        {
            // ...
        }
    }

Importing Routes from a Controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Hmm, routing right above the controller? Interesting!

But like all routing - we have to import it before it works. Open up the
``routing.yml`` file in the bundle, copy the ``event.yml`` import line and
change the key so its unique. To import annotation routes, just point the
resource at the ``Controller`` directory and add a type option:

.. code-block:: yaml

    # src/Yoda/EventBundle/Resources/config/routing.yml
    # ...
    
    EventBundle_event_annotation:
        resource: "@EventBundle/Controller"
        prefix:   /
        type:     annotation

When we refresh, it still works.

Duplicate Routes
~~~~~~~~~~~~~~~~

BUT, things are not as they seem. Check out the web debug toolbar. It says
that the ``event`` route is being matched. Now, run the ``router:debug``
console task. Uh oh, we have *two* routes with identical paths:

    event                    ANY         ANY    ANY  /
    ...
    yoda_event_event_index   ANY         ANY    ANY  /

The first route is from ``event.yml`` and the second is from our annotations
where Symfony generates a name automatically by default. When two routes
have the same path, the *first* route matches. So let's remove the first
one in ``event.yml``:

    # src/Yoda/EventBundle/Resources/config/routing/event.yml
    # event:
    #     pattern:  /
    #     defaults: { _controller: "EventBundle:Event:index" }

*Now* when we refresh, it works *and* our route is matched.

But when we try to create a new event, we get an error! In ``new.html.twig``
we're generating a link to the homepage by using its route name - ``event``.
Symfony generated a different name for the annotation route: ``yoda_event_event_index``.

Easy fix. Just add a ``name="event"`` key to the routing annotation::

    // src/Yoda/EventBundle/Controller/EventController.php
    // ...

    /**
     * @Template()
     * @Route("/", name="event")
     */
    public function indexAction()
    {
        // ...
    }

And just like that, life is good. For homework, read through these docs and
see what other cool things you can do.

.. _`SensioFrameworkExtraBundle`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html
.. _`@Route and @Method`: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/routing.html
