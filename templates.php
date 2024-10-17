<?php
/**
 * templates.php - Jednoduchý templating systém
 * Verze: 1.4
 * Rok: 2023
 * Autor: PB
 *
 * Tento soubor implementuje jednoduchý templating systém pro práci s HTML šablonami s vlastními tagy a proměnnými.
 * Systém umožňuje otevírání šablon, nastavování proměnných, iteraci přes bloky a parsování finálního výstupu.
 *
 * Funkce:
 * - tmpl_open($filename)
 * - tmpl_close($t)
 * - tmpl_iterate($t, $path)
 * - tmpl_set($t, $path_or_key, $value)
 * - tmpl_set_array($t, $array)
 * - tmpl_set_iarray($t, $path, $array)
 * - tmpl_parse($t, $path = null)
 * - tmpl_debug($t)
 * - tmpl_get_tags($t)
 * - tmpl_version()
 * - tmpl_include($t, $path, $filename)
 * - tmpl_exists($t, $path)
 * - tmpl_set_tag($t, $path, $htmltag, $attributes, $content = '')
 */

class Template
{
    public $content;
    public $tree;
    public $data;
    public $iterations;
    public $enabledPaths;
    public $unrenderedTags;
    public $unrenderedPlaceholders;
    public static $version = '1.4';

    private $selfClosingTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];

    public function __construct($filename = null, $content = null)
    {
        if ($filename) {
            $this->content = file_get_contents($filename);
        } else {
            $this->content = $content;
        }
        $this->tree = $this->parseTemplate($this->content);
        $this->data = [];
        $this->iterations = [];
        $this->enabledPaths = [];
        $this->unrenderedTags = [];
        $this->unrenderedPlaceholders = [];
    }

    private function parseTemplate($content)
    {
        // Parsuje obsah šablony do stromové struktury
        $pattern = '/<tmpl:([a-zA-Z0-9_]+)>(.*?)<\/tmpl:\\1>/s';
        $tree = [];
        $offset = 0;
        while (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE, $offset)) {
            $tag = $matches[1][0];
            $innerContent = $matches[2][0];
            $startPos = $matches[0][1];
            $endPos = $startPos + strlen($matches[0][0]);

            $before = substr($content, $offset, $startPos - $offset);
            if (trim($before) !== '') {
                $tree[] = $before;
            }

            $node = [
                'tag' => $tag,
                'content' => $this->parseTemplate($innerContent),
                'startPos' => $startPos,
                'endPos' => $endPos,
            ];
            $tree[] = $node;

            $offset = $endPos;
        }

        // Přidá zbývající obsah
        $remaining = substr($content, $offset);
        if (trim($remaining) !== '') {
            $tree[] = $remaining;
        }

        return $tree;
    }

    private function normalizePath($path)
    {
        return '/' . trim($path, '/');
    }

    private function enablePath($path)
    {
        $this->enabledPaths[$path] = true;
        // Povolení rodičovských cest
        $segments = explode('/', trim($path, '/'));
        $accumPath = '';
        foreach ($segments as $segment) {
            $accumPath .= '/' . $segment;
            $this->enabledPaths[$accumPath] = true;
        }
    }

    private function isPathEnabled($path)
    {
        return isset($this->enabledPaths[$path]);
    }

    public function set($path_or_key, $value)
    {
        $value = trim($value); // Ořezání mezer
        if (strpos($path_or_key, '/') !== false) {
            $path = $this->normalizePath($path_or_key);
            $parentPath = dirname($path);
            $key = basename($path);

            // Pokud je cesta pod iterací, uložíme data podle indexu iterace
            if (isset($this->iterations[$parentPath])) {
                $index = $this->iterations[$parentPath] - 1; // Index aktuální iterace
                $this->data[$parentPath][$index][$key] = $value;
            } else {
                $this->data[$path] = $value;
            }
            $this->enablePath($path);
        } else {
            $this->data[$path_or_key] = $value;
        }
    }

    public function setArray($array)
    {
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function iterate($path)
    {
        $path = $this->normalizePath($path);
        if (!isset($this->iterations[$path])) {
            $this->iterations[$path] = 0;
        }
        $this->iterations[$path]++;
        $this->enablePath($path);
    }

    public function setIArray($path, $array)
    {
        $path = $this->normalizePath($path);
        $this->data[$path]['_iarray'] = $array;
        $this->enablePath($path);
    }

    public function parse($path = null)
    {
        $output = '';
        if ($path === null) {
            $output = $this->render($this->tree, '', []);
        } else {
            $path = $this->normalizePath($path);
            $nodes = $this->findNodeByPath($this->tree, $path);
            if ($nodes !== null) {
                // Dočasně povolí tuto cestu pro renderování
                $this->enabledPaths[$path] = true;
                $output = $this->render($nodes, $path, []);
            } else {
                $output = '';
            }
        }
        return trim($output); // Ořezání mezer ve výstupu
    }

    private function render($nodes, $currentPath, $currentData)
    {
        $output = '';
        foreach ($nodes as $node) {
            if (is_string($node)) {
                // Nahrazení proměnných v řetězci
                $replaced = $this->replaceVariables($node, $currentPath, $currentData);
                if (trim($replaced) !== '') {
                    $output .= $replaced;
                }
            } elseif (is_array($node)) {
                $tag = $node['tag'];
                $path = $this->normalizePath($currentPath . '/' . $tag);

                if ($this->isPathEnabled($path)) {
                    if (isset($this->data[$path]['_iarray'])) {
                        // Iterace přes pole
                        $array = $this->data[$path]['_iarray'];
                        foreach ($array as $item) {
                            // Sloučení aktuálních dat s daty položky
                            $newData = array_merge($currentData, $item);
                            $output .= $this->render($node['content'], $path, $newData);
                        }
                    } elseif (isset($this->iterations[$path])) {
                        // Iterace na základě počtu volání iterate
                        $iterations = $this->iterations[$path];
                        for ($i = 0; $i < $iterations; $i++) {
                            // Sloučení aktuálních dat s daty pro tuto iteraci
                            if (isset($this->data[$path][$i])) {
                                $newData = array_merge($currentData, $this->data[$path][$i]);
                            } else {
                                $newData = $currentData;
                            }
                            $output .= $this->render($node['content'], $path, $newData);
                        }
                    } else {
                        // Renderuje jednou, pokud bylo nastaveno přes tmpl_set
                        // Sloučení aktuálních dat s daty pro tuto cestu
                        if (isset($this->data[$path])) {
                            $newData = array_merge($currentData, $this->data[$path]);
                        } else {
                            $newData = $currentData;
                        }
                        $output .= $this->render($node['content'], $path, $newData);
                    }
                } else {
                    // Shromažďování nezobrazených tagů
                    $this->unrenderedTags[] = $path;
                }
            }
        }
        return $output;
    }

    private function replaceVariables($text, $currentPath, $currentData)
    {
        // Nahrazení placeholderů jako {variable}
        return preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($matches) use ($currentPath, $currentData) {
            $key = $matches[1];
            $pathKey = $this->normalizePath($currentPath . '/' . $key);
            if (isset($currentData[$key])) {
                return $currentData[$key];
            } elseif (isset($this->data[$pathKey])) {
                return $this->data[$pathKey];
            } elseif (isset($this->data[$key])) {
                return $this->data[$key];
            } else {
                // Shromažďování nezobrazených placeholderů
                $this->unrenderedPlaceholders[] = $key;
                // Odstranění nepoužitých placeholderů
                return '';
            }
        }, $text);
    }

    private function findNodeByPath($nodes, $path)
    {
        $segments = explode('/', trim($path, '/'));
        return $this->findNode($nodes, $segments);
    }

    private function findNode($nodes, $segments)
    {
        if (empty($segments)) {
            return $nodes;
        }

        $segment = array_shift($segments);
        foreach ($nodes as $node) {
            if (is_array($node) && $node['tag'] === $segment) {
                if (empty($segments)) {
                    return $node['content'];
                } else {
                    return $this->findNode($node['content'], $segments);
                }
            }
        }
        return null;
    }

    public function close()
    {
        // Vyčištění zdrojů
        $this->content = null;
        $this->tree = null;
        $this->data = null;
        $this->iterations = null;
        $this->enabledPaths = null;
        $this->unrenderedTags = null;
        $this->unrenderedPlaceholders = null;
    }

    public function debug()
    {
        echo "\n---------------------- Debug Info ----------------------\n";
        echo "Struktura šablony:\n";
        $this->printTree($this->tree);
        echo "\nSeznam tagů <tmpl:xxx>:\n";
        $allTags = $this->collectTags($this->tree);
        foreach ($allTags as $tagPath) {
            echo "- $tagPath\n";
        }
        echo "\nSeznam placeholderů {}:\n";
        $allPlaceholders = $this->collectPlaceholders($this->tree);
        foreach ($allPlaceholders as $placeholder) {
            echo "- $placeholder\n";
        }
        echo "\nNezobrazené tagy <tmpl:xxx>:\n";
        foreach (array_unique($this->unrenderedTags) as $tagPath) {
            echo "- $tagPath\n";
        }
        echo "\nNezobrazené placeholdery {}:\n";
        foreach (array_unique($this->unrenderedPlaceholders) as $placeholder) {
            echo "- $placeholder\n";
        }
        echo "\n--------------------------------------------------------\n";
    }

    private function printTree($nodes, $indent = '')
    {
        foreach ($nodes as $node) {
            if (is_string($node)) {
                // Nic pro řetězce
            } elseif (is_array($node)) {
                $tag = $node['tag'];
                echo $indent . "<tmpl:$tag>\n";
                $this->printTree($node['content'], $indent . '  ');
                echo $indent . "</tmpl:$tag>\n";
            }
        }
    }

    private function collectTags($nodes, $currentPath = '')
    {
        $tags = [];
        foreach ($nodes as $node) {
            if (is_array($node)) {
                $tag = $node['tag'];
                $path = $currentPath . '/' . $tag;
                $tags[] = $path;
                $tags = array_merge($tags, $this->collectTags($node['content'], $path));
            }
        }
        return $tags;
    }

    private function collectPlaceholders($nodes, $currentPath = '')
    {
        $placeholders = [];
        foreach ($nodes as $node) {
            if (is_string($node)) {
                preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $node, $matches);
                foreach ($matches[1] as $placeholder) {
                    $placeholders[] = $placeholder;
                }
            } elseif (is_array($node)) {
                $placeholders = array_merge($placeholders, $this->collectPlaceholders($node['content'], $currentPath));
            }
        }
        return array_unique($placeholders);
    }

    // Nová funkce: tmpl_get_tags

    public function getTags()
    {
        return $this->collectTags($this->tree);
    }

    // Nová funkce: tmpl_version

    public static function version()
    {
        return self::$version;
    }

    // Nová funkce: tmpl_exists

    public function exists($path)
    {
        $path = $this->normalizePath($path);
        $node = $this->findNodeByPath($this->tree, $path);
        return $node !== null;
    }

    // Nová funkce: tmpl_include

    public function includeTemplate($path, $filename)
    {
        $path = $this->normalizePath($path);
        $includedContent = file_get_contents($filename);
        $includedTree = $this->parseTemplate($includedContent);

        // Najde rodičovský uzel pro vložení
        $segments = explode('/', trim($path, '/'));
        $tagToReplace = array_pop($segments);
        $parentPath = '/' . implode('/', $segments);
        $parentNode = &$this->findNodeReferenceByPath($this->tree, $parentPath);

        if ($parentNode !== null && is_array($parentNode)) {
            foreach ($parentNode as &$node) {
                if (is_array($node) && $node['tag'] === $tagToReplace) {
                    // Nahradí obsah tohoto uzlu vloženým stromem
                    $node['content'] = $includedTree;
                    return true;
                }
            }
        }
        return false;
    }

    private function &findNodeReferenceByPath(&$nodes, $path)
    {
        $segments = explode('/', trim($path, '/'));
        return $this->findNodeReference($nodes, $segments);
    }

    private function &findNodeReference(&$nodes, $segments)
    {
        $null = null;
        if (empty($segments)) {
            return $nodes;
        }

        $segment = array_shift($segments);
        foreach ($nodes as &$node) {
            if (is_array($node) && $node['tag'] === $segment) {
                if (empty($segments)) {
                    return $node['content'];
                } else {
                    return $this->findNodeReference($node['content'], $segments);
                }
            }
        }
        return $null;
    }

    // Přidání nové metody setTag
    public function setTag($path_or_key, $htmltag, $attributes, $content = '')
    {
        $html = '<' . $htmltag;
        foreach ($attributes as $attr => $value) {
            if ($value === '') {
                $html .= ' ' . $attr;
            } else {
                $html .= ' ' . $attr . '="' . htmlspecialchars($value, ENT_QUOTES) . '"';
            }
        }
        if (in_array($htmltag, $this->selfClosingTags)) {
            $html .= '>';
        } else {
            $html .= '>' . $content . '</' . $htmltag . '>';
        }
        $this->set($path_or_key, $html);
    }
}

// Globální funkce

function tmpl_open($filename)
{
    return new Template($filename);
}

function tmpl_close($t)
{
    if ($t instanceof Template) {
        $t->close();
    }
}

function tmpl_iterate($t, $path)
{
    if ($t instanceof Template) {
        $t->iterate($path);
    }
}

function tmpl_set($t, $path_or_key, $value)
{
    if ($t instanceof Template) {
        $t->set($path_or_key, $value);
    }
}

function tmpl_set_array($t, $array)
{
    if ($t instanceof Template) {
        $t->setArray($array);
    }
}

function tmpl_set_iarray($t, $path, $array)
{
    if ($t instanceof Template) {
        $t->setIArray($path, $array);
    }
}

function tmpl_parse($t, $path = null)
{
    if ($t instanceof Template) {
        return $t->parse($path);
    }
    return '';
}

function tmpl_debug($t)
{
    if ($t instanceof Template) {
        $t->debug();
    }
}

// Nové funkce

function tmpl_get_tags($t)
{
    if ($t instanceof Template) {
        return $t->getTags();
    }
    return [];
}

function tmpl_version()
{
    return Template::version();
}

function tmpl_exists($t, $path)
{
    if ($t instanceof Template) {
        return $t->exists($path);
    }
    return false;
}

function tmpl_include($t, $path, $filename)
{
    if ($t instanceof Template) {
        return $t->includeTemplate($path, $filename);
    }
    return false;
}

function tmpl_set_tag($t, $path_or_key, $htmltag, $attributes, $content = '')
{
    if ($t instanceof Template) {
        $t->setTag($path_or_key, $htmltag, $attributes, $content);
    }
}
?>
