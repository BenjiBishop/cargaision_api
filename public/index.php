<?php
// public/index.php - Version finale avec namespaces complets

// Configuration des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Autoloader PSR-4 compatible
spl_autoload_register(function ($class) {
    // Conversion namespace vers chemin de fichier
    // src\Router\Router → src/Router/Router.php
    $file = __DIR__ . '/../' . str_replace('\\', '/', $class) . '.php';

    if (file_exists($file)) {
        require_once $file;
        return true;
    }

    // Log pour déboguer les classes non trouvées
    error_log("Autoloader: Classe $class non trouvée. Fichier attendu: $file");
    return false;
});

// Import des classes principales
use src\Router\Router;
use src\Database\Database;

// Initialisation du routeur
$router = new Router('/api');

// === ROUTES CARGAISONS ===
$router->post('/cargaisons', ['src\Controllers\CargaisonController', 'create']);
$router->get('/cargaisons/{id}', ['src\Controllers\CargaisonController', 'getById']);
$router->get('/cargaisons/numero/{numero}', ['src\Controllers\CargaisonController', 'getByNumero']);
$router->get('/cargaisons', ['src\Controllers\CargaisonController', 'search']);
$router->put('/cargaisons/{id}/close', ['src\Controllers\CargaisonController', 'close']);
$router->put('/cargaisons/{id}/reopen', ['src\Controllers\CargaisonController', 'reopen']);

// === ROUTES COLIS ===
$router->post('/colis', ['src\Controllers\ColisController', 'create']);
$router->get('/colis/{code}', ['src\Controllers\ColisController', 'getByCode']);
$router->put('/colis/{id}/received', ['src\Controllers\ColisController', 'markAsReceived']);
$router->put('/colis/{id}/lost', ['src\Controllers\ColisController', 'markAsLost']);
$router->put('/colis/{id}/archive', ['src\Controllers\ColisController', 'archive']);
$router->put('/colis/{id}/cancel', ['src\Controllers\ColisController', 'cancel']);
$router->put('/colis/{id}/status', ['src\Controllers\ColisController', 'updateStatus']);

// === ROUTES CLIENTS ===
$router->post('/clients', ['src\Controllers\ClientController', 'create']);
$router->get('/clients', ['src\Controllers\ClientController', 'getAll']);
$router->get('/clients/{id}', ['src\Controllers\ClientController', 'getById']);

// === ROUTES SUIVI ===
$router->get('/suivi/{code}', ['src\Controllers\SuiviController', 'trackByCode']);

// === ROUTE DE TEST ===
$router->get('/test', function() {
    Router::sendJson([
        'message' => 'API Cargo fonctionnelle avec namespaces',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'php_version' => phpversion(),
        'autoloader' => 'PSR-4 Compatible',
        'classes_testees' => [
            'Router' => class_exists('src\Router\Router') ? 'OK' : 'ERREUR',
            'Database' => class_exists('src\Database\Database') ? 'OK' : 'ERREUR',
            'CargaisonController' => class_exists('src\Controllers\CargaisonController') ? 'OK' : 'ERREUR'
        ]
    ]);
});

// === ROUTE DE SANTÉ ===
$router->get('/health', function() {
    try {
        $db = Database::getInstance();
        $db->query("SELECT 1");

        Router::sendJson([
            'status' => 'healthy',
            'database' => 'connected',
            'timestamp' => date('Y-m-d H:i:s'),
            'classes_loaded' => get_declared_classes()
        ]);
    } catch (Exception $e) {
        Router::sendJson([
            'status' => 'unhealthy',
            'database' => 'disconnected',
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ], 500);
    }
});

// === ROUTE DOCUMENTATION ===
$router->get('/', function() {
    $documentation = [
        'title' => 'API Gestion Cargaison',
        'version' => '1.0.0',
        'description' => 'API pour la gestion des cargaisons et colis de GrP du Monde',
        'architecture' => 'Namespaces PSR-4',
        'endpoints' => [
            'cargaisons' => [
                'POST /api/cargaisons' => 'Créer une nouvelle cargaison',
                'GET /api/cargaisons/{id}' => 'Obtenir une cargaison par ID',
                'GET /api/cargaisons/numero/{numero}' => 'Obtenir une cargaison par numéro',
                'GET /api/cargaisons' => 'Rechercher des cargaisons (avec filtres)',
                'PUT /api/cargaisons/{id}/close' => 'Fermer une cargaison',
                'PUT /api/cargaisons/{id}/reopen' => 'Rouvrir une cargaison'
            ],
            'colis' => [
                'POST /api/colis' => 'Créer un nouveau colis',
                'GET /api/colis/{code}' => 'Obtenir un colis par code',
                'PUT /api/colis/{id}/received' => 'Marquer un colis comme récupéré',
                'PUT /api/colis/{id}/lost' => 'Marquer un colis comme perdu',
                'PUT /api/colis/{id}/archive' => 'Archiver un colis',
                'PUT /api/colis/{id}/cancel' => 'Annuler un colis',
                'PUT /api/colis/{id}/status' => 'Changer l\'état d\'un colis'
            ],
            'clients' => [
                'GET /api/clients' => 'Lister tous les clients',
                'GET /api/clients/{id}' => 'Obtenir un client par ID'
            ],
            'suivi' => [
                'GET /api/suivi/{code}' => 'Suivre un colis par code'
            ],
            'system' => [
                'GET /api/health' => 'Vérifier l\'état de l\'API',
                'GET /api/test' => 'Test de l\'API'
            ]
        ]
    ];

    Router::sendJson($documentation);
});

// === ROUTE DEBUG ===
$router->get('/debug', function() {
    Router::sendJson([
        'loaded_classes' => array_filter(get_declared_classes(), function($class) {
            return strpos($class, 'src\\') === 0;
        }),
        'included_files' => get_included_files(),
        'autoloader_functions' => spl_autoload_functions()
    ]);
});

// Lancer le routeur avec gestion d'erreurs
try {
    $router->run();
} catch (Exception $e) {
    Router::sendJson([
        'error' => 'Erreur interne du serveur',
        'message' => $e->getMessage(),
        'file' => basename($e->getFile()),
        'line' => $e->getLine(),
        'timestamp' => date('Y-m-d H:i:s')
    ], 500);
}