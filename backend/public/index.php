<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

$vendorPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorPath)) {
    // Попробуем найти vendor в текущей директории (если index.php уже в корне бэкенда)
    $vendorPath = __DIR__ . '/vendor/autoload.php';
}
if (!file_exists($vendorPath)) {
    die('Error: vendor/autoload.php not found. Please run "composer install" in the backend directory.');
}
require $vendorPath;

$app = AppFactory::create();

// Middleware для CORS - должен быть первым
$app->add(function (Request $request, $handler) {
    // Обработка preflight OPTIONS запросов
    if ($request->getMethod() === 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Max-Age', '3600')
            ->withStatus(204);
    }
    
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Базовый маршрут
$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(json_encode([
        'message' => 'Biofarm API',
        'version' => '1.0.0'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

// Загружаем зависимости и подключаем маршруты
// Пытаемся найти файлы относительно текущей директории (если index.php в корне)
$bootstrapPath = __DIR__ . '/src/Http/bootstrap.php';
$routesPath = __DIR__ . '/config/routes.php';

// Если не найдены, пробуем на уровень выше (если index.php в public/)
if (!file_exists($bootstrapPath)) {
    $bootstrapPath = __DIR__ . '/../src/Http/bootstrap.php';
}
if (!file_exists($routesPath)) {
    $routesPath = __DIR__ . '/../config/routes.php';
}

if (!file_exists($bootstrapPath) || !file_exists($routesPath)) {
    die('Error: Required files not found. Bootstrap: ' . $bootstrapPath . ', Routes: ' . $routesPath);
}

$dependencies = require $bootstrapPath;
(require $routesPath)($app, $dependencies);

$app->run();
