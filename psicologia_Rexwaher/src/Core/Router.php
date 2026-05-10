<?php

namespace Src\Core;

class Router {
    protected $routes = [];
    protected $basePath;

    public function __construct($basePath = '')
    {
        $this->basePath = $basePath;
    }

    public function add($method, $path, $handler, $middlewares = [])
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function dispatch($method, $uri)
    {
        $method = strtoupper($method);

        // 1. Obtener la ruta limpia (sin query string)
        $parsedUrl = parse_url($uri, PHP_URL_PATH);
        
        // 2. Decodificar la URI
        $uri = urldecode($parsedUrl);
        
        // 3. Remover el basePath del inicio de la URI si existe
        if (!empty($this->basePath) && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Remover /index.php si existe al inicio (caso de servidores sin reescritura total)
        if (strpos($uri, '/index.php') === 0) {
            $uri = substr($uri, 10);
        }
        
        // 4. Si la ruta quedó vacía, es la raíz
        if ($uri === '' || $uri === false) {
            $uri = '/';
        }

        // Iterar sobre las rutas
        foreach ($this->routes as $route) {
            // Convertir ruta definida (ej: /users/{id}) a Regex
            // Escapamos las barras para la regex pero permitimos parámetros {param}
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $route['path']);
            $pattern = "#^" . str_replace('/', '\/', $pattern) . "$#";
            
            // Comprobamos método y patrón
            if ($route['method'] === $method && preg_match($pattern, $uri, $matches)) {
                
                // Eliminamos la coincidencia completa, dejando solo los grupos capturados
                array_shift($matches);

                // Ejecutamos Middlewares
                foreach ($route['middlewares'] as $middleware) {
                    if (is_string($middleware)) {
                        $instance = new $middleware();
                        if (method_exists($instance, 'handle')) {
                            // Si retorna false, detenemos la ejecución
                            if ($instance->handle() === false) {
                                return;
                            }
                        }
                    }
                }

                $handler = $route['handler'];

                // Si es un array [Controlador, Metodo]
                if (is_array($handler)) {
                    $controllerName = $handler[0];
                    $methodName = $handler[1];
                    $controller = new $controllerName();
                    
                    // Llamamos al método pasando los parámetros capturados
                    return call_user_func_array([$controller, $methodName], $matches);
                }

                // Si es una función anónima
                if (is_callable($handler)) {
                    return call_user_func_array($handler, $matches);
                }
            }
        }

        // Si llegamos aquí, no se encontró ninguna ruta
        http_response_code(404);
        echo "404 Not Found";
        // Descomenta para depurar:
        // echo "<br>URI Solicitada: " . htmlspecialchars($uri);
        // echo "<br>Base Path: " . htmlspecialchars($this->basePath);
    }
}