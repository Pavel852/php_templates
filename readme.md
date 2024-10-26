
# Templating systém pro PHP - templates.php

`templates.php` je jednoduchý templating systém pro PHP, který odděluje logiku aplikace od její prezentace. Systém pracuje s HTML šablonami obsahujícími vlastní `<tmpl:xxx>` tagy a `{promenna}` zástupné symboly. Tento systém umožňuje generovat dynamický obsah, spravovat iterace a vkládat šablony do hlavní šablony.

## Funkce a použití

### Hlavní funkce knihovny templates.php

1. **`tmpl_open($filename)`**
   - Načte šablonu ze souboru a vrátí objekt šablony pro další manipulaci.
   - Příklad použití:
     ```php
     <?php
     $t = tmpl_open("example.html");
     ?>
     ```

2. **`tmpl_close($t)`**
   - Uzavře objekt šablony a uvolní zdroje.
   - Použití:
     ```php
     <?php
     tmpl_close($t);
     ?>
     ```

3. **`tmpl_set($t, $path_or_key, $value)`**
   - Nastaví hodnotu pro zástupný symbol `{promenna}` nebo tag v šabloně.
   - Příklad:
     ```php
     <?php
     tmpl_set($t, "title", "Moje stránka");
     tmpl_set($t, "header_text", "Vítejte!");
     ?>
     ```

4. **`tmpl_set_array($t, $array)`**
   - Nastaví více hodnot najednou pomocí asociativního pole.
   - Příklad:
     ```php
     <?php
     $data = [
         "title" => "Moje stránka",
         "header_text" => "Vítejte!",
         "content" => "Toto je obsah stránky."
     ];
     tmpl_set_array($t, $data);
     ?>
     ```

5. **`tmpl_iterate($t, $path)`**
   - Umožňuje iteraci přes specifický tag v šabloně.
   - Příklad:
     ```php
     <?php
     tmpl_iterate($t, "menu_items");
     ?>
     ```

6. **`tmpl_parse($t, $path = null)`**
   - Zpracuje šablonu a vrátí výsledný HTML.
   - Příklad:
     ```php
     <?php
     echo tmpl_parse($t);
     ?>
     ```

7. **`tmpl_include($t, $path, $filename)`**
   - Vloží jinou šablonu do aktuální šablony na specifikované místo.
   - Příklad:
     ```php
     <?php
     tmpl_include($t, "header", "header_template.html");
     ?>
     ```

8. **`tmpl_exists($t, $path)`**
   - Kontroluje, zda v šabloně existuje daný tag nebo cesta.
   - Příklad:
     ```php
     <?php
     if (tmpl_exists($t, "footer")) {
         tmpl_set($t, "footer", "Zápatí existuje!");
     }
     ?>
     ```

## Příklady použití

### Příklad 1: Základní šablona a vkládání hodnot
#### HTML šablona - example.html
```html
<html>
<head><title>{title}</title></head>
<body>
    <h1>{header_text}</h1>
    <p>{content}</p>
</body>
</html>
```
#### PHP skript
```php
<?php
require_once 'templates.php';
$t = tmpl_open("example.html");

tmpl_set($t, "title", "Moje Stránka");
tmpl_set($t, "header_text", "Vítejte!");
tmpl_set($t, "content", "Toto je obsah stránky.");

echo tmpl_parse($t);
tmpl_close($t);
?>
```

### Příklad 2: Iterace a vkládání dalších šablon
#### HTML šablona - menu.html
```html
<ul>
    <tmpl:menu_items>
        <li><a href="{link}">{name}</a></li>
    </tmpl:menu_items>
</ul>
```
#### PHP skript
```php
<?php
$t = tmpl_open("menu.html");
$menu_items = [
    ["name" => "Domů", "link" => "/home"],
    ["name" => "O nás", "link" => "/about"],
    ["name" => "Kontakt", "link" => "/contact"]
];
tmpl_set_iarray($t, "menu_items", $menu_items);
echo tmpl_parse($t);
tmpl_close($t);
?>
```

## Autor a Kontakt
- **Autor**: PB
- **Email**: pavel.bartos.pb@gmail.com
