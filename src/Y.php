<?php
/**
 * @package     laravel-helper
 * @author      Payam Yasaie <payam@yasaie.ir>
 * @copyright   2019-07-13
 */

namespace Yasaie\Helper;


class Y
{

    /**
     * @param $object
     * @param $dots
     * @param bool $html
     *
     * @return array|string
     * @package dotObject
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     */
    public static function dotObject($object, $dots, $html = false)
    {
        # extract given dotted string to array
        $extract = explode('.', $dots);

        try {
            # check if current is function
            if (strpos($extract[0], '()') and !is_array($object)) {
                $extract[0] = str_replace('()', '', $extract[0]);
                $item = $object->{$extract[0]}();

                # check if current index is array
            } elseif (isset($object[$extract[0]])) {
                $item = $object[$extract[0]];

                # check if current index is object
            } else {
                $item = $object->{$extract[0]};
            }
        } catch (\Exception $e) {
            try {
                # check if current index is nested array/object
                foreach ($object as $ob) {
                    $item[] = self::dotObject($ob, $extract[0], $html);
                }
            } catch (\Exception $e) {
                # finaly return null if nothing works
                $item = null;
            }
        }
        # check if still has child
        if (count($extract) > 1) {
            # remove first index of object for pass to function again
            $slice = implode('.', array_slice($extract, 1));
            return self::dotObject($item, $slice, $html);
        }
        # Check if it's not set return null
        if (!isset($item)) {
            $item = null;
        }
        # convert array to html if flag is true
        if ($html and is_array($item)) {
            $item = implode('<br>' . PHP_EOL, $item);
        }
        # finaly return last child
        return $item;
    }

    /**
     * @param $items
     * @param $names
     * @param $search
     *
     * @return array|\Illuminate\Support\Collection
     * @package flattenItems
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     */
    public static function flattenItems($items, $names, $search)
    {
        $output = [];
        foreach ($items as $item) {
            $output[$item->id] = new \stdClass();
            $found = false;
            foreach ($names as $name) {
                # if get index is not set default is name
                isset($name['get'])
                or $name['get'] = $name['name'];

                $is_html = isset($name['string']) and $name['string'];
                # get item value recurusive
                $value = self::dotObject($item, $name['get'], $is_html);
                $output[$item->id]->{$name['name']} = $value;

                # check if current item is searchable and change
                # found flag if search string found in item
                if ((!isset($name['hidden'])
                        or !$name['hidden'])
                    and !is_array($value)
                    and preg_match("/$search/i", $value)
                ) {
                    $found = true;
                }
            }

            # unset item if no result found in search
            if (!$found) unset($output[$item->id]);
        }

        if (class_exists('Illuminate\Support\Collection'))
            return collect($output);
        else
            return $output;
    }

    /**
     * @param $object
     * @param $field
     * @param $new
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     * @package addAndRemove
     */
    static public function addAndRemove($object, $field, $new)
    {
        $old = $object->pluck($field)->toArray();
        $new = $new ?: [];

        $added = array_diff($new, $old);
        $removed = array_diff($old, $new);

        foreach ($added as $add) {
            $object->create([
                $field => $add
            ]);
        }

        $object->whereIn($field, $removed)->delete();
    }

    /**
     * @param $elements
     * @param int $parentId
     *
     * @return array
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     * @package buildTree
     */
    public static function buildTree($elements, $parentId = 0)
    {
        $branch = array();

        foreach ($elements as $element) {
            if ($element->parent_id == $parentId) {
                $children = self::buildTree($elements, $element->id);
                if ($children) {
                    $element->children = $children;
                }
                $branch[] = $element;
            }
        }

        return $branch;
    }

    /**
     * @param $query
     * @param string $text
     * @param null $route
     *
     * @return string
     * @package makeRoute
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     */
    public static function makeRoute($query, $text = '', $route = null)
    {
        if (isset($query['route'])) {
            $route = $query['route'];
            unset($query['route']);
        }
        $query = http_build_query($query);
        return "<a href='" . route($route) . "/?$query'>$text</a>";
    }
}
