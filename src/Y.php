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
     *
     * @return mixed
     */
    public static function dotObject($object, $dots)
    {
        # extract given dotted string to array
        $extract = explode('.', $dots);

        # check if current is function
        if (strpos($extract[0], '()')) {
            $extract[0] = str_replace('()', '', $extract[0]);
            $item = $object->{$extract[0]}();
        } else {
            # check if current index is array or object
            $item = isset($object[$extract[0]])
                ? $object[$extract[0]] : $object->{$extract[0]};
        }

        # check if still has child
        if (count($extract) > 1) {
            # remove first index of object for pass to function again
            $slice = implode('.', array_slice($extract, 1));
            return self::dotObject($item, $slice);
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
     *
     * @return mixed
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

                # get item value recurusive
                $value = self::dotObject($item, $name['get']);
                $output[$item->id]->{$name['name']} = $value;

                # check if current item is searchable and change
                # found flag if search string found in item
                if (!isset($name['hidden'])
                    and !$name['hidden']
                    and preg_match("/$search/i", $value)
                ) {
                    $found = true;
                }
            }

            # unset item if no result found in search
            if (! $found) unset($output[$item->id]);
        }

        if (class_exists('Illuminate\Support\Collection'))
            return collect($output);
        else
            return $output;
    }

    /**
     * @package paginate
     * @author  Payam Yasaie <payam@yasaie.ir>
     *
     * @param $items
     * @param $current
     * @param $perPage
     *
     * @return \stdClass
     */
    public static function paginate(&$items, $current, $perPage)
    {
        $items = $items instanceof \Illuminate\Support\Collection ? $items : collect($items);
        $page = new \stdClass();
        $page->current = $current;
        $page->perPage = $perPage;
        $page->items_count = count($items);
        $page->count = (int)ceil($page->items_count / $page->perPage);

        $items = $items->forPage($page->current, $page->perPage);
        return $page;
    }
}