<?php
/**
 * templates.php - Simple Templating System
 * Version: 1.7
 * Year: 2023
 * Author: PB
 *
 * This file implements a simple templating system for working with HTML templates with custom tags and variables.
 * The system allows opening templates, setting variables, iterating over blocks, and parsing the final output.
 *
 * Functions:
 * - tmpl_open($filename)
 * - tmpl_close($t)
 * - tmpl_iterate($t, $path)
 * - tmpl_set($t, $path_or_key, $value = '')
 * - tmpl_set_array($t, $array)
 * - tmpl_set_iarray($t, $path, $array)
 * - tmpl_parse($t, $path = null)
 * - tmpl_debug($t)
 * - tmpl_get_tags($t)
 * - tmpl_version()
 * - tmpl_include($t, $path, $filename)
 * - tmpl_exists($t, $path)
 * - tmpl_set_tag($t, $path, $htmltag, $attributes, $content = '')
 * - tmpl_setting($t, $options)
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
    public static $version = '1.7';

    private $selfClosingTags = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
    protected $settings = [];

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
        $this->settings = [];
    }

    private function parseTemplate($content)
    {
        // Parse the template content into a tree structure
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

        // Append any remaining content
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
        // Enable parent paths
        $segments = explode('/', trim($path, '/'));
        $accumPath = '';
        foreach ($segments as $segment) {
            $accumPath .= '/' . $segment;
            $this->enabledPaths[$accumPath] = true;
        }
    }

    private function isPathEnabled($path, $currentEnabledPaths = [])
    {
        return isset($currentEnabledPaths[$path]) || isset($this->enabledPaths[$path]);
    }

    public function set($path_or_key, $value = '')
    {
        $value = trim($value); // Trim spaces
        if (strpos($path_or_key, '/') !== false) {
            $path = $this->normalizePath($path_or_key);
            $parentPath = dirname($path);
            $key = basename($path);

            // If the path is under an iteration, store data under the iteration index
            if (isset($this->iterations[$parentPath])) {
                $index = $this->iterations[$parentPath] - 1; // Current iteration index

                // Ensure data for this iteration is an array
                if (!isset($this->data[$parentPath][$index]) || !is_array($this->data[$parentPath][$index])) {
                    $this->data[$parentPath][$index] = [];
                }

                $this->data[$parentPath][$index][$key] = $value;

                // Enable path for this iteration
                if (!isset($this->data[$parentPath][$index]['_enabledPaths'])) {
                    $this->data[$parentPath][$index]['_enabledPaths'] = [];
                }
                $segments = explode('/', trim($path, '/'));
                $accumPath = '';
                foreach ($segments as $segment) {
                    $accumPath .= '/' . $segment;
                    $this->data[$parentPath][$index]['_enabledPaths'][$accumPath] = true;
                }
            } else {
                if ($key === '') {
                    // If key is empty, we don't store a string in place of an array
                    $this->data[$path] = [];
                } else {
                    if (!isset($this->data[$path]) || !is_array($this->data[$path])) {
                        $this->data[$path] = [];
                    }
                    $this->data[$path][$key] = $value;
                }
                $this->enablePath($path);
            }
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
            $output = $this->render($this->tree, '', [], []);
        } else {
            $path = $this->normalizePath($path);
            $nodes = $this->findNodeByPath($this->tree, $path);
            if ($nodes !== null) {
                // Temporarily enable this path for rendering
                $this->enabledPaths[$path] = true;
                $output = $this->render($nodes, $path, [], []);
            } else {
                $output = '';
            }
        }
        return trim($output); // Trim output
    }

    private function render($nodes, $currentPath, $currentData, $currentEnabledPaths = [])
    {
        $output = '';
        foreach ($nodes as $node) {
            if (is_string($node)) {
                // Replace variables in the string
                $replaced = $this->replaceVariables($node, $currentPath, $currentData);
                // Process text according to settings
                $processed = $this->processText($replaced);
                if (trim($processed) !== '') {
                    $output .= $processed;
                }
            } elseif (is_array($node)) {
                $tag = $node['tag'];
                $path = $this->normalizePath($currentPath . '/' . $tag);

                if ($this->isPathEnabled($path, $currentEnabledPaths)) {
                    if (isset($this->data[$path]['_iarray'])) {
                        // Iterate over array
                        $array = $this->data[$path]['_iarray'];
                        foreach ($array as $item) {
                            // Merge current data with item data
                            $newData = array_merge($currentData, $item);
                            // Merge enabled paths
                            $newEnabledPaths = $currentEnabledPaths;
                            $output .= $this->render($node['content'], $path, $newData, $newEnabledPaths);
                        }
                    } elseif (isset($this->iterations[$path])) {
                        // Iterate based on the number of times iterate was called
                        $iterations = $this->iterations[$path];
                        for ($i = 0; $i < $iterations; $i++) {
                            // Merge current data with data for this iteration
                            if (isset($this->data[$path][$i])) {
                                $iterationData = $this->data[$path][$i];

                                // Ensure iterationData is an array
                                if (!is_array($iterationData)) {
                                    $iterationData = [];
                                }

                                $newEnabledPaths = isset($iterationData['_enabledPaths']) ? array_merge($currentEnabledPaths, $iterationData['_enabledPaths']) : $currentEnabledPaths;
                                unset($iterationData['_enabledPaths']); // Remove _enabledPaths from data
                                $newData = array_merge($currentData, $iterationData);
                            } else {
                                $newData = $currentData;
                                $newEnabledPaths = $currentEnabledPaths;
                            }
                            // Render content with data and enabled paths for this iteration
                            $output .= $this->render($node['content'], $path, $newData, $newEnabledPaths);
                        }
                    } else {
                        // Render once if set via tmpl_set
                        // Merge current data with data for this path
                        if (isset($this->data[$path])) {
                            $pathData = $this->data[$path];

                            // Ensure pathData is an array
                            if (!is_array($pathData)) {
                                $pathData = [];
                            }

                            $newEnabledPaths = isset($pathData['_enabledPaths']) ? array_merge($currentEnabledPaths, $pathData['_enabledPaths']) : $currentEnabledPaths;
                            unset($pathData['_enabledPaths']); // Remove _enabledPaths from data
                            $newData = array_merge($currentData, $pathData);
                        } else {
                            $newData = $currentData;
                            $newEnabledPaths = $currentEnabledPaths;
                        }
                        $output .= $this->render($node['content'], $path, $newData, $newEnabledPaths);
                    }
                } else {
                    // Collect unrendered tags
                    $this->unrenderedTags[] = $path;
                }
            }
        }
        return $output;
    }

    private function replaceVariables($text, $currentPath, $currentData)
    {
        // Replace placeholders like {variable}
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
                // Collect unrendered placeholders
                $this->unrenderedPlaceholders[] = $key;
                // Remove unused placeholders
                return '';
            }
        }, $text);
    }

    private function processText($text)
    {
        if (empty($this->settings)) {
            return $text;
        }

        // Process email addresses
        if (isset($this->settings['email'])) {
            $text = preg_replace_callback('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6})/', function ($matches) {
                $email = $matches[1];
                return '<a href="mailto:' . $email . '">' . $email . '</a>';
            }, $text);
        }

        // Process phone numbers
        if (isset($this->settings['tel'])) {
            $text = preg_replace_callback('/(\+?\d[\d\s\-]{7,}\d)/', function ($matches) {
                $tel = $matches[1];
                $telClean = preg_replace('/[\s\-]/', '', $tel);
                return '<a href="tel:' . $telClean . '">' . $tel . '</a>';
            }, $text);
        }

        // Process URLs
        if (isset($this->settings['url'])) {
            $text = preg_replace_callback('/(https?:\/\/[^\s<]+|www\.[^\s<]+)/', function ($matches) {
                $url = $matches[1];
                $href = $url;
                if (strpos($url, 'http') !== 0) {
                    $href = 'http://' . $url;
                }
                return '<a href="' . $href . '">' . $url . '</a>';
            }, $text);
        }

        return $text;
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
        // Clean up resources
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
        echo "Template Structure:\n";
        $this->printTree($this->tree);
        echo "\nList of <tmpl:xxx> Tags:\n";
        $allTags = $this->collectTags($this->tree);
        foreach ($allTags as $tagPath) {
            echo "- $tagPath\n";
        }
        echo "\nList of Placeholders {}:\n";
        $allPlaceholders = $this->collectPlaceholders($this->tree);
        foreach ($allPlaceholders as $placeholder) {
            echo "- $placeholder\n";
        }
        echo "\nUnrendered <tmpl:xxx> Tags:\n";
        foreach (array_unique($this->unrenderedTags) as $tagPath) {
            echo "- $tagPath\n";
        }
        echo "\nUnrendered Placeholders {}:\n";
        foreach (array_unique($this->unrenderedPlaceholders) as $placeholder) {
            echo "- $placeholder\n";
        }
        echo "\n--------------------------------------------------------\n";
    }

    private function printTree($nodes, $indent = '')
    {
        foreach ($nodes as $node) {
            if (is_string($node)) {
                // Do nothing for strings
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

    // New function: tmpl_get_tags

    public function getTags()
    {
        return $this->collectTags($this->tree);
    }

    // New function: tmpl_version

    public static function version()
    {
        return self::$version;
    }

    // New function: tmpl_exists

    public function exists($path)
    {
        $path = $this->normalizePath($path);
        $node = $this->findNodeByPath($this->tree, $path);
        return $node !== null;
    }

    // New function: tmpl_include

    public function includeTemplate($path, $filename)
    {
        $path = $this->normalizePath($path);
        $includedContent = file_get_contents($filename);
        $includedTree = $this->parseTemplate($includedContent);

        // Find the parent node to insert into
        $segments = explode('/', trim($path, '/'));
        $tagToReplace = array_pop($segments);
        $parentPath = '/' . implode('/', $segments);
        $parentNode = &$this->findNodeReferenceByPath($this->tree, $parentPath);

        if ($parentNode !== null && is_array($parentNode)) {
            foreach ($parentNode as &$node) {
                if (is_array($node) && $node['tag'] === $tagToReplace) {
                    // Replace the content of this node with the included tree
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

    // Added new method setTag
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

    // Added method setting
    public function setting($options)
    {
        $options = explode(',', $options);
        foreach ($options as $option) {
            $option = trim($option);
            if ($option !== '') {
                $this->settings[$option] = true;
            }
        }
    }
}

// Global functions

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

function tmpl_set($t, $path_or_key, $value = '')
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

// New functions

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

function tmpl_setting($t, $options)
{
    if ($t instanceof Template) {
        $t->setting($options);
    }
}
?>
