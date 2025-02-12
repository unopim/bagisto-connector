<?php

namespace Webkul\SunskyOnline\Presenters;

use Webkul\HistoryControl\Interfaces\HistoryPresenterInterface;
use Webkul\HistoryControl\Presenters\JsonDataPresenter as JsonDataPresenters;

class JsonDataPresenter extends JsonDataPresenters implements HistoryPresenterInterface
{
    /**
     * Represents value changes for history tracking.
     *
     * @param  mixed  $oldValues  Old values that will be compared.
     * @param  mixed  $newValues  New values to compare against old values.
     * @param  string  $fieldName  Name of the field being tracked.
     * @return array Normalized array of changes for history tracking.
     */
    public static function representValueForHistory(mixed $oldValues, mixed $newValues, string $fieldName): array
    {
        $oldArray = is_string($oldValues) ? json_decode($oldValues, true) : (is_array($oldValues) ? $oldValues : []);
        $newArray = is_string($newValues) ? json_decode($newValues, true) : (is_array($newValues) ? $newValues : []);
        $normalizedData = [];

        if (! empty($oldArray) && all_elements_are_arrays($oldArray) && ! empty($newArray) && all_elements_are_arrays($newArray)) {
            $oldArray = array_merge_recursive(...array_values($oldArray));
            $newArray = array_merge_recursive(...array_values($newArray));
        }

        if (empty($oldArray) && empty($newArray)) {
            return $normalizedData;
        }

        $removed = static::calculateDifference($oldArray, $newArray);
        $updated = static::calculateDifference($newArray, $oldArray);

        static::normalizeValues($removed, 'old', $normalizedData);
        static::normalizeValues($updated, 'new', $normalizedData);

        return $normalizedData;
    }

    /**
     * Check if all elements in an array are arrays.
     */
    private static function all_elements_are_arrays(array $array): bool
    {
        foreach ($array as $element) {
            if (! is_array($element)) {
                return false;
            }
        }

        return true;
    }
}
