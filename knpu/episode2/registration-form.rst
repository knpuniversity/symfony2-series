Registration Form
=================

Now that we've got users, let's create a true registration form. We're about
to touch on a lot of great concepts like forms and validation, so get ready!

Creating the Registration Page
------------------------------

Let's start by creating a new ``RegisterController`` class in our UserBundle.
Creating a controller by hand is easy: just add the proper namespace and
then extend Symfony's base controller class. The ``registerAction`` method
will be our actual registration page::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    class RegisterController extends Controller
    {
        public function registerAction()
        {
            // todo
        }
    }

To keep things simple, let's put the route right inside the controller. Remember,
to do this, we need two things. First, add the Route use statement::

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

Second, make sure that you're importing routes from the Controller directory
of the bundle. In this case, we already are, so we're ready to go.

Building the Form
~~~~~~~~~~~~~~~~~

Now let's see how forms work. The easiest way to create a form is right inside
your controller by calling the ``createFormBuilder()`` method. Building a
form is like creating a recipe where you say which fields you need and what
"type" each field is. Symfony comes with built-in types for creating text
fields, select fields, date fields and lots more.

For our recipe, we'll need a ``username`` field that's a ``text`` type, an
``email`` field that's a ``text`` type, and a ``password`` field that's a
``password`` type. To finish the form, we call ``getForm()``::

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

Let's pass the finished form into a template so we can render it. To save
time, I'm going use the :ref:`@Template annotation trick<symfony-ep2-template-annotation>`
we saw earlier passing the form as the only variable::

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

    The above code contains a few errors. Keep reading below to discover them.

Fixing the Missing @Template Annotation
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Next, create the corresponding template. Create a "Register" directory since
we are rendering from ``RegisterController``. I'll copy in some structure
to get us started:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {% extends '::base.html.twig' %}

    {% block body %}
    <section class="main-block">
        <article>
            <section>
                <h1>Register</h1>

                {# render the form in a moment ... #}
            </section>
        </article>
    </section>
    {% endblock %}

Go to your browser to see if things are working so far. When you go to ``/register``,
you should see an error!

>
AnnotationException: [SemanticalError] The annotation "@Template" in method
Yoda\UserBundle\Controller\RegisterController::registerAction() was never
imported. Did you maybe forget to add a "use" statement for this annotation?

.. tip::

    Sometimes errors are nested, and the most helpful parts are further below.

Look closely, this error will point you to what is wrong in your code. In
this case, I've used the ``@Template`` shortcut but forgot to import its namespace.
After adding the namespace, I can refresh and see the page::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    // ...
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

Very Basic Form Rendering
~~~~~~~~~~~~~~~~~~~~~~~~~

To render the form, start with a ``form`` tag that submits right back to the
same route and controller we just created. The easiest way to render a form is
all at once by using a special ``form_widget`` Twig function. Give it
the form variable that we passed into the template, and add a submit button:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {% block body %}
    <section class="main-block">
        <article>
            <section>
                <h1>Register</h1>

                <form action="{{ path('user_register') }}" method="POST">
                    {{ form_widget(form) }}

                    <input type="submit" value="Register!" />
                </form>
            </section>
        </article>
    </section>
    {% endblock %}

Form versus FormView
~~~~~~~~~~~~~~~~~~~~

Refresh the page. Oh man, another error!

>
An exception has been thrown during the rendering of a template ("Catchable Fatal
Error: Argument 1 passed to Symfony\Component\Form\FormRenderer::searchAndRenderBlock()
must be an instance of Symfony\Component\Form\FormView, instance of Symfony\Component\Form\Form
given, called in ...")

This one is more difficult to track down, but it contains one important phrase
you should learn to look for. Specifically:

>
Argument 1 passed to FormRenderer::searchAndRenderBlock() must be an instance
of FormView, instance of Form given

This is a mouthful, but it means that somewhere, we're calling a method and
passing it the wrong type of object. It's expecting a`` FormView``, but we're
passing it a ``Form``. Something is obviously wrong with the form we created.
Head back to ``RegisterController`` to fix this. Whenever you pass a form
to a template, you *must* call ``createView`` on it. This transforms the object
from a Form into a ``FormView``::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    public function registerAction()
    {
        // ...

        return array('form' => $form->createView());
    }

This isn't really very important, but I wanted you to see the error, because
someday, you'll forget to call this and see it.

Refresh the page once again. You should see a fully-rendered form. Each field
has a label, an input, and validation errors if there are any. Awesome!

Rendering the Form one Field at a time
--------------------------------------

Rendering the form all at once is great for trying things out, but usually
too rigid for a real project. To render each field individually, use the
``form_row`` function on each of your fields:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    <form action="{{ path('user_register') }}" method="POST">
        {{ form_row(form.username) }}
        {{ form_row(form.email) }}
        {{ form_row(form.password) }}

        <input type="submit" value="Register!" />
    </form>

Refresh the page and inspect the form. Each field row is surrounded by a ``div``
and contains the label and input:

.. code-block:: html

    <div>
        <label for="form_username" class="required">Username</label>
        <input type="text" id="form_username" name="form[username]" required="required" />
    </div>
    <!-- ... -->

Using form_widget, form_label and form_errors
---------------------------------------------

In the next screencast, we'll learn how to customize how a field row is rendered.
But even now, we can take more control by using the ``form_label``, ``form_widget``
and ``form_errors`` functions individually. Pass each just the one field you 
want to render:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    <form action="{{ path('user_register') }}" method="POST">
        <div class="awesome-username-wrapper">
            {{ form_errors(form.username) }}
            {{ form_label(form.username) }}
            {{ form_widget(form.username) }}
        </div>

        {{ form_row(form.email) }}
        {{ form_row(form.password) }}

        <input type="submit" value="Register!" />
    </form>

.. note::

    ``form_row`` is just a shortcut that calls ``form_label``, ``form_errors``,
    and ``form_widget`` and puts it all in a ``div`` (by default).

Refresh to check that this works the same as before, except with an extra
class on the div. I usually try to use ``form_row`` whenever possible.

Don't forget form_errors and form_rest!
---------------------------------------

Apart from the fields themselves, there are two other things that should be
in every form. First, calling ``form_errors`` on the entire form object makes
sure that any "global" validation errors are rendered:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    <form action="{{ path('user_register') }}" method="POST">
        {{ form_errors(form) }}

        {{ form_row(form.username) }}
        {{ form_row(form.email) }}
        {{ form_row(form.password) }}

        <input type="submit" value="Register!" />
    </form>

Most errors will appear right next to the field they belong to. But in some cases,
you might have a "global" error that doesn't apply to any one specific field.
This line takes care of that. Next, ``form_rest`` will render any fields that
you forgotten. In addition to rendering any forgotten fields, ``form_rest``
is really handy because it renders all of your hidden fields automatically:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    <form action="{{ path('user_register') }}" method="POST">
        {{ form_errors(form) }}

        {{ form_row(form.username) }}
        {{ form_row(form.email) }}
        {{ form_row(form.password) }}

        {{ form_rest(form) }}

        <input type="submit" value="Register!" />
    </form>

In fact, all forms have a hidden "token" field by default to protect against
CSRF attacks. With `form_rest`, you never have to worry or think about hidden
fields.

Using More Fields: email and repeated
-------------------------------------

Now that we have our form rendering nicely, let's complicate things! In our
controller, change the ``email`` field to be an ``email`` type and the ``password``
to be a ``repeated`` type::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    public function registerAction()
    {
        $form = $this->createFormBuilder()
            ->add('username', 'text')
            ->add('email', 'email')
            ->add('password', 'repeated', array(
                'type' => 'password',
            ))
            ->getForm()
        ;

        // ..
    }

The ``repeated`` field type is a special type that renders two identical fields,
in this case, password fields. If the two values don't match, the user sees
a validation error.

Refresh the page to see that the password has actually split into two separate
fields. The email looks normal, but if you inspect it, you'll see that it's
an input ``email`` field, a new HTML5 field type that should be used for emails:

.. code-block:: text

    <input type="email" ... />

If you're not familiar with HTML5, don't worry. This field works just like
a normal input text field and is fully backwards compatible with all browsers.

The Repeated Fields and "Compound" fields
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Next, look at the password. This highlights a very special aspect about the
way that the forms work. A single field in a form may in fact be one or *many*
fields:

.. code-block:: text

    <div>
        <!-- -->
        <input type="password" id="form_password_first" name="form[password][first]" required="required" />
    </div>

    <div>
        <!-- -->
        <input type="password" id="form_password_second" name="form[password][second]" required="required" />
    </div>

When you use the `repeated field type`_, it creates two sub-fields called "first"
and "second". To see what I'm talking about, replace the `form_row` that renders
the ``password`` field with two lines: one that renders the first box and one
that renders the second:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {{ form_row(form.username) }}
    {{ form_row(form.email) }}
    {{ form_row(form.password.first) }}
    {{ form_row(form.password.second) }}

    {# ... #}

.. note::

    When a field is actually a collection of several fields, it's called
    a compound field.

If you refresh, you'll see that this works exactly like before. If this sounds
confusing, don't worry! This concept is a little bit more advanced, but it's
important to be aware of.

Customizing Field Labels
~~~~~~~~~~~~~~~~~~~~~~~~

Finally, let's customize the labels on the two password fields, since "first"
and "second" are, well, terrible labels. One way to do this is by adding a
second argument to ``form_row`` and passing in the ``label`` key:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {{ form_row(form.password.first, {
        'label': 'Password'
    }) }}

    {{ form_row(form.password.second, {
        'label': 'Repeat Password'
    }) }}

Refresh to see that this works.

Handling Form Submissions
-------------------------

Enough with form rendering! Let's setup the form to submit. In our controller,
we'll process the form if the current request is a POST. To get easy access
to the request object, add it as an argument to the action and type-hint
it correctly. Create an ``if`` statement to handle the form processing and
use the form's ``bind`` method::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    use Symfony\Component\HttpFoundation\Request;
    // ...

    public function registerAction(Request $request)
    {
        $form = $this->createFormBuilder()
            // ...
            ->getForm()
        ;

        if ($request->isMethod('POST')) {
            $form->bind($request);

            // do something in a moment...
        }

        return array('form' => $form);
    }

.. tip::

    If any argument to your controller has the :symfonyclass:`Symfony\\Component\\HttpFoundation\\Request`
    type hint, Symfony will pass in the request object to it.

This method grabs the correct POST'ed data from the request and binds it to
the form. Let's check if the form is valid and, for now, print out the submitted
data. If the form is invalid, it should skip this block and re-render the
form with its errors::

    if ($request->isMethod('POST')) {
        $form->bind($request);

        if ($form->isValid()) {
            var_dump($form->getData());die;
        }
    }

Let's try it! We haven't added any validation yet, but the password fields
should throw an error if the values don't match. When I submit, the form
is re-rendered, meaning there was an error.

And when we fill in the form completely, we see our dumped data:

.. code-block:: text

    array(
        'username' => string 'foo' (length=3),
        'email' => string 'foo@foo.com' (length=11),
        'password' => string 'foo' (length=3),
    )

Notice that the data is an array with a value for each field of our form.
Let's take this data and build a new ``User`` object from it. In a moment,
we'll see an easier way to do this, but for now, there's nothing wrong with
creating the new ``User`` object by hand::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    use Yoda\UserBundle\Entity\User;
    // ...

    if ($request->isMethod('POST')) {
        $form->bind($request);

        if ($form->isValid()) {
            $data = $form->getData();

            $user = new User();
            $user->setUsername($data['username']);
            $user->setEmail($data['email']);
        }
    }

Let's copy in some code from our user fixtures to help us encode the password
correctly. We'll fix this in a much more awesome way in the next screencast::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    private function encodePassword($user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user)
        ;

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }

Finally, persist and flush the new user to the database::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    if ($form->isValid()) {
        $data = $form->getData();

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
        $user->setPassword($this->encodePassword($user, $data['password']));

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        // we'll redirect the user next...
    }

After registration, let's redirect the user to the homepage. To do this, first
generate the URL to the homepage by calling the ``generateUrl`` method. This
takes the exact same arguments as the Twig ``path`` function. Now, to actually
redirect, use the ``redirect`` method and pass it the URL::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    if ($form->isValid()) {
        // ... all the stuff from above

        $em->flush();

        $url = $this->generate('event');

        return $this->redirect($url);
    }

Great, let's try it! As expected, we end up on the homepage. We can prove
that things really worked by logging in as the new user.

Form: Default Data
------------------

Back on the registration form, what if we wanted some default data to appear? 
We can just pass the data as the first argument when we create the form::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    public function registerAction(Request $request)
    {
        $defaultData = array(
            'username' => 'Foo',
        );

        $form = $this->createFormBuilder($defaultData)
            // ...
            ->getForm()
        ;

        // ...
    }

Binding the Form to a User object: The data_class Option
--------------------------------------------------------

Having a form that returns an array of data is great, but what if we could
make a form that automatically built the ``User`` object for us? Clear out
the default data we just added and add a second array. This is an array of options
that we're passing into the form. By passing it a ``data_class`` option,
our bound form will return a ``User`` object instead of an array::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    public function registerAction(Request $request)
    {
        $form = $this->createFormBuilder(null, array(
            'data_class' => 'Yoda\UserBundle\Entity\User',
        ))
            // ...
            ->getForm()
        ;

        // ...
    }

Let's dump the form values again and try it::

    // inside registerAction()
    if ($form->isValid()) {
        var_dump($form->getData());die;

        // all the User saving code from before ...
    }

Great! As expected, we get back a full ``User`` object populated with the
data. Behind the scenes, the form component creates a new ``User`` object
and then calls ``setUsername``, ``setEmail`` and ``setPassword`` on it, passing
each the value from the form. We can now simplify things on our controller::

    // inside registerAction()
    if ($form->isValid()) {
        $user = $form->getData();

        $user->setPassword(
            $this->encodePassword($user, $user->getPassword())
        );

        $em = $this->getDoctrine()->getManager();
        // save the user and redirect just as before
    }

But what about default data? Let's put back the array we had earlier::

    $defaultData = array(
        'username' => 'Foo',
    );

    $form = $this->createFormBuilder($defaultData, array(
        'data_class' => 'Yoda\UserBundle\Entity\User',
    ))
        // ...
        ->getForm()
    ;

Look at the error message closely:

>
The form's view data is expected to be an instance of class Yoda\UserBundle\Entity\User,
but is a(n) array. You can avoid this error by setting the "data_class" option
to null or by adding a view transformer that transforms a(n) array to an
instance of Yoda\UserBundle\Entity\User.

It's telling us that we gave it an array but it was expecting a ``User`` object.
By using the ``data_class`` option, we're telling the form that both the
output *and* the input of the form should be a ``User``. To set default data,
just create a ``User`` object, give it some data and pass it in::

    $defaultUser = new User();
    $defaultUser->setUsername('Foo');

    $form = $this->createFormBuilder($defaultUser, array(
        'data_class' => 'Yoda\UserBundle\Entity\User',
    ))
        // ...
        ->getForm()
    ;

Cleaning up with a plainPassword Field
--------------------------------------

One strange thing is that we're using our password field both to temporarily
store the plain-text password as well as the encoded password later. This
is a bad idea. What if we forget to encode a user's password? In this case,
the plain-text password would be saved to the database instead of throwing
an error.

A better practice is to create a new property on the ``User`` entity called
``plainPassword``::

    private $plainPassword;

    // ...

    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    public function setPlainPassword($plainPassword)
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

This property is just like the others, except that it's not actually persisted
to the database. It exists just as a temporary place to store data. Find the
``eraseCredentials`` method and clear out the ``plainPassword`` field::

    public function eraseCredentials()
    {
        $this->setPlainPassword(null);
    }

This method isn't particularly important, but it's called during the authentication
process and it's purpose is to make sure your User doesn't have any sensitive
data on it. I've also generated a getter and a setter for the new field.

Let's update our form code - changing "password" to "plainPassword"::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    public function registerAction(Request $request)
    {
        // ...
        $form = $this->createFormBuilder(...)
            // ...
            ->add('plainPassword', 'repeated', array(
                'type' => 'password',
            ))
            ->getForm()
        ;

        // ...
    }

Also don't forget to update the template:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}

    {{ form_row(form.plainPassword.first, {
        'label': 'Password'
    }) }}

    {{ form_row(form.plainPassword.second, {
        'label': 'Repeat Password'
    }) }}

When the form submits, ``plainPassword`` is populated. We can use it to set
the real, encoded ``password`` value::

    // inside registerAction()
    $user->setPassword(
        $this->encodePassword($user, $user->getPlainPassword())
    );

Refactoring to an External Form Type Class
------------------------------------------

Let's make one more change. Building a form right inside our controller is
simple and fun, but not easily reusable. Instead, let's move this logic to
an external class. Create a new ``Form`` directory and a new file called ``RegisterFormType``.
This is a form class. Give it the proper namespace and make it extend a class
called :symfonyclass:`Symfony\\Component\\Form\\AbstractType`::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    namespace Yoda\UserBundle\Form;

    use Symfony\Component\Form\AbstractType;

    class RegisterFormType extends AbstractType
    {
    }

A form class can have several methods. The first, but probably least important,
is :symfonymethod:`Symfony\\Component\\Form\\FormTypeInterface::getName`. This
should just return a string that's unique among your forms. It'll be used
in the name of the fields::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    public function getName()
    {
        return 'user_register';
    }

Next, the most important method is :symfonymethod:`Symfony\\Component\\Form\\FormTypeInterface::buildForm`.
When you create this method, don't forget the ``use`` statement for the
:symfonyclass:`Symfony\\Component\\Form\\FormBuilderInterface`. This
is where we build our fields, and we can basically copy in the code form our
controller and put it here::

    // src/Yoda/UserBundle/Form/RegisterFormType.php

    use Symfony\Component\Form\FormBuilderInterface;
    // ...

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', 'text')
            ->add('email', 'email')
            ->add('plainPassword', 'repeated', array(
                'type' => 'password'
            )
        );
    }

Finally, create a ``setDefaultOptions`` function. Use this to return an array
with the ``data_class`` option::

    // src/Yoda/UserBundle/Form/RegisterFormType.php

    use Symfony\Component\OptionsResolver\OptionsResolverInterface;
    // ...

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Yoda\UserBundle\Entity\User',
        ));
    }

And that's it! Remove the form code in our controller. We can replace it
by calling ``createForm`` and passing it an instance of our new ``RegisterFormType``::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    
    use Yoda\UserBundle\Form\RegisterFormType;
    // ...

    public function registerAction(Request $request)
    {
        $defaultUser = new User();
        $defaultUser->setUsername('Foo');

        $form = $this->createForm(new RegisterFormType(), $defaultUser);

        // ...
    }

Let's refresh the page to make sure that everything went ok. Great!

Forms: From Space
-----------------

Let's review. A form is something you build, giving it the fields and field
types you need. By default, a form just returns an array of data, but we can
change that with the ``data_class`` option to return a populated object. Forms
can also be built right inside the controller or in an external class. Got
it? Great! Let's move on to validation.

.. _`repeated field type`: http://symfony.com/doc/current/reference/forms/types/repeated.html

