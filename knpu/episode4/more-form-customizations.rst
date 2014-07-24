More Form Customizations
========================

Our registration form looks great, except for the ``plainPassword`` field.
The error shows up nicely, but if the 2 values don't match, it only highlights
the first field red. This is fine, but it would be better if both fields were
highlighted.

We know enough about forms now that we can just fix this right in our template.
Surround everything with a ``control-group`` div. Next, give it a conditional
``error`` class just like we did in our form template:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}
    
    <div class="control-group{{ form.plainPassword.first.vars.errors|length > 0 ? ' error' : '' }}">
        {{ form_row(form.plainPassword.first, {
            'label': 'Password'
        }) }}
        {{ form_row(form.plainPassword.second, {
            'label': 'Repeat Password'
        }) }}
    </div>

To get the same ``errors`` variable that's available to us when we're customizing
a form theme block, we access a ``vars`` array property on the field itself.
This is somewhat advanced, but the idea is simple: each field in a form has
a number of variables attached to them. You can get these variables at any
time via the ``vars`` property. When you're customizing a form block, all
of these variables are just automatically made available.

Also notice that the error itself is attached to the ``first`` sub-field.
This is just the way the ``repeated`` field type works - every error is attached
to exactly one field, and for ``repeated`` fields, the first field makes the
most sense.

Now refresh. It looks better already!

Customizing the form_row for repeated Fields
--------------------------------------------

This is a great solution because it works and was quick. But if we had a lot
of repeated fields on our site, we might want to fix this globally. Change
the template back to simply call ``form_row`` on the entire ``plainPassword``
field:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}
    
    {{ form_row(form.plainPassword) }}

When we refresh, it looks bad again, Let's fix that!

In this case, we want to customize the ``row`` part of a ``repeated`` field.
To do that, our target block is ``repeated_row``.

    +------------+------------+-----------------+
    | Field type | Which part | Block name      |
    +------------+------------+-----------------+
    | textarea   | widget     | textarea_widget |
    +------------+------------+-----------------+
    | textarea   | errors     | form_errors     |
    +------------+------------+-----------------+
    | repeated   | row        | repeated_row    |
    +------------+------------+-----------------+

If we search in Symfony's base template, we find it, and it actually just
renders another block called ``form_rows``. This is used whenever you render
a group of fields at once.

Like always, start by copying the ``repeated_row`` block into our custom template:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block repeated_row %}
    {% spaceless %}
        {{ block('form_rows') }}
    {% endspaceless %}
    {% endblock repeated_row %}

I'll paste the code we need in here, which should mostly look familiar:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block repeated_row %}
        <div class="control-group{{ form.first.vars.errors|length > 0 ? ' error' : '' }}">
            {{ form_row(form.first) }}
            {{ form_row(form.second) }}
        </div>
    {% endblock repeated_row %}

Like before, we surround everything with a ``control-group`` div with an
optional error class.

Using new Form Variables to Extend the repeated Functionality
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Try it out! When the repeated field renders, it uses our new block, which
wraps it in the markup we want and with the error class when validation fails.
The only problem is that we've lost the custom labels we were using for each
of the individual fields.

To fix this temporarily, remember that you can pass a ``label`` option to
``form_row``. Do this to customize each of the underlying fields:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block repeated_row %}
        <div class="control-group{{ form.first.vars.errors|length > 0 ? ' error' : '' }}">
            {{ form_row(form.first, {'label': 'Password'}) }}
            {{ form_row(form.second, {'label': 'Repeat Password') }}
        </div>
    {% endblock repeated_row %}

This works, of course, but hardcoding these labels here won't work later if
we use the ``repeated`` field type for something else, like an email address.

Instead, let's invent our own solution! In our registration template, it would
be really nice if we could pass a ``firstLabel`` and ``secondLabel`` option
to the repeated field's ``form_row`` function:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}
    
    {{ form_row(form.plainPassword, {
        'firstLabel': 'Password',
        'secondLabel': 'Repeat Password
    }) }}

Unfortunately, there's no functionality inside the ``repeated`` field type
for this. But, now that we're passing in these variables, they're actually
available inside the ``repeated_row`` block. We can see this by echoing each
of them:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block repeated_row %}
        {{ firstLabel}}, {{ secondLabel }}
    
        {# ... #}
    {% endblock repeated_row %}

And now that we have these variables, we can pass each as the ``label`` option
to the right ``form_row``:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block repeated_row %}
        <div class="control-group{{ form.first.vars.errors|length > 0 ? ' error' : '' }}">
            {{ form_row(form.first, {'label': firstLabel}) }}
            {{ form_row(form.second, {'label': secondLabel) }}
        </div>
    {% endblock repeated_row %}

And just like that, we've made the ``repeated`` field type more flexible!
We've also highlighted the fact that you can control and modify all of the
variables that are passed into these blocks. If you want to modify the ``attr``
we saw earlier, you can do that easily.

Using the default Filter to protect agains undefined Variables
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

To make the ``repeated`` field more fault-tolerant, add the ``default`` filter:

.. code-block:: html+jinja

    {# app/Resources/views/forms.html.twig #}
    {# ... #}

    {% block repeated_row %}
        <div class="control-group{{ form.first.vars.errors|length > 0 ? ' error' : '' }}">
            {{ form_row(form.first, {'label': firstLabel|default}) }}
            {{ form_row(form.second, {'label': secondLabel|default) }}
        </div>
    {% endblock repeated_row %}

If the ``firstLabel`` or ``secondLabel`` options *aren't* passed in when rendering
the field, the ``default`` filter prevents an error from being thrown and
gives these both a default blank value.

FormView: Customizing Form Variables from your Form Type
--------------------------------------------------------

Quickly, let's learn just a little bit more about these variables that are
available when rendering a field. Right before we pass a form to a controller,
we always call ``createView`` on it::

    // src/Yoda/UserBundle/Controller/RegisterController.php
    // ...

    public function registerAction(Request $request)
    {
        // ...

        // We're using the @Template annotation to render the template
        return array('form' => $form->createView());
    }

This changes the ``Form`` object into a :symfonyclass:``Symfony\\Component\\Form\\FormView``
object. That's not terribly important, except to realize that you're always
working with a ``FormView`` object when you're in a template.

Open up the ``RegisterFormType``. We created this class earlier, and it basically
just defines the fields of our form. But, we can also customize the ``FormView``
object that's passed into the template

To see what I mean, remove the ``firstLabel`` and ``secondLabel`` options
when rendering our field:

.. code-block:: html+jinja

    {# src/Yoda/UserBundle/Resources/views/Register/register.html.twig #}
    {# ... #}
    
    {{ form_row(form.plainPassword, {}) }}

As expected, without these, the form just uses the default labels. Next, create
a ``finishView`` method in your form type::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...
    
    use Symfony\Component\Form\FormView;
    use Symfony\Component\Form\FormInterface;
    // ...

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        // todo
    }


This method is executed when you call ``createView()`` on your form object,
and it gives you an opportunity to modify the ``FormView`` that's being created.

In our case, we can add the ``firstLabel`` and ``secondLabel`` variables to
the ``plainPassword`` field *right* here::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view['plainPassword']->vars['firstLabel'] = 'Password';
        $view['plainPassword']->vars['secondLabel'] = 'Repeat Password';
    }

This has the same effect as passing them in when rendering the field: both
become available in the field blocks. This is really handy, because we can
modify any of the FormView objects here, like customizing the label of the
"username" field, for example::

    // src/Yoda/UserBundle/Form/RegisterFormType.php
    // ...

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $view['username']->vars['firstLabel'] = 'Your Username';

        $view['plainPassword']->vars['firstLabel'] = 'Password';
        $view['plainPassword']->vars['secondLabel'] = 'Repeat Password';
    }

Refresh the page to prove it's working. The point is this: when a field is
rendered, it uses a group of variables like ``label``, ``attr``, ``name``
and more. These variables can be modified either when you're rendering the
field *or* directly on the ``FormView`` object when you're building the form.
The power is yours!

.. tip::

    Most of the built-in form view variables come from the ``FormType::buildView``
    method: http://bit.ly/sf2-form-build-view
