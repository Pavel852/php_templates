       
       
       
       
       
       Manual for templates.php
           
           Introduction to the tmpl system
templates.php is a simple templating system for PHP that allows you to separate the logic of the application from its presentation. It provides an easy way to work with HTML templates containing custom <tmpl:xxx> tags and {promenna} placeholders . The system allows you to dynamically generate content, iterate over data, insert parts of templates and manage complex HTML structures without mixing with PHP code.
           Possibilities of use
    • Separation of logic and presentation: Allows you to maintain clean code by separating PHP logic from HTML templates.
    • Dynamic content generation: Easily replaces placeholders with values and manages repeating structures using iterations.
    • Template Modularity: Supports embedding additional templates into the main template, making it easy to reuse components.
    • Conditional Display: Allows you to show or hide parts of the template based on conditions in the code.
    • Better Readability: Simplifies code and improves readability by reducing the amount of PHP code in HTML.
           
           Description of tmpl_ commands
               tmpl_open($filename)
Description: Loads a template from a file and creates a template object.
Use:
php
Copy the code
$t = tmpl_open("template.html");
               tmpl_close($t)
Description: Closes the template object and frees the resources.
Use:
php
Copy the code
tmpl_close($t);
               tmpl_set($t, $path_or_key, $value)
Description: Sets the value for a placeholder or tag in the template.
Use:
php
Copy the code
tmpl_set($t, "title", "My Page");
tmpl_set($t, "/menu/item/name", "Home");
               tmpl_set_array($t, $array)
Description: Sets multiple values at once using an associative array.
Use:
php
Copy the code
$data = ['title' => 'Welcome', 'description' => 'Page description'];
tmpl_set_array($t, $data);
               tmpl_iterate($t, $path)
Description: Enable or iterate over a specific tag in a template.
Use:
php
Copy the code
tmpl_iterate($t, "/menu/item");
               tmpl_set_iarray($t, $path, $array)
Description: Sets the data array to iterate through the tag.
Use:
php
Copy the code
$items = [
    ['name' => 'Home', 'link' => '/home'],
    ['name' => 'Contact', 'link' => '/contact']
];
tmpl_set_iarray($t, '/menu/item', $items);
               tmpl_parse($t, $path = null)
Description: Parses the template and returns the resulting HTML. If a path is given, it only parses the specific part.
Use:
php
Copy the code
echo tmpl_parse($t);
echo tmpl_parse($t, '/header');
               tmpl_debug($t)
Description: Displays information about the processing of the template, structure, non-displayed tags and placeholders.
Use:
php
Copy the code
tmpl_debug($t);
               tmpl_get_tags($t)
Description: Gets a list of all <tmpl:xxx> tags in the template with their paths.
Use:
php
Copy the code
$tags = tmpl_get_tags($t);
               tmpl_version()
Description: Returns the version of the templating system.
Use:
php
Copy the code
echo tmpl_version();
               tmpl_include($t, $path, $filename)
Description: Inserts another template at the specified location in the current template.
Use:
php
Copy the code
tmpl_include($t, '/header', 'header.html');
               tmpl_exists($t, $path)
Description: Checks if a specific tag or path exists in the template.
Use:
php
Copy the code
if (tmpl_exists($t, '/footer')) {
    // Perform actions
}
Description of the tmpl_set_tag function
tmpl_set_tag($t, $path_or_key, $htmltag, $attributes, $content = '')
    • Description: This function allows you to insert a special HTML tag into the template at a specified location. It can be used to insert meta tags, form input fields and other HTML elements.
    • Parameters:
        ◦ $t : Template object obtained by tmpl_open() .
        ◦ $path_or_key : Placeholder name or path in the template where the HTML tag will be inserted.
        ◦ $htmltag : The name of the HTML tag you want to create (eg meta , input , form ).
        ◦ $attributes : Associative array of attributes and their values for the HTML tag.
        ◦ $content : (Optional) The content inside the HTML tag if it is not a self-closing tag.
How it works:
    • Constructs an HTML tag based on the specified parameters.
    • Inserts the generated HTML tag into the template at the location specified by $path_or_key .

           Examples of using tmpl_set_tag
               Example 1: Inserting a meta tag for page refresh
PHP code:
php
Copy the code
<?php
require_once 'templates.php';

$t = tmpl_open("example.html");

tmpl_set_tag($t, 'tag1', 'meta', ['http-equiv' => 'refresh', 'content' => '33; url=https://address']);

echo tmpl_parse($t);
tmpl_close($t);
?>
Template example.html :
html
Copy the code
<!DOCTYPE html>
<html>
<head>
    {tag1}
    <title>My Page</title>
</head>
<points>
    <p>Page content.</p>
</body>
</html>
Exit:
html
Copy the code
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv='refresh' content='33; url=https://address'>
    <title>My Page</title>
</head>
<points>
    <p>Page content.</p>
</body>
</html>

               Example 2: Inserting a form input field
PHP code:
php
Copy the code
<?php
require_once 'templates.php';

$t = tmpl_open("form.html");

tmpl_set_tag($t, 'input_email', 'input', ['type' => 'email', 'name' => 'email', 'placeholder' => 'Enter your email']);

echo tmpl_parse($t);
tmpl_close($t);
?>
form.html template :
html
Copy the code
<form action="submit.php" method="post">
    {input_email}
    <input type="submit" value="Submit">
</form>
Exit:
html
Copy the code
<form action="submit.php" method="post">
    <input type='email' name='email' placeholder='Enter your email'>
    <input type="submit" value="Submit">
</form>

               Example 3: Creating a button with content
PHP code:
php
Copy the code
<?php
require_once 'templates.php';

$t = tmpl_open("button.html");

tmpl_set_tag($t, 'my_button', 'button', ['type' => 'button', 'class' => 'btn'], 'Click here');

echo tmpl_parse($t);
tmpl_close($t);
?>
button.html template :
html
Copy the code
<div>
    {my_button}
</div>
Exit:
html
Copy the code
<div>
    <button type='button' class='btn'>Click here</button>
</div>

               Example 4: Inserting a video tag with nested content
PHP code:
php
Copy the code
<?php
require_once 'templates.php';

$t = tmpl_open("video.html");

$videoContent = '<source src="movie.mp4" type="video/mp4">';
tmpl_set_tag($t, 'video_player', 'video', ['controls' => '', 'width' => '600'], $videoContent);

echo tmpl_parse($t);
tmpl_close($t);
?>
Template video.html :
html
Copy the code
<div>
    {video_player}
</div>
Exit:
html
Copy the code
<div>
    <video controls width='600'><source src="movie.mp4" type="video/mp4"></video>
</div>

               Example 5: Inserting a form with multiple input fields
PHP code:
php
Copy the code
<?php
require_once 'templates.php';

$t = tmpl_open("complex_form.html");

// Create the input fields
tmpl_set_tag($t, 'input_username', 'input', ['type' => 'text', 'name' => 'username', 'placeholder' => 'Username']);
tmpl_set_tag($t, 'input_password', 'input', ['type' => 'password', 'name' => 'password', 'placeholder' => 'Password']);

// Create a submit button
tmpl_set_tag($t, 'submit_button', 'button', ['type' => 'submit'], 'Submit');

// Build the form
$formContent = "{input_username}<br>{input_password}<br>{submit_button}";
tmpl_set_tag($t, 'login_form', 'form', ['action' => '/login', 'method' => 'post'], $formContent);

echo tmpl_parse($t);
tmpl_close($t);
?>
complex_form.html template :
html
Copy the code
<div>
    {login_form}
</div>
Exit:
html
Copy the code
<div>
    <form action='/login' method='post'><input type='text' name='username' placeholder='Username'><br><input type='password' name='password' placeholder= 'Password'><br><button type='submit'>Login</button></form>
</div>

           Tags from HTML5 and forms
tmpl_set_tag function can be used with any HTML tag and attributes. Here are some other examples:
    • To insert an audio player:
php
Copy the code
tmpl_set_tag($t, 'audio_player', 'audio', ['controls' => ''], '<source src="song.mp3" type="audio/mp3">');
    • To create an image:
php
Copy the code
tmpl_set_tag($t, 'logo_image', 'img', ['src' => 'logo.png', 'alt' => 'Company Logo']);
    • To create a link:
php
Copy the code
tmpl_set_tag($t, 'homepage_link', 'a', ['href' => '/', 'title' => 'Home'], 'Homepage');
    • Insert script:
php
Copy the code
tmpl_set_tag($t, 'script_tag', 'script', ['src' => 'app.js'], '');

           Comment
    • Self-closing tags: The function automatically recognizes self-closing tags and correctly creates them without an end tag.
    • Attributes without a value: If the attribute has no value (eg controls for an audio or video tag), set its value to the empty string '' .
    • Security: Attribute values are automatically escaped using htmlspecialchars to prevent XSS attacks.

           Examples of use
               Example 1: Basic use of placeholders and tags
File: example1.html
html
Copy the code
<html>
<head>
    <title>{title}</title>
</head>
<points>
<tmpl:header>
    <h1>{header_text}</h1>
</tmpl:header>

<p>{content}</p>

</body>
</html>
File: example1.php
php
Copy the code
<?php
require_once 'templates.php';

$t = tmpl_open("example1.html");

tmpl_set($t, "title", "My Page");
tmpl_set($t, "/header/header_text", "Welcome!");
tmpl_set($t, "content", "This is the content of the page.");

echo tmpl_parse($t);
tmpl_close($t);
?>
Exit:
html
Copy the code
<html>
<head>
    <title>My Page</title>
</head>
<points>
<h1>Welcome!</h1>

<p>This is the content of the page.</p>

</body>
</html>
               Example 2: Iterating over data and using tmpl_set_iarray
File: example2.html
html
Copy the code
<html>
<head>
    <title>{title}</title>
</head>
<points>
<h1>{title}</h1>

<tmpl:menu>
    <ul>
        <tmpl:item>
            <li><a href="{link}">{name}</a></li>
        </tmpl:item>
    </ul>
</tmpl:menu>

</body>
</html>
File: example2.php
php
Copy the code
<?php
require_once 'templates.php';

$t = tmpl_open("example2.html");

tmpl_set($t, "title", "Navigation");

$menuItems = [
    ['name' => 'Home', 'link' => '/home'],
    ['name' => 'About us', 'link' => '/about'],
    ['name' => 'Contact', 'link' => '/contact']
];

tmpl_set_iarray($t, '/menu/item', $menuItems);

echo tmpl_parse($t);
tmpl_close($t);
?>
Exit:
html
Copy the code
<html>
<head>
    <title>Navigation</title>
</head>
<points>
<h1>Navigation</h1>

<ul>
    <li><a href="/home">Home</a></li>
    <li><a href="/about">About Us</a></li>
    <li><a href="/contact">Contact</a></li>
</ul>

</body>
</html>
               Example 3: Inserting templates and checking the existence of tags
File: main.html
html
Copy the code
<html>
<head>
    <title>{title}</title>
</head>
<points>
<tmpl:header></tmpl:header>

<tmpl:content>
    <p>{content_text}</p>
</tmpl:content>

<tmpl:footer></tmpl:footer>

</body>
</html>
File: header.html
html
Copy the code
<div class="header">
    <h1>{header_title}</h1>
</div>
File: example3.php
php
Copy the code
<?php
require_once 'templates.php';

$t = tmpl_open("main.html");

tmpl_set($t, "title", "My Page");
tmpl_set($t, "header_title", "Welcome to my page");

if (tmpl_exists($t, '/header')) {
    tmpl_include($t, '/header', 'header.html');
}

tmpl_set($t, "/content/content_text", "This is the main content of the page.");
tmpl_iterate($t, '/content');

echo tmpl_parse($t);
tmpl_close($t);
?>
Exit:
html
Copy the code
<html>
<head>
    <title>My Page</title>
</head>
<points>
<div class="header">
    <h1>Welcome to my page</h1>
</div>

<p>This is the main content of the page.</p>

</body>
</html>
           
Conclusion
templates.php is an effective and simple tool for managing templates in PHP projects. It allows developers to easily separate logic from presentation, simplifies work with repeating structures, and improves code maintainability. With support for iterations, template embedding, and other features, it provides enough flexibility to build complex web applications.
