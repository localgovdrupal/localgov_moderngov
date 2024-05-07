Drupal module for [Modern.Gov](http://www.civica.com/moderngov) template page generation.

Modern.Gov, when provided with a template page URL, fetches that template page at a regular interval.  It then uses the template page to generate your instance of the Modern.Gov site.

## Features
This module serves the Modern.Gov template page from the /moderngov-template path.  If you need a different path then please add a URL alias for /moderngov-template.

The template page has the the following ModernGov tokens embedded within:
- ```{pagetitle}``` instead of the actual Drupal page title.
- ```{breadcrumb}``` instead of the Drupal breadcrumb.
- ```{content}``` instead of the page content.
- ```{sidenav}``` instead of the second sidebar.

Both links and asset URLs in this Modern.Gov template page are rendered as **absolute** URLs which is a requirement for Modern.Gov template pages.

## Page template
Most sites will need a customized Modern.Gov page template for their themes.  The page template provided with this module is meant to serve as an example of how Modern.Gov tokens could be placed in a page template.  

## Good to know
- [ModernGov test URL](https://reversecms.moderngov.co.uk/).  This sits behind HTTP authentication.
- Any relative URL embedded within inline Javascript or script tags will *not* be converted to absolute URLs.
- [BigPipe](https://www.drupal.org/docs/8/core/modules/big-pipe/overview) functionality has been turned off.  This is because this module is concerned about serving templates for anonymous users.  This can be reviewed later if necessary.

## Related templates
- Empty page template at /moderngov-template?nocontent: Instead of ModernGov tokens, this template comes with an empty `main` HTML tag.
- Header template at /moderngov-template?header: Everything inside the `header` HTML tag.  Comes with asset links that preceed the `header` tag.
- Footer template at /moderngov-template?footer: Everything inside the `footer` HTML tag.  Joined by asset links that follow the `footer` tag.
