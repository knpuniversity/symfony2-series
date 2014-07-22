Handling Form Submissions
=========================

Ok, let's get this form to actually submit! Since we're submitting back to
the same route and controller, we want to process things only if the request
is a POST.

Getting the Request object
--------------------------

First, we'll need Symfony's Request object. To get this in a controller,
add a ``$request`` argument that's type-hinted with Symfony's ``Request`` class::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    use Symfony\Component\HttpFoundation\Request;

    class RegisterController extends Controller
    {
        // ...
        public function registerAction(Request $request)
        {
            // ...
        }
    }

Normally, if you have an argument here, Symfony tries to populate it from
a routing wildcard with the same name as the variable. If it doesn't find 
one, it throws a giant error. The only exception to that rule is this: if
you type-hint an argument with the Request class, Symfony will give you that
object. This doesn't work for everything, only the Request class.

Using handleRequest
-------------------

Use the form's ``handleRequest`` method to actually process the data. Next,
add an ``if`` statement that checks to see if the form was submitted and
if all of the data is valid::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    use Symfony\Component\HttpFoundation\Request;
    // ...

    public function registerAction(Request $request)
    {
        $form = $this->createFormBuilder()
            // ...
            ->getForm()
        ;

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // do something in a moment
        }

        return array('form' => $form);
    }

The ``handleRequest`` method grabs the POST'ed data from the request, processes
it, and runs any validation. And actually, it *only* does this for POST requests
so on a GET request, ``$form->isSubmitted()`` returns false.

.. tip::

    If you have a form that's submitted via a different HTTP method, set
    the `method`_.

If the form *is* submitted because it's a POST request *and* passes validation,
let's just print the submitted data for now. If the form is invalid, or if
this is a GET request, it'll just skip this block and re-render the form
with errors if there are any::

    if ($form->isSubmitted() && $form->isValid()) {
        var_dump($form->getData());die;
    }

Time to test it! We haven't added validation yet, but the password fields
have built-in validation if the values don't match. When I submit, the form
is re-rendered, meaning there was an error.

In fact, there's now a little red box on the web debug toolbar. If we click
it, we can see details about the form: what was submitted and options for each
field.

Head back and fill in the form correctly. Now we see our dumped data:

.. code-block:: text

    array(
        'username' => string 'foo' (length=3),
        'email' => string 'foo@foo.com' (length=11),
        'password' => string 'foo' (length=3),
    )

Using the Submitted Data Array
------------------------------

Notice that the data is an array with a key and value for each field. Let's
take this data and build a new ``User`` object from it. There *is* an easier
way to do this, and I'll show you in a second::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    use Yoda\UserBundle\Entity\User;
    // ...

    $form->handleRequest($request);
    if ($form->isSubmitted() && $form->isValid()) {
        $data = $form->getData();

        $user = new User();
        $user->setUsername($data['username']);
        $user->setEmail($data['email']);
    }

Encoding the User's Password
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

We still need to encode and set the password. For now, let's copy in some
code from our user fixtures to help with this. We'll make this much more
awesome in the next screencast::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    private function encodePassword(User $user, $plainPassword)
    {
        $encoder = $this->container->get('security.encoder_factory')
            ->getEncoder($user)
        ;

        return $encoder->encodePassword($plainPassword, $user->getSalt());
    }

Use this function, then finally persist and flush the new User::

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

Redirecting after Success
-------------------------

The last step of any successful form submit is to redirect - we'll redirect
to the homepage. First, we need to generate a URL - just like we do with
the ``path()`` function in Twig. In a controller, there's a ``generateUrl``
function that works exactly the same way::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    if ($form->isSubmitted() && $form->isValid()) {
        // ...

        $em->flush();

        $url = $this->generateUrl('event');
    }

To redirect, use the ``redirect`` function and pass it the URL::

    if ($form->isSubmitted() && $form->isValid()) {
        // ...
        $url = $this->generateUrl('event');
        
        return $this->redirect($url);
    }

Remember that a controller always returns a Response object. ``redirect``
is just a shortcut to create a Response that's all setup to redirect to
this URL.

Ok, time to kick this proton torpedo! As expected, we end up on the homepage. We can
even login as the new user!

You Don't Need isSubmitted
--------------------------

Head back to the controller and remove the ``isSubmitted()`` call in the
``if`` statement::

    // src/Yoda/UserBundle/Controller/RegisterController.php

    $form->handleRequest($request);
    if ($form->isValid()) {
        // ...
    }

This actually doesn't change anything because ``isValid()`` automatically
returns false if the form wasn't submitted - meaning, if the request isn't
a POST. So either just do this, or keep the ``isSubmitted`` part in there
if you want - I find it adds some clarity.

.. _`method`: http://symfony.com/doc/current/reference/forms/types/form.html#method
