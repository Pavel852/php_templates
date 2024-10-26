
# Templating systém pro PHP - templates.php

`templates.php` je jednoduchý templating systém pro PHP, který umožňuje oddělit logiku aplikace od její prezentace. Poskytuje možnost práce s HTML šablonami obsahujícími vlastní `<tmpl:xxx>` tagy a `{promenna}` zástupné symboly. Tento systém usnadňuje generování dynamického obsahu, práci s iteracemi a vkládání částí šablon.

## Funkce
- **Oddělení logiky a prezentace**: Udržujte kód čistý oddělením PHP logiky od HTML šablon.
- **Generování dynamického obsahu**: Nahrazení zástupných symbolů hodnotami, správa opakujících se struktur pomocí iterací.
- **Modularita šablon**: Podpora vkládání dalších šablon do hlavní šablony pro snadné opakované použití komponent.
- **Podmíněné zobrazení**: Zobrazujte nebo skrývejte části šablony na základě podmínek v kódu.

## Použití

### Příkazy pro práci s templates.php

1. **`tmpl_open($filename)`**: Načte šablonu ze souboru a vytvoří objekt šablony.
2. **`tmpl_close($t)`**: Uzavře objekt šablony a uvolní zdroje.
3. **`tmpl_set($t, $path_or_key, $value)`**: Nastaví hodnotu pro zástupný symbol nebo tag v šabloně.
4. **`tmpl_set_array($t, $array)`**: Nastaví více hodnot najednou pomocí asociativního pole.
5. **`tmpl_iterate($t, $path)`**: Umožňuje nebo iteruje přes specifický tag v šabloně.
6. **`tmpl_parse($t, $path = null)`**: Zpracuje šablonu a vrátí výsledný HTML.
7. **`tmpl_include($t, $path, $filename)`**: Vloží jinou šablonu na určené místo v aktuální šabloně.
8. **`tmpl_exists($t, $path)`**: Zkontroluje, zda v šabloně existuje specifický tag nebo cesta.

### Příklady použití

#### Příklad 1: Vložení hodnot do šablony
```php
<?php
require_once 'templates.php';

$t = tmpl_open("example1.html");
tmpl_set($t, "title", "Moje Stránka");
tmpl_set($t, "header_text", "Vítejte!");
tmpl_set($t, "content", "Toto je obsah stránky.");
echo tmpl_parse($t);
tmpl_close($t);
?>
```

#### Příklad 2: Iterace dat
```php
<?php
require_once 'templates.php';

$t = tmpl_open("example2.html");
tmpl_set($t, "title", "Navigace");

$menuItems = [
    ['name' => 'Domů', 'link' => '/home'],
    ['name' => 'O nás', 'link' => '/about'],
    ['name' => 'Kontakt', 'link' => '/contact']
];
tmpl_set_iarray($t, '/menu/item', $menuItems);
echo tmpl_parse($t);
tmpl_close($t);
?>
```

## Autor a Kontakt
- **Autor**: PB
- **Email**: pavel.bartos.pb@gmail.com
