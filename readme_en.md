
# PHP Templating System - templates.php

`templates.php` is a simple PHP templating system that separates application logic from presentation. The system works with HTML templates containing custom `<tmpl:xxx>` tags and `{variable}` placeholders. It enables dynamic content generation, iteration management, and embedding additional templates into the main template.

## Features and Usage

### Main functions of templates.php library

1. **`tmpl_open($filename)`**
   - Loads a template from a file and returns a template object for further manipulation.
   - Example:
     ```php
     <?php
     $t = tmpl_open("example.html");
     ?>
     ```

2. **`tmpl_close($t)`**
   - Closes the template object and frees resources.
   - Usage:
     ```php
     <?php
     tmpl_close($t);
     ?>
     ```

3. **`tmpl_set($t, $path_or_key, $value)`**
   - Sets a value for a `{variable}` placeholder or tag in the template.
   - Example:
     ```php
     <?php
     tmpl_set($t, "title", "My Page");
     tmpl_set($t, "header_text", "Welcome!");
     ?>
     ```

4. **`tmpl_set_array($t, $array)`**
   - Sets multiple values at once using an associative array.
   - Example:
     ```php
     <?php
     $data = [
         "title" => "My Page",
         "header_text" => "Welcome!",
         "content" => "This is the page content."
     ];
     tmpl_set_array($t, $data);
     ?>
     ```

5. **`tmpl_iterate($t, $path)`**
   - Enables iteration over a specific tag in the template.
   - Example:
     ```php
     <?php
     tmpl_iterate($t, "menu_items");
     ?>
     ```

6. **`tmpl_parse($t, $path = null)`**
   - Parses the template and returns the resulting HTML.
   - Example:
     ```php
     <?php
     echo tmpl_parse($t);
     ?>
     ```

7. **`tmpl_include($t, $path, $filename)`**
   - Embeds another template at the specified location in the current template.
   - Example:
     ```php
     <?php
     tmpl_include($t, "header", "header_template.html");
     ?>
     ```

8. **`tmpl_exists($t, $path)`**
   - Checks if a specific tag or path exists in the template.
   - Example:
     ```php
     <?php
     if (tmpl_exists($t, "footer")) {
         tmpl_set($t, "footer", "Footer exists!");
     }
     ?>
     ```

## Usage Examples

### Example 1: Basic Template and Value Insertion
#### HTML Template - example.html
```html
<html>
<head><title>{title}</title></head>
<body>
    <h1>{header_text}</h1>
    <p>{content}</p>
</body>
</html>
```
#### PHP Script
```php
<?php
require_once 'templates.php';
$t = tmpl_open("example.html");

tmpl_set($t, "title", "My Page");
tmpl_set($t, "header_text", "Welcome!");
tmpl_set($t, "content", "This is the content of the page.");

echo tmpl_parse($t);
tmpl_close($t);
?>
```

### Example 2: Iteration and Embedding Additional Templates
#### HTML Template - menu.html
```html
<ul>
    <tmpl:menu_items>
        <li><a href="{link}">{name}</a></li>
    </tmpl:menu_items>
</ul>
```
#### PHP Script
```php
<?php
$t = tmpl_open("menu.html");
$menu_items = [
    ["name" => "Home", "link" => "/home"],
    ["name" => "About Us", "link" => "/about"],
    ["name" => "Contact", "link" => "/contact"]
];
tmpl_set_iarray($t, "menu_items", $menu_items);
echo tmpl_parse($t);
tmpl_close($t);
?>
```

## Author and Contact
- **Author**: PB
- **Email**: pavel.bartos.pb@gmail.com
