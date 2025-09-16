<?php

namespace App\Core\Traits;



trait UtilityTrait
{


    public static function getIndexFromLabel($label, $arr)
    {
        $label = strtolower(trim($label)); // Convert the label to lowercase
        $arr = array_map('strtolower', $arr); // Convert all elements of the array to lowercase

        $index = array_search($label, $arr);
        return $index !== false ? $index : null; // Return null if the status is not found
    }

    public static function getIncrementedIndexFromLabel($label, $arr, $increment = 1, $ignoreZero = true)
    {
        $label = strtolower(trim($label)); // Convert the label to lowercase
        $arr = array_map('strtolower', $arr); // Convert all elements of the array to lowercase

        $index = array_search($label, $arr);
        return $index !== false ? ($index == 0 && $ignoreZero ? null : $index + $increment) : null; // Return null if not found or ignore zero
    }
}
