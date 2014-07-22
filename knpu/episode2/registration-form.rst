Registration Form
=================

Let's make our site a little bit more legit with a registration form.
Go grab some coffee, cause we're about to rock our world with some big and
powerful concepts like forms and validation.

Creating the Registration Page
------------------------------

Let's start by creating a new ``RegisterController`` class in UserBundle.
Creating a controller by hand is easy: just add the right namespace and
then extend Symfony's base controller class. The ``registerAction`` method
will be our actual registration page::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    namespace Yoda\UserBundle\Controller;

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    class RegisterController extends Controller
    {
        public function registerAction()
        {
            // todo
        }
    }

I'm kinda liking these annotation routes, so let's use those again. Remember,
to do this, we need two things. First, add the Route use statement and then
setup the route above the method::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    // ...
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

    class RegisterController extends Controller
    {
        /**
         * @Route("/register", name="user_register")
         */
        public function registerAction()
        {
            // todo
        }
    }

Second, we need to import the routes in ``routing.yml``. And hey! We're
already importing annotations from the entire ``Controller/`` directory, so
we're ready to go!

.. code-block:: yaml

    # app/config/routing.yml
    # ...

    user_routes:
        resource: "@UserBundle/Controller"
        type: annotation

To see if the route is being loaded, run the ``router:debug`` console task.

.. code-block:: bash

    php app/console router:debug

Yep, there it is!

Building the Form
-----------------

Now let's build a form, which is an object that knows all of the fields we
want and their types. It'll help us render the form and process its values.
It's pretty fancy!

Start by calling the ``createFormBuilder()`` method. Now, use the ``add``
function to give the form ``username``, ``email`` and ``password``
fields::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    public function registerAction()
    {
        $form = $this->createFormBuilder()
            ->add('username', 'text')
            ->add('email', 'text')
            ->add('password', 'password')
            ->getForm()
        ;

        // todo next - render a template
    }

The two arguments to ``add`` are the name of the field and the field "type".
Symfony comes with built-in types for creating text fields, select fields,
date fields and forcefields. I'll show you where to find a list in a minute.

When we're all done, we call ``getForm()``.

Passing the Form into Twig
--------------------------

I want to render the form, so let's pass it to Twig. To save and impress my
ewok friends, I'm going use the :ref:`@Template annotation trick<symfony-ep2-template-annotation>`
we saw earlier. Pass the form as the only variable::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    /**
     * @Route("/register", name="user_register")
     * @Template
     */
    public function registerAction()
    {
        $form = $this->createFormBuilder()
            // ...
            ->getForm()
        ;

        return array('form' => $form);
    }

.. tip::

    The above code has some bugs! Yuck! Keep reading below to fix them.

Fixing the Missing @Template Annotation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

So let's create the Twig template. Make a "Register" directory in ``Resources/views``
since we're rendering from ``RegisterController`` and ``@Template`` uses
that to figure out the template path. I'll paste in some HTML-goodness to
get us started:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {% extends '::base.html.twig' %}

    {% block stylesheets %}
        {{ parent() }}

        <link rel="stylesheet" href="{{ asset('bundles/user/css/login.css') }}" />
    {% endblock %}

    {% block body %}
    <section class="login">
        <article>
            <h1>Register</h1>

        </article>
    </section>
    {% endblock %}


.. tip::

    You can find this template code in the ``resources/episode2`` directory
    of the code download. Go get it!

So let's head to the browser to see how things look so far. When we go to
``/register``, we see a nice looking page. Kidding! We see a huge, horrible
threatening error!

>
AnnotationException: [SemanticalError] The annotation "@Template" in method
Yoda\UserBundle\Controller\RegisterController::registerAction() was never
imported. Did you maybe forget to add a "use" statement for this annotation?

.. tip::

    Sometimes errors are nested, and the most helpful parts are further below.

Look closely, the error contains the answer. Ah, I've used the ``@Template``
shortcut but forgot to put a ``use`` statement for it. After adding the namespace,
I can refresh and see the page::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    // ...
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
