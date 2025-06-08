# Customizing E-Mail messages

The templates for the e-mail messages sent to the teams after registration are located in [`app/Presenters/templates/Mail/` directory](../app/Presenters/templates/Mail/) so one can easily modify them. But often, one wants to change just a single section, e.g. payment instructions, and modifying the whole file is not very convenient for that. Also, it makes updating the entries for the next year’s event awkward due to having to manually merge possible changes.

If you look at the [default e-mail template](../app/Presenters/templates/Mail/verification.latte), you will see [`block` tags](https://latte.nette.org/en/template-inheritance#toc-blocks) that will allow you to replace specific sections of the message.

If you create a `verification.$lang.latte` file in the `app/Config/mail/` directory with the following contents, you will change just the greeting and the payment instructions, while keeping the rest of the message body for the e-mail in language `$lang`:

```latte
{layout $layout}

{block greeting}
<p>¡Hola!</p>
{/block}

{block payment}
<p>Please pay <strong>{$invoice->getTotal()|price}</strong> at the registration desk.</p>
{/block}
```

You can also override the `body` block to change the whole message body (while keeping the entry details and headings), or even use `{layout none}` to replace the template completely.

When an override for a given language does not exist, the system will also try `verification.latte` file.

The templates are written in [Latte templating language](https://latte.nette.org/en/syntax) and the available variables are defined [in the Team presenter](../app/Presenters/TeamPresenter.php) (search for “variables for use in the e-mail template”).
