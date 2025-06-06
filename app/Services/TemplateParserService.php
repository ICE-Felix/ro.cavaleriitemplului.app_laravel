<?php

namespace App\Services;

use Carbon\Carbon;

class TemplateParserService
{
    /**
     * Parse a template string with placeholders and replace them with data values.
     *
     * @param string $template The template string containing placeholders.
     * @param array $data The data array with values to replace the placeholders.
     * @return string The parsed template with placeholders replaced by data values.
     */
    public function parseTemplate(string $template, array $data): string
    {
        // Check if the template contains any placeholders
        if (!preg_match('/\{(\w+)(?::([^}]+))?\}/', $template)) {
            // Treat the entire template as a key if no placeholders are found
            return $data[$template] ?? '';
        }

        return preg_replace_callback('/\{(\w+)(?::([^}]+))?\}/', function ($matches) use ($data) {
            $key = $matches[1];
            $format = $matches[2] ?? null;

            // Check if the key exists in the data array
            if (!isset($data[$key]) || $data[$key] === null) {
                return '';
            }

            $value = $data[$key];

            // If a format is specified and the value is a date, apply the date format
            if ($format && strtotime($value) !== false) {
                return Carbon::parse($value)->format($format);
            }

            return $value;
        }, $template);
    }
}
