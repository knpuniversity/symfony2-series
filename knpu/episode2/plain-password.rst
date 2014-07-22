Cleaning up with a plainPassword Field
======================================

We're abusing our ``password`` field. It temporarily stores the plain text
submitted password and then later stores the encoded version. This is a bad
idea. What if we forget to encode a user's password? The plain-text password
would be saved to the database instead of throwing an error. And storing
plain text passwords is definitely against the Jedi Code!

Instead, create a new property on the ``User`` entity called ``plainPassword``.
Let's also add the getter and setter method for it::

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
to the database. It exists just as a temporary place to store data.

Using eraseCredentials
----------------------

Find the ``eraseCredentials`` method and clear out the ``plainPassword``
field::

    public function eraseCredentials()
    {
        $this->setPlainPassword(null);
    }

This method isn't really important, but it's called during the authentication
process and its purpose is to make sure your User doesn't have any sensitive
data on it.

Using plainPassword
-------------------

Now, update the form code - changing the field name from ``password`` to
``plainPassword``::

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

Now, when the form submits, the ``plainPassword`` is populated on the User.
Use it to set the real, encoded ``password`` property::

    // inside registerAction()
    $user->setPassword(
        $this->encodePassword($user, $user->getPlainPassword())
    );

Let's try it out! I'll register as a new user and then try to login. Once
again, things work perfectly!
