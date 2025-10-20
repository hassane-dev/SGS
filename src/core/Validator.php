<?php

class Validator {

    /**
     * Sanitizes an array of data by trimming whitespace and converting special characters to HTML entities.
     *
     * @param array $data The data to sanitize.
     * @return array The sanitized data.
     */
    public static function sanitize(array $data): array {
        $sanitized_data = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized_data[$key] = self::sanitize($value);
            } elseif (is_string($value)) {
                $sanitized_data[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
            } else {
                $sanitized_data[$key] = $value;
            }
        }
        return $sanitized_data;
    }
}
?>
