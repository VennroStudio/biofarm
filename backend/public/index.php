<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

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
$dependencies = require __DIR__ . '/../src/Http/bootstrap.php';
(require __DIR__ . '/../config/routes.php')($app, $dependencies);

$app->run();
