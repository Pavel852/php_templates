<?php
/**
 * templates.php - Simple Templating System
 * Version: 2.2
 * Release Date: 10/2024
 * Author: PB
 *
 * This file implements a simple templating system for working with HTML templates with custom tags and variables.
 * The system allows opening templates, setting variables with optional text transformations, iterating over blocks, parsing the final output, and inserting tables generated from arrays.
 *
 * Transformations:
 * - `diacritics`: Removes diacritics from the text.
 * - `upper`: Converts the text to uppercase.
 * - `lower`: Converts the text to lowercase.
 * - `1up`: Capitalizes the first letter of the text.
 * - `1upw`: Capitalizes the first letter of each word.
 * - `num_cz`: Formats a number to the Czech format (e.g., `33 545,00`).
 * - `num_us`: Formats a number to the US format (e.g., `12,678.00`).
 * - `date2cz`: Converts a date from `YYYY-MM-DD` to `DD.MM.YYYY`.
 * - `date2us`: Converts a date from `DD.MM.YYYY` to `YYYY-MM-DD`.
 *
 * Functions:
 * - tmpl_open($filename)
 * - tmpl_close($t)
 * - tmpl_iterate($t, $path)
 * - tmpl_set($t, $path_or_key, $value = '', $params = '')
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
 * - tmpl_table($t, $path_or_key, $data, $params = '')
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
    public static $version = '2.1';

    private $selfClosingTags = [
        'area', 'base', 'br', 'col', 'embed', 'hr', 'img',
        'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'
    ];
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

    /**
     * Parses the template content into a tree structure.
     *
     * @param string $content The template content.
     * @return array The parsed tree structure.
     */
    private function parseTemplate($content)
    {
        $pattern = '/<tmpl:([a-zA-Z0-9_]+)>(.*?)<\/tmpl:\\1>/s';
        $tree = [];
        $offset = 0;
        while (preg_match(
            $pattern,
            $content,
            $matches,
            PREG_OFFSET_CAPTURE,
            $offset
        )) {
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

        $remaining = substr($content, $offset);
        if (trim($remaining) !== '') {
            $tree[] = $remaining;
        }

        return $tree;
    }

    /**
     * Normalizes the given path.
     *
     * @param string $path The path to normalize.
     * @return string The normalized path.
     */
    private function normalizePath($path)
    {
        return '/' . trim($path, '/');
    }

    /**
     * Enables the given path for rendering.
     *
     * @param string $path The path to enable.
     */
    private function enablePath($path)
    {
        $this->enabledPaths[$path] = true;
        $segments = explode('/', trim($path, '/'));
        $accumPath = '';
        foreach ($segments as $segment) {
            $accumPath .= '/' . $segment;
            $this->enabledPaths[$accumPath] = true;
        }
    }

    /**
     * Checks if a path is enabled for rendering.
     *
     * @param string $path The path to check.
     * @param array $currentEnabledPaths Currently enabled paths.
     * @return bool True if the path is enabled, false otherwise.
     */
    private function isPathEnabled($path, $currentEnabledPaths = [])
    {
        return isset($currentEnabledPaths[$path]) ||
               isset($this->enabledPaths[$path]);
    }

    /**
     * Sets a value for a given path or key, with optional parameters for text transformations.
     *
     * @param string $path_or_key The path or key to set.
     * @param string $value The value to set.
     * @param string $params Optional parameters for text transformations.
     */
    public function set($path_or_key, $value = '', $params = '')
    {
        $value = trim($value); // Trim spaces

        // Process text transformations based on params
        if ($params !== '') {
            $value = $this->applyTextTransformations($value, $params);
        }

        if (strpos($path_or_key, '/') !== false) {
            $path = $this->normalizePath($path_or_key);
            $parentPath = dirname($path);
            $key = basename($path);

            // If the path is under an iteration, store data under the iteration index
            if (isset($this->iterations[$parentPath])) {
                $index = $this->iterations[$parentPath] - 1; // Current iteration index

                // Ensure data for this iteration is an array
                if (!isset($this->data[$parentPath][$index]) ||
                    !is_array($this->data[$parentPath][$index])) {
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
                    $this->data[$parentPath][$index]['_enabledPaths']
                        [$accumPath] = true;
                }
            } else {
                if ($key === '') {
                    // If key is empty, we don't store a string in place of an array
                    $this->data[$path] = [];
                } else {
                    if (!isset($this->data[$path]) ||
                        !is_array($this->data[$path])) {
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

    /**
     * Applies text transformations based on the provided parameters.
     *
     * @param string $text The text to transform.
     * @param string $params The transformation parameters.
     * @return string The transformed text.
     */
    private function applyTextTransformations($text, $params)
    {
        $options = explode(',', $params);
        foreach ($options as $option) {
            $option = trim($option);
            switch ($option) {
                case 'diacritics':
                    $text = $this->removeDiacritics($text);
                    break;
                case 'upper':
                    $text = mb_strtoupper($text);
                    break;
                case 'lower':
                    $text = mb_strtolower($text);
                    break;
                case '1up':
                    $text = mb_strtoupper(mb_substr($text, 0, 1)) .
                            mb_substr($text, 1);
                    break;
                case '1upw':
                    $text = mb_convert_case($text, MB_CASE_TITLE);
                    break;
                case 'num_cz':
                    $text = $this->formatNumberCz($text);
                    break;
                case 'num_us':
                    $text = $this->formatNumberUs($text);
                    break;
                case 'date2cz':
                    $text = $this->formatDateToCz($text);
                    break;
                case 'date2us':
                    $text = $this->formatDateToUs($text);
                    break;
            }
        }
        return $text;
    }

    /**
     * Removes diacritics from the given text.
     *
     * @param string $text The text from which to remove diacritics.
     * @return string The text without diacritics.
     */
    private function removeDiacritics($text)
    {
        $normalizeChars = array(
            'á' => 'a', 'č' => 'c', 'ď' => 'd', 'é' => 'e', 'ě' => 'e',
            'í' => 'i', 'ň' => 'n', 'ó' => 'o', 'ř' => 'r', 'š' => 's',
            'ť' => 't', 'ú' => 'u', 'ů' => 'u', 'ý' => 'y', 'ž' => 'z',
            'Á' => 'A', 'Č' => 'C', 'Ď' => 'D', 'É' => 'E', 'Ě' => 'E',
            'Í' => 'I', 'Ň' => 'N', 'Ó' => 'O', 'Ř' => 'R', 'Š' => 'S',
            'Ť' => 'T', 'Ú' => 'U', 'Ů' => 'U', 'Ý' => 'Y', 'Ž' => 'Z',
        );
        return strtr($text, $normalizeChars);
    }

    /**
     * Formats a number to the Czech format (e.g., "33545.00" to "33 545,00").
     *
     * @param string $number The number to format.
     * @return string The formatted number.
     */
    private function formatNumberCz($number)
    {
        $number = floatval(str_replace([' ', ','], ['', '.'], $number));
        return number_format($number, 2, ',', ' ');
    }

    /**
     * Formats a number to the US format (e.g., "12678.00" to "12,678.00").
     *
     * @param string $number The number to format.
     * @return string The formatted number.
     */
    private function formatNumberUs($number)
    {
        $number = floatval(str_replace([' ', ','], ['', '.'], $number));
        return number_format($number, 2, '.', ',');
    }

    /**
     * Converts a date from "YYYY-MM-DD" to "DD.MM.YYYY".
     *
     * @param string $date The date to convert.
     * @return string The converted date.
     */
    private function formatDateToCz($date)
    {
        $timestamp = strtotime($date);
        if ($timestamp !== false) {
            return date('d.m.Y', $timestamp);
        }
        return $date; // Return original if conversion fails
    }

    /**
     * Converts a date from "DD.MM.YYYY" to "YYYY-MM-DD".
     *
     * @param string $date The date to convert.
     * @return string The converted date.
     */
    private function formatDateToUs($date)
    {
        // Remove extra spaces
        $date = preg_replace('/\s+/', '', $date);
        // Convert to standard format
        $dateParts = preg_split('/[.\-\/]/', $date);
        if (count($dateParts) === 3) {
            list($day, $month, $year) = $dateParts;
            if (checkdate($month, $day, $year)) {
                return sprintf('%04d-%02d-%02d', $year, $month, $day);
            }
        }
        return $date; // Return original if conversion fails
    }

    /**
     * Sets multiple values from an associative array.
     *
     * @param array $array The associative array of key-value pairs.
     */
    public function setArray($array)
    {
        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Starts an iteration block for the given path.
     *
     * @param string $path The path to iterate over.
     */
    public function iterate($path)
    {
        $path = $this->normalizePath($path);
        if (!isset($this->iterations[$path])) {
            $this->iterations[$path] = 0;
        }
        $this->iterations[$path]++;
        $this->enablePath($path);
    }

    /**
     * Sets an array of data for iteration over a block.
     *
     * @param string $path The path of the block.
     * @param array $array The array of data to iterate over.
     */
    public function setIArray($path, $array)
    {
        $path = $this->normalizePath($path);
        $this->data[$path]['_iarray'] = $array;
        $this->enablePath($path);
    }

    /**
     * Parses the template and returns the rendered output.
     *
     * @param string|null $path Optional path to parse a specific block.
     * @return string The rendered output.
     */
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

    /**
     * Renders the template nodes recursively.
     *
     * @param array $nodes The nodes to render.
     * @param string $currentPath The current path in the template tree.
     * @param array $currentData The current data context.
     * @param array $currentEnabledPaths The currently enabled paths.
     * @return string The rendered output.
     */
    private function render(
        $nodes,
        $currentPath,
        $currentData,
        $currentEnabledPaths = []
    ) {
        $output = '';
        foreach ($nodes as $node) {
            if (is_string($node)) {
                $replaced = $this->replaceVariables(
                    $node,
                    $currentPath,
                    $currentData
                );
                $processed = $this->processText($replaced);
                if (trim($processed) !== '') {
                    $output .= $processed;
                }
            } elseif (is_array($node)) {
                $tag = $node['tag'];
                $path = $this->normalizePath($currentPath . '/' . $tag);

                if ($this->isPathEnabled($path, $currentEnabledPaths)) {
                    if (isset($this->data[$path]['_iarray'])) {
                        $array = $this->data[$path]['_iarray'];
                        foreach ($array as $item) {
                            $newData = array_merge($currentData, $item);
                            $newEnabledPaths = $currentEnabledPaths;
                            $output .= $this->render(
                                $node['content'],
                                $path,
                                $newData,
                                $newEnabledPaths
                            );
                        }
                    } elseif (isset($this->iterations[$path])) {
                        $iterations = $this->iterations[$path];
                        for ($i = 0; $i < $iterations; $i++) {
                            if (isset($this->data[$path][$i])) {
                                $iterationData = $this->data[$path][$i];
                                if (!is_array($iterationData)) {
                                    $iterationData = [];
                                }
                                $newEnabledPaths = isset(
                                    $iterationData['_enabledPaths']
                                ) ? array_merge(
                                    $currentEnabledPaths,
                                    $iterationData['_enabledPaths']
                                ) : $currentEnabledPaths;
                                unset($iterationData['_enabledPaths']);
                                $newData = array_merge(
                                    $currentData,
                                    $iterationData
                                );
                            } else {
                                $newData = $currentData;
                                $newEnabledPaths = $currentEnabledPaths;
                            }
                            $output .= $this->render(
                                $node['content'],
                                $path,
                                $newData,
                                $newEnabledPaths
                            );
                        }
                    } else {
                        if (isset($this->data[$path])) {
                            $pathData = $this->data[$path];
                            if (!is_array($pathData)) {
                                $pathData = [];
                            }
                            $newEnabledPaths = isset(
                                $pathData['_enabledPaths']
                            ) ? array_merge(
                                $currentEnabledPaths,
                                $pathData['_enabledPaths']
                            ) : $currentEnabledPaths;
                            unset($pathData['_enabledPaths']);
                            $newData = array_merge($currentData, $pathData);
                        } else {
                            $newData = $currentData;
                            $newEnabledPaths = $currentEnabledPaths;
                        }
                        $output .= $this->render(
                            $node['content'],
                            $path,
                            $newData,
                            $newEnabledPaths
                        );
                    }
                } else {
                    $this->unrenderedTags[] = $path;
                }
            }
        }
        return $output;
    }

    /**
     * Replaces placeholders with actual data.
     *
     * @param string $text The text containing placeholders.
     * @param string $currentPath The current path in the template tree.
     * @param array $currentData The current data context.
     * @return string The text with placeholders replaced.
     */
    private function replaceVariables(
        $text,
        $currentPath,
        $currentData
    ) {
        return preg_replace_callback(
            '/\{([a-zA-Z0-9_]+)\}/',
            function ($matches) use ($currentPath, $currentData) {
                $key = $matches[1];
                $pathKey = $this->normalizePath($currentPath . '/' . $key);
                if (isset($currentData[$key])) {
                    return $currentData[$key];
                } elseif (isset($this->data[$pathKey])) {
                    return $this->data[$pathKey];
                } elseif (isset($this->data[$key])) {
                    return $this->data[$key];
                } else {
                    $this->unrenderedPlaceholders[] = $key;
                    return '';
                }
            },
            $text
        );
    }

    /**
     * Processes text according to the settings (e.g., email, tel, url).
     *
     * @param string $text The text to process.
     * @return string The processed text.
     */
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
        $text = preg_replace_callback(
            '/\b(\+?\d[\d\s]{6,}\d)\b/',
            function ($matches) {
                $tel = $matches[1];

                // Check if the matched string resembles a date
                if (preg_match('/^\d{4}-\d{1,2}-\d{1,2}$/', $tel) ||
                    preg_match('/^\d{4}\.\d{1,2}\.\d{1,2}$/', $tel) ||
                    preg_match('/^\d{1,2}\.\d{1,2}\.\d{4}$/', $tel)) {
                    // It's a date, return as is
                    return $tel;
                }

                $telClean = preg_replace('/[\s]/', '', $tel);
                $formattedTel = $this->formatPhoneNumber($telClean);
                return '<a href="tel:' . $telClean . '">' . $formattedTel . '</a>';
            },
            $text
        );
    }

    // Process URLs
    if (isset($this->settings['url'])) {
        $text = preg_replace_callback('/(https?:\/\/[^\s<]+|www\.[^\s<]+)/', function ($matches) {
            $url = $matches[1];
            $href = $url;
            if (strpos($url, 'http') !== 0) {
                $href = 'http://' . $url;
            }
            return '<a target="_blank" href="' . $href . '">' . $url . '</a>';
        }, $text);
    }

    return $text;
}

    /**
     * Formats phone numbers according to specified patterns.
     *
     * @param string $number The phone number to format.
     * @return string The formatted phone number.
     */
    private function formatPhoneNumber($number)
    {
        $number = preg_replace('/[^\d+]/', '', $number);
        $formatted = '';
        if (strpos($number, '+') === 0) {
            $formatted .= '+';
            $number = substr($number, 1);
        }

        if ($formatted === '+') {
            $countryCodeLength = 3;
            $countryCode = substr($number, 0, $countryCodeLength);
            $formatted .= $countryCode . ' ';
            $number = substr($number, $countryCodeLength);
        }

        $groups = [];

        if (strlen($number) >= 3) {
            $groups[] = substr($number, 0, 3);
            $number = substr($number, 3);
        }

        while (strlen($number) > 0) {
            if (strlen($number) >= 2) {
                $groups[] = substr($number, 0, 2);
                $number = substr($number, 2);
            } else {
                $groups[] = $number;
                $number = '';
            }
        }

        $formatted .= implode(' ', $groups);
        return $formatted;
    }

    /**
     * Finds nodes by path.
     *
     * @param array $nodes The nodes to search.
     * @param string $path The path to find.
     * @return array|null The found nodes or null.
     */
    private function findNodeByPath($nodes, $path)
    {
        $segments = explode('/', trim($path, '/'));
        return $this->findNode($nodes, $segments);
    }

    /**
     * Recursively finds a node in the template tree.
     *
     * @param array $nodes The nodes to search.
     * @param array $segments The path segments.
     * @return array|null The found node or null.
     */
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

    /**
     * Closes the template and cleans up resources.
     */
    public function close()
    {
        $this->content = null;
        $this->tree = null;
        $this->data = null;
        $this->iterations = null;
        $this->enabledPaths = null;
        $this->unrenderedTags = null;
        $this->unrenderedPlaceholders = null;
    }

    /**
     * Outputs debug information about the template.
     */
    public function debug()
    {
        echo "<hr><pre>\n---------------------- Debug Info ----------------------\n";
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
        echo "\n--------------------------------------------------------\n</pre>";
    }

    /**
     * Prints the template tree structure.
     *
     * @param array $nodes The nodes to print.
     * @param string $indent The indentation for formatting.
     */
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

    /**
     * Collects all tag paths in the template.
     *
     * @param array $nodes The nodes to search.
     * @param string $currentPath The current path.
     * @return array The list of tag paths.
     */
    private function collectTags($nodes, $currentPath = '')
    {
        $tags = [];
        foreach ($nodes as $node) {
            if (is_array($node)) {
                $tag = $node['tag'];
                $path = $currentPath . '/' . $tag;
                $tags[] = $path;
                $tags = array_merge(
                    $tags,
                    $this->collectTags($node['content'], $path)
                );
            }
        }
        return $tags;
    }

    /**
     * Collects all placeholders in the template.
     *
     * @param array $nodes The nodes to search.
     * @param string $currentPath The current path.
     * @return array The list of placeholders.
     */
    private function collectPlaceholders($nodes, $currentPath = '')
    {
        $placeholders = [];
        foreach ($nodes as $node) {
            if (is_string($node)) {
                preg_match_all(
                    '/\{([a-zA-Z0-9_]+)\}/',
                    $node,
                    $matches
                );
                foreach ($matches[1] as $placeholder) {
                    $placeholders[] = $placeholder;
                }
            } elseif (is_array($node)) {
                $placeholders = array_merge(
                    $placeholders,
                    $this->collectPlaceholders($node['content'], $currentPath)
                );
            }
        }
        return array_unique($placeholders);
    }

    /**
     * Returns a list of all tags in the template.
     *
     * @return array The list of tags.
     */
    public function getTags()
    {
        return $this->collectTags($this->tree);
    }

    /**
     * Returns the version of the templating system.
     *
     * @return string The version.
     */
    public static function version()
    {
        return self::$version;
    }

    /**
     * Checks if a given path exists in the template.
     *
     * @param string $path The path to check.
     * @return bool True if the path exists, false otherwise.
     */
    public function exists($path)
    {
        $path = $this->normalizePath($path);
        $node = $this->findNodeByPath($this->tree, $path);
        return $node !== null;
    }

    /**
     * Includes another template file at a specified path.
     *
     * @param string $path The path where to include the template.
     * @param string $filename The filename of the template to include.
     * @return bool True on success, false on failure.
     */
    public function includeTemplate($path, $filename)
    {
        $path = $this->normalizePath($path);
        $includedContent = file_get_contents($filename);
        $includedTree = $this->parseTemplate($includedContent);

        // Find the parent node to insert into
        $segments = explode('/', trim($path, '/'));
        $tagToReplace = array_pop($segments);
        $parentPath = '/' . implode('/', $segments);
        $parentNode = &$this->findNodeReferenceByPath(
            $this->tree,
            $parentPath
        );

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

    /**
     * Finds a node reference by path.
     *
     * @param array &$nodes The nodes to search.
     * @param string $path The path to find.
     * @return array|null The found node reference or null.
     */
    private function &findNodeReferenceByPath(&$nodes, $path)
    {
        $segments = explode('/', trim($path, '/'));
        return $this->findNodeReference($nodes, $segments);
    }

    /**
     * Recursively finds a node reference in the template tree.
     *
     * @param array &$nodes The nodes to search.
     * @param array $segments The path segments.
     * @return array|null The found node reference or null.
     */
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
                    return $this->findNodeReference(
                        $node['content'],
                        $segments
                    );
                }
            }
        }
        return $null;
    }

    /**
     * Sets an HTML tag at a specified path.
     *
     * @param string $path_or_key The path or key where to set the tag.
     * @param string $htmltag The HTML tag name.
     * @param array $attributes The attributes for the tag.
     * @param string $content The inner content of the tag.
     */
    public function setTag(
        $path_or_key,
        $htmltag,
        $attributes,
        $content = ''
    ) {
        $html = '<' . $htmltag;
        foreach ($attributes as $attr => $value) {
            if ($value === '') {
                $html .= ' ' . $attr;
            } else {
                $html .= ' ' . $attr . '="' .
                         htmlspecialchars($value, ENT_QUOTES) . '"';
            }
        }
        if (in_array($htmltag, $this->selfClosingTags)) {
            $html .= '>';
        } else {
            $html .= '>' . $content . '</' . $htmltag . '>';
        }
        $this->set($path_or_key, $html);
    }

    /**
     * Sets processing options for the template (e.g., email, tel, url).
     *
     * @param string $options Comma-separated list of options.
     */
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

    /**
     * Sets an HTML table at a specified path using the provided data array.
     *
     * @param string $path_or_key The path or key where to set the table.
     * @param array $data The data array to generate the table from.
     * @param string $params Optional parameters for table generation and text transformations.
     */
    public function setTable($path_or_key, $data, $params = '')
    {
        if (empty($data) || !is_array($data)) {
            return;
        }

        // Extract parameters
        $options = explode(',', $params);
        $useHeader = in_array('th', $options);
        $textParams = array_diff($options, ['th']);

        // Start building the table HTML
        $html = '<table border="1" cellpadding="5" cellspacing="0">';

        foreach ($data as $rowIndex => $row) {
            $html .= '<tr>';
            foreach ($row as $cellIndex => $cell) {
                // Apply text transformations
                $cellContent = $this->applyTextTransformations(
                    $cell,
                    implode(',', $textParams)
                );

                // Determine if this cell should be a header
                if ($useHeader && $rowIndex === 0) {
                    $html .= '<th>' . htmlspecialchars($cellContent) . '</th>';
                } else {
                    $html .= '<td>' . htmlspecialchars($cellContent) . '</td>';
                }
            }
            $html .= '</tr>';
        }

        $html .= '</table>';

        // Set the generated table HTML into the template
        $this->set($path_or_key, $html);
    }
}

// Global functions

/**
 * Opens a template file and returns a Template object.
 *
 * @param string $filename The filename of the template.
 * @return Template The Template object.
 */
function tmpl_open($filename)
{
    return new Template($filename);
}

/**
 * Closes the Template object.
 *
 * @param Template $t The Template object to close.
 */
function tmpl_close($t)
{
    if ($t instanceof Template) {
        $t->close();
    }
}

/**
 * Starts an iteration block in the template.
 *
 * @param Template $t The Template object.
 * @param string $path The path to iterate over.
 */
function tmpl_iterate($t, $path)
{
    if ($t instanceof Template) {
        $t->iterate($path);
    }
}

/**
 * Sets a value in the template with optional parameters for text transformations.
 *
 * @param Template $t The Template object.
 * @param string $path_or_key The path or key to set.
 * @param string $value The value to set.
 * @param string $params Optional parameters for text transformations.
 */
function tmpl_set($t, $path_or_key, $value = '', $params = '')
{
    if ($t instanceof Template) {
        $t->set($path_or_key, $value, $params);
    }
}

/**
 * Sets multiple values in the template from an associative array.
 *
 * @param Template $t The Template object.
 * @param array $array The associative array of key-value pairs.
 */
function tmpl_set_array($t, $array)
{
    if ($t instanceof Template) {
        $t->setArray($array);
    }
}

/**
 * Sets an array of data for iteration over a block in the template.
 *
 * @param Template $t The Template object.
 * @param string $path The path of the block.
 * @param array $array The array of data to iterate over.
 */
function tmpl_set_iarray($t, $path, $array)
{
    if ($t instanceof Template) {
        $t->setIArray($path, $array);
    }
}

/**
 * Parses the template and returns the rendered output.
 *
 * @param Template $t The Template object.
 * @param string|null $path Optional path to parse a specific block.
 * @return string The rendered output.
 */
function tmpl_parse($t, $path = null)
{
    if ($t instanceof Template) {
        return $t->parse($path);
    }
    return '';
}

/**
 * Outputs debug information about the template.
 *
 * @param Template $t The Template object.
 */
function tmpl_debug($t)
{
    if ($t instanceof Template) {
        $t->debug();
    }
}

/**
 * Returns a list of all tags in the template.
 *
 * @param Template $t The Template object.
 * @return array The list of tags.
 */
function tmpl_get_tags($t)
{
    if ($t instanceof Template) {
        return $t->getTags();
    }
    return [];
}

/**
 * Returns the version of the templating system.
 *
 * @return string The version.
 */
function tmpl_version()
{
    return Template::version();
}

/**
 * Checks if a given path exists in the template.
 *
 * @param Template $t The Template object.
 * @param string $path The path to check.
 * @return bool True if the path exists, false otherwise.
 */
function tmpl_exists($t, $path)
{
    if ($t instanceof Template) {
        return $t->exists($path);
    }
    return false;
}

/**
 * Includes another template file at a specified path.
 *
 * @param Template $t The Template object.
 * @param string $path The path where to include the template.
 * @param string $filename The filename of the template to include.
 * @return bool True on success, false on failure.
 */
function tmpl_include($t, $path, $filename)
{
    if ($t instanceof Template) {
        return $t->includeTemplate($path, $filename);
    }
    return false;
}

/**
 * Sets an HTML tag at a specified path in the template.
 *
 * @param Template $t The Template object.
 * @param string $path_or_key The path or key where to set the tag.
 * @param string $htmltag The HTML tag name.
 * @param array $attributes The attributes for the tag.
 * @param string $content The inner content of the tag.
 */
function tmpl_set_tag(
    $t,
    $path_or_key,
    $htmltag,
    $attributes,
    $content = ''
) {
    if ($t instanceof Template) {
        $t->setTag($path_or_key, $htmltag, $attributes, $content);
    }
}

/**
 * Sets processing options for the template (e.g., email, tel, url).
 *
 * @param Template $t The Template object.
 * @param string $options Comma-separated list of options.
 */
function tmpl_setting($t, $options)
{
    if ($t instanceof Template) {
        $t->setting($options);
    }
}

/**
 * Sets an HTML table at a specified path in the template using the provided data array.
 *
 * @param Template $t The Template object.
 * @param string $path_or_key The path or key where to set the table.
 * @param array $data The data array to generate the table from.
 * @param string $params Optional parameters for table generation and text transformations.
 */
function tmpl_table($t, $path_or_key, $data, $params = '')
{
    if ($t instanceof Template) {
        $t->setTable($path_or_key, $data, $params);
    }
}
?>
