<?php

namespace App\Core;

class Router
{
    private $routes = [];
    
    public function get(string $path, string $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }
    
    public function post(string $path, string $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }
    
    private function addRoute(string $method, string $path, string $handler): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler
        ];
    }
    
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $uri = parse_url($requestUri, PHP_URL_PATH);
        
        // Remove trailing slash (except for root)
        if ($uri !== '/' && substr($uri, -1) === '/') {
            $uri = rtrim($uri, '/');
        }
        
        // Debug: Log routing attempt (remove in production)
        if (ini_get('display_errors')) {
            error_log("Router: Method=$method, URI=$uri, RequestURI=$requestUri");
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $route['path'] === $uri) {
                list($controller, $action) = explode('@', $route['handler']);
                
                if (class_exists($controller)) {
                    try {
                        $instance = new $controller();
                        if (method_exists($instance, $action)) {
                            $instance->$action();
                            return;
                        } else {
                            throw new \Exception("Method {$action} not found in {$controller}");
                        }
                    } catch (\Throwable $e) {
                        http_response_code(500);
                        if (ini_get('display_errors')) {
                            echo "<h1>Controller Error</h1>";
                            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
                            echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
                            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                        } else {
                            echo "Internal server error";
                        }
                        return;
                    }
                } else {
                    http_response_code(500);
                    if (ini_get('display_errors')) {
                        echo "Controller class not found: {$controller}";
                    } else {
                        echo "Internal server error";
                    }
                    return;
                }
            }
        }
        
        // 404 - No route matched
        http_response_code(404);
        if (ini_get('display_errors')) {
            echo "<h1>404 - Page Not Found</h1>";
            echo "<p>No route found for: {$method} {$uri}</p>";
            echo "<p>Available routes:</p><ul>";
            foreach ($this->routes as $route) {
                echo "<li>{$route['method']} {$route['path']}</li>";
            }
            echo "</ul>";
        } else {
            echo "Page not found";
        }
    }
}

