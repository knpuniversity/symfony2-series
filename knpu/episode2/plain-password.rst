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
