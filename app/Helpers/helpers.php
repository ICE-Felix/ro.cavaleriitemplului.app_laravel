<?php

use App\Services\TemplateParserService;

if (!function_exists('parseTemplate')) {
    function parseTemplate($template, $data) {
        $parser = app(TemplateParserService::class);
        return $parser->parseTemplate($template, $data);
    }
}