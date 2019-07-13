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
}