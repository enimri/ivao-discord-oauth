<?php

namespace App\Core;

class View
{
    public static function render(string $template, array $data = []): void
    {
        extract($data);
        
        // Use ROOT_PATH if defined, otherwise try to find templates
        $templatePath = defined('TEMPLATES_PATH') 
            ? TEMPLATES_PATH . '/' . $template . '.php'
            : __DIR__ . '/../../templates/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            throw new \Exception("Template not found: {$template}. Path: {$templatePath}");
        }
        
        require $templatePath;
    }
}

