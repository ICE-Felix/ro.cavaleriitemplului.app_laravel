<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ParseSqlToJSON extends Command
{
    protected $signature = 'parse:sql {sql}';
    protected $description = 'Parses SQL to JSON and saves it to a file';

    public function handle()
    {
        $sql = $this->argument('sql');

        $json = $this->parseSqlToJSON($sql);

        // Define the file path
        $filePath = 'json/' . now()->format('Y-m-d_His') . '_parsed.json';

        // Save to storage/app/json
        Storage::disk('local')->put($filePath, $json);

        $this->info('JSON has been saved to storage/app/' . $filePath);
    }

    private function parseSqlToJSON($sql)
    {
        // Remove line breaks and extra spaces
        $sql = preg_replace('/\s+/', ' ', $sql);

        $json = [
            'name' => [
                'singular' => '',
                'plural' => ''
            ],
            'schema' => [],
            'GET' => 'read',
            'INSERT' => 'create',
            'DELETE' => 'delete',
            'UPDATE' => 'update'
        ];

        if (preg_match('/create table\s+(\w+).(\w+)/i', $sql, $matches)) {
            $json['name']['singular'] = strtolower($matches[2]);
            $json['name']['plural'] = strtolower($matches[2]) . 's';
        }

        preg_match_all('/(\w+)\s+(\w+)(.*?),/', $sql, $columns, PREG_SET_ORDER);
        foreach ($columns as $col) {
            $name = $col[1];
            $type = strtolower($col[2]);
            $details = $col[3];

            $excludedFields = ['create', 'created_at', 'constraint'];
            if (in_array(strtolower($name), $excludedFields)) {
                continue; // Skip the excluded fields
            }

            $default = null;
            if (preg_match('/default\s+\'?([^,\'\s]+)\'?/i', $details, $defaultMatch)) {
                $default = trim($defaultMatch[1], " ()'"); // Trimming potential wrapping parentheses, spaces, and single quotes
                $default = preg_replace("/::\w+/", "", $default); // Removing PostgreSQL type-casting
            }

            // Exclude defaults that are not meaningful
            if (in_array($default, ['true', '#'])) {
                $default = null;
            }

            // Handling specific data types
            if ($type === 'boolean') {
                $json['schema'][$name] = [
                    'type' => 'select',
                    'cast' => 'bool',
                    'label' => ucfirst($name),
                    'readonly' => false,
                    'visible' => true,
                    'data' => [
                        'source' => [
                            'true' => ['value' => 'true', 'name' => 'True'],
                            'false' => ['value' => 'false', 'name' => 'False']
                        ]
                    ]
                ];
            } elseif ($type === 'uuid') {
                $json['schema'][$name] = [
                    'type' => 'text', // Handling UUID as text
                    'label' => ucfirst($name),
                    'readonly' => false,
                    'visible' => true
                ];
            } else {
                $json['schema'][$name] = [
                    'type' => $type,
                    'label' => ucfirst($name),
                    'readonly' => strpos($details, 'not null') !== false,
                    'visible' => true
                ];
            }

            if ($default !== null && $default !== '') {
                $json['schema'][$name]['default'] = $default;
            }
        }

        // Ensure 'id' field exists with specific attributes
        if (!isset($json['schema']['id'])) {
            $json['schema']['id'] = [
                'label' => '#',
                'type' => 'text',
                'readonly' => true,
                'visible' => false
            ];
        }

        return json_encode($json, JSON_PRETTY_PRINT);
    }

}
