
# PHP Templating System - templates.php

`templates.php` is a simple templating system for PHP that allows you to separate application logic from presentation. It provides a way to work with HTML templates containing custom `<tmpl:xxx>` tags and `{variable}` placeholders. This system simplifies dynamic content generation, iteration handling, and template part insertion.

## Features
- **Separation of Logic and Presentation**: Keep code clean by separating PHP logic from HTML templates.
- **Dynamic Content Generation**: Replace placeholders with values, manage repeating structures with iterations.
- **Template Modularity**: Supports embedding additional templates into the main template for easy component reuse.
- **Conditional Display**: Show or hide template parts based on conditions in the code.

## Usage

### Commands for templates.php

1. **`tmpl_open($filename)`**: Loads a template from a file and creates a template object.
2. **`tmpl_close($t)`**: Closes the template object and frees resources.
3. **`tmpl_set($t, $path_or_key, $value)`**: Sets a value for a placeholder or tag in the template.
4. **`tmpl_set_array($t, $array)`**: Sets multiple values at once using an associative array.
5. **`tmpl_iterate($t, $path)`**: Enables or iterates over a specific tag in a template.
6. **`tmpl_parse($t, $path = null)`**: Parses the template and returns the resulting HTML.
7. **`tmpl_include($t, $path, $filename)`**: Inserts another template at a specified location in the current template.
8. **`tmpl_exists($t, $path)`**: Checks if a specific tag or path exists in the template.

### Usage Examples

#### Example 1: Inserting values into a template
```php
<?php
require_once 'templates.php';

$t = tmpl_open("example1.html");
tmpl_set($t, "title", "My Page");
tmpl_set($t, "header_text", "Welcome!");
tmpl_set($t, "content", "This is the content of the page.");
echo tmpl_parse($t);
tmpl_close($t);
?>
```

#### Example 2: Iterating over data
```php
<?php
require_once 'templates.php';

$t = tmpl_open("example2.html");
tmpl_set($t, "title", "Navigation");

$menuItems = [
    ['name' => 'Home', 'link' => '/home'],
    ['name' => 'About Us', 'link' => '/about'],
    ['name' => 'Contact', 'link' => '/contact']
];
tmpl_set_iarray($t, '/menu/item', $menuItems);
echo tmpl_parse($t);
tmpl_close($t);
?>
```

## Author and Contact
- **Author**: PB
- **Email**: pavel.bartos.pb@gmail.com
