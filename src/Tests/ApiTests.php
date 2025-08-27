<?php

namespace src\Tests;

class ApiTests {
    private $baseUrl = 'http://localhost/api';
    private $testResults = [];

    public function runAllTests() {
        echo "🧪 Lancement des tests de l'API Cargaison\n";
        echo "========================================\n\n";

        $this->testApiHealth();
        $this->testCargaisonCreation();
        $this->testCargaisonList();
        $this->testCargaisonSearch();
        $this->testColisCreation();
        $this->testColisTracking();
        $this->testClientsList();
        $this->testCargaisonWorkflow();
        $this->testErrorHandling();
        $this->testBusinessRules();

        $this->displayResults();
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'body' => json_decode($response, true),
            'raw' => $response
        ];
    }

    private function assert($condition, $message) {
        if ($condition) {
            $this->testResults[] = "✅ $message";
            return true;
        } else {
            $this->testResults[] = "❌ $message";
            return false;
        }
    }

    public function testApiHealth() {
        echo "🔍 Test de santé de l'API...\n";

        $response = $this->makeRequest('/health');

        $this->assert($response['status'] === 200, "API Health - Code de statut 200");
        $this->assert(isset($response['body']['status']), "API Health - Champ 'status' présent");
        $this->assert($response['body']['status'] === 'healthy', "API Health - Statut 'healthy'");
        $this->assert(isset($response['body']['database']), "API Health - Champ 'database' présent");

        echo "✅ Test de santé terminé\n\n";
    }

    public function testCargaisonCreation() {
        echo "🚢 Test de création de cargaisons...\n";

        // Test création cargaison maritime
        $cargaisonData = [
            'numero' => 'TEST_MAR_' . time(),
            'poids_max' => 5000,
            'lieu_depart' => 'Dakar',
            'lieu_arrivee' => 'Marseille',
            'distance_km' => 4500,
            'type' => 'maritime',
            'coordonnees_depart' => '14.6928,-17.4467',
            'coordonnees_arrivee' => '43.2965,5.3698'
        ];

        $response = $this->makeRequest('/cargaisons', 'POST', $cargaisonData);

        $this->assert($response['status'] === 201, "Création cargaison - Code de statut 201");
        $this->assert(isset($response['body']['message']), "Création cargaison - Message de succès");
        $this->assert(isset($response['body']['cargaison']), "Création cargaison - Données de la cargaison");

        if (isset($response['body']['cargaison']['id'])) {
            $this->testCargaisonId = $response['body']['cargaison']['id'];
        }

        // Test création cargaison aérienne
        $cargaisonAerienne = [
            'numero' => 'TEST_AER_' . time(),
            'poids_max' => 2000,
            'lieu_depart' => 'Dakar',
            'lieu_arrivee' => 'Paris',
            'distance_km' => 4200,
            'type' => 'aerienne'
        ];

        $response = $this->makeRequest('/cargaisons', 'POST', $cargaisonAerienne);
        $this->assert($response['status'] === 201, "Création cargaison aérienne - Succès");

        // Test création cargaison routière
        $cargaisonRoutiere = [
            'numero' => 'TEST_ROU_' . time(),
            'poids_max' => 3000,
            'lieu_depart' => 'Dakar',
            'lieu_arrivee' => 'Bamako',
            'distance_km' => 1200,
            'type' => 'routiere'
        ];

        $response = $this->makeRequest('/cargaisons', 'POST', $cargaisonRoutiere);
        $this->assert($response['status'] === 201, "Création cargaison routière - Succès");

        echo "✅ Tests de création terminés\n\n";
    }

    public function testCargaisonList() {
        echo "📋 Test de liste des cargaisons...\n";

        $response = $this->makeRequest('/cargaisons');

        $this->assert($response['status'] === 200, "Liste cargaisons - Code de statut 200");
        $this->assert(is_array($response['body']) || isset($response['body']['data']), "Liste cargaisons - Format de réponse valide");

        // Test avec pagination
        $response = $this->makeRequest('/cargaisons?page=1&limit=5');
        $this->assert($response['status'] === 200, "Liste cargaisons avec pagination - Succès");

        echo "✅ Tests de liste terminés\n\n";
    }

    public function testCargaisonSearch() {
        echo "🔍 Test de recherche de cargaisons...\n";

        // Recherche par type
        $response = $this->makeRequest('/cargaisons?type=maritime');
        $this->assert($response['status'] === 200, "Recherche par type - Succès");

        // Recherche par lieu
        $response = $this->makeRequest('/cargaisons?lieu_depart=Dakar');
        $this->assert($response['status'] === 200, "Recherche par lieu de départ - Succès");

        // Recherche combinée
        $response = $this->makeRequest('/cargaisons?type=maritime&lieu_depart=Dakar');
        $this->assert($response['status'] === 200, "Recherche combinée - Succès");

        echo "✅ Tests de recherche terminés\n\n";
    }

    public function testColisCreation() {
        echo "📦 Test de création de colis...\n";

        $colisData = [
            'client' => [
                'nom' => 'TestNom',
                'prenom' => 'TestPrenom',
                'telephone' => '+221' . rand(700000000, 799999999),
                'adresse' => 'Adresse de test, Dakar',
                'email' => 'test@example.com'
            ],
            'nombre_colis' => 1,
            'poids_total' => 25.5,
            'type_produit' => 'alimentaire',
            'type_cargaison' => 'maritime',
            'distance' => 4500,
            'destinataire' => [
                'nom' => 'Destinataire Test',
                'telephone' => '+33123456789',
                'adresse' => '13001 Marseille'
            ]
        ];

        $response = $this->makeRequest('/colis', 'POST', $colisData);

        $this->assert($response['status'] === 201, "Création colis - Code de statut 201");
        $this->assert(isset($response['body']['colis']), "Création colis - Données du colis");
        $this->assert(isset($response['body']['recu']), "Création colis - Reçu généré");

        if (isset($response['body']['colis']['code'])) {
            $this->testColisCode = $response['body']['colis']['code'];
        }

        echo "✅ Tests de création de colis terminés\n\n";
    }

    public function testColisTracking() {
        echo "📍 Test de suivi de colis...\n";

        // Utiliser un code de colis existant ou celui créé dans le test précédent
        $testCode = $this->testColisCode ?? 'COL20250820ABC123';

        $response = $this->makeRequest("/suivi/$testCode");

        // Le colis peut ne pas exister, donc on teste les deux cas
        if ($response['status'] === 200) {
            $this->assert(isset($response['body']['code']), "Suivi colis - Code présent");
            $this->assert(isset($response['body']['etat']), "Suivi colis - État présent");
        } else {
            $this->assert($response['status'] === 404, "Suivi colis inexistant - Code 404");
        }

        echo "✅ Tests de suivi terminés\n\n";
    }

    public function testClientsList() {
        echo "👥 Test de liste des clients...\n";

        $response = $this->makeRequest('/clients');

        $this->assert($response['status'] === 200, "Liste clients - Code de statut 200");
        $this->assert(is_array($response['body']), "Liste clients - Format tableau");

        echo "✅ Tests de clients terminés\n\n";
    }

    public function testCargaisonWorkflow() {
        echo "🔄 Test du workflow de cargaison...\n";

        if (!isset($this->testCargaisonId)) {
            echo "⏭️  Pas d'ID de cargaison de test, passage du workflow\n\n";
            return;
        }

        $id = $this->testCargaisonId;

        // Test fermeture
        $response = $this->makeRequest("/cargaisons/$id/close", 'PUT');
        $this->assert($response['status'] === 200, "Fermeture cargaison - Succès");

        // Test réouverture
        $response = $this->makeRequest("/cargaisons/$id/reopen", 'PUT');
        $this->assert($response['status'] === 200 || $response['status'] === 400, "Réouverture cargaison - Réponse attendue");

        echo "✅ Tests de workflow terminés\n\n";
    }

    public function testErrorHandling() {
        echo "⚠️  Test de gestion d'erreurs...\n";

        // Test création cargaison avec données manquantes
        $response = $this->makeRequest('/cargaisons', 'POST', ['numero' => 'INCOMPLETE']);
        $this->assert($response['status'] === 400, "Données incomplètes - Code 400");

        // Test récupération cargaison inexistante
        $response = $this->makeRequest('/cargaisons/99999');
        $this->assert($response['status'] === 404, "Cargaison inexistante - Code 404");

        // Test suivi colis inexistant
        $response = $this->makeRequest('/suivi/INEXISTANT123');
        $this->assert($response['status'] === 404, "Colis inexistant - Code 404");

        echo "✅ Tests de gestion d'erreurs terminés\n\n";
    }

    public function testBusinessRules() {
        echo "📋 Test des règles métier...\n";

        // Test type de cargaison invalide
        $invalidData = [
            'numero' => 'INVALID_' . time(),
            'poids_max' => 1000,
            'lieu_depart' => 'Test',
            'lieu_arrivee' => 'Test',
            'distance_km' => 100,
            'type' => 'invalide'
        ];

        $response = $this->makeRequest('/cargaisons', 'POST', $invalidData);
        $this->assert($response['status'] === 400, "Type invalide - Rejeté");

        // Test numéro de cargaison en double
        $duplicateData = [
            'numero' => 'MAR001', // Numéro déjà existant
            'poids_max' => 1000,
            'lieu_depart' => 'Test',
            'lieu_arrivee' => 'Test',
            'distance_km' => 100,
            'type' => 'maritime'
        ];

        $response = $this->makeRequest('/cargaisons', 'POST', $duplicateData);
        $this->assert($response['status'] === 409 || $response['status'] === 400, "Numéro en double - Rejeté");

        // Test prix minimum (10 000 F)
        $colisMinPrice = [
            'client' => [
                'nom' => 'TestMin',
                'prenom' => 'Prix',
                'telephone' => '+221' . rand(700000000, 799999999),
                'adresse' => 'Test',
            ],
            'nombre_colis' => 1,
            'poids_total' => 1.0, // Très léger pour tester le prix minimum
            'type_produit' => 'alimentaire',
            'type_cargaison' => 'routiere',
            'distance' => 10 // Distance courte
        ];

        $response = $this->makeRequest('/colis', 'POST', $colisMinPrice);
        if ($response['status'] === 201) {
            $this->assert($response['body']['colis']['prix'] >= 10000, "Prix minimum 10 000 F - Respecté");
        }

        echo "✅ Tests des règles métier terminés\n\n";
    }

    private function displayResults() {
        echo "📊 RÉSULTATS DES TESTS\n";
        echo "=====================\n\n";

        $passed = 0;
        $failed = 0;

        foreach ($this->testResults as $result) {
            echo "$result\n";
            if (strpos($result, '✅') !== false) {
                $passed++;
            } else {
                $failed++;
            }
        }

        $total = $passed + $failed;
        $percentage = $total > 0 ? round(($passed / $total) * 100, 1) : 0;

        echo "\n📈 STATISTIQUES:\n";
        echo "================\n";
        echo "✅ Tests réussis: $passed\n";
        echo "❌ Tests échoués: $failed\n";
        echo "📊 Total: $total\n";
        echo "🎯 Taux de réussite: $percentage%\n\n";

        if ($failed === 0) {
            echo "🎉 Tous les tests sont passés avec succès !\n";
        } else {
            echo "⚠️  Certains tests ont échoué. Vérifiez les détails ci-dessus.\n";
        }
    }
}

// Point d'entrée pour l'exécution des tests
if (php_sapi_name() === 'cli') {
    $tests = new ApiTests();
    $tests->runAllTests();
} else {
    echo "Les tests doivent être exécutés en ligne de commande.\n";
}