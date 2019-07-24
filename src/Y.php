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
     * @package dotObject
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     * @param $object
     * @param $dots
     * @param bool $html
     *
     * @return array|string
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
        } catch(\Exception $e) {
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
     * @package flattenItems
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     * @param $items
     * @param $names
     * @param $search
     * @param null $column
     *
     * @return \Illuminate\Support\Collection
     */
    public static function flattenItems($items, $names, $search, $column = null)
    {
        $output = [];
        foreach ($items as $item) {
            $output[$item->id] = new \stdClass();
            $found = false;
            foreach ($names as $name) {
                # if get index is not set default is name
                isset($name['get'])
                or $name['get'] = $name['name'];

                # check if return is string
                $return_string = isset($name['string']) and $name['string'];
                # get item value recurusive
                $value = self::dotObject($item, $name['get'], $return_string);
                $output[$item->id]->{$name['name']} = $value;

                # check if current item is searchable and change
                # found flag if search string found in item
                if (!$column and isset($name['visible']) and $name['visible']) {
                    if (preg_match("/$search/i", $value))
                        $found = true;

                    # check if column is set search only there
                } elseif ($column and $column == $name['name']) {
                    if (preg_match("/$search/i", $value))
                        $found = true;
                }
            }

            # unset item if no result found in search
            if (! $found) unset($output[$item->id]);
        }

        return collect($output);
    }

    /**
     * @package addAndRemove
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     * @param $object
     * @param $field
     * @param $new
     */
    static public function addAndRemove($object, $field, $new)
    {
        $old = $object->pluck($field)->toArray();

        $added = array_diff($new, $old);
        $removed = array_diff($old, $new);

        foreach ($added as $add) {
            $object->create([
                $field => $add
            ]);
        }

        foreach ($removed as $remove) {
            $object->where($field, $remove)->delete();
        }
    }

    /**
     * @package buildTree
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     * @param $elements
     * @param int $parentId
     *
     * @return array
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
     * @package makeRoute
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     * @param $query
     * @param string $text
     * @param null $route
     *
     * @return string
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
