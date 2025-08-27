<?php
namespace src\Controllers;
use Exception;
use src\Database\Database;
use src\Models\Cargaison\Cargaison;
use src\Models\Cargaison\CargaisonAerienne;
use src\Models\Cargaison\CargaisonMaritime;
use src\Models\Cargaison\CargaisonRoutiere;
use src\Router\Router;

class CargaisonController {

    public function create() {
        try {
            $data = Router::getInput();

            // Validation des données
            $required = ['numero', 'poids_max', 'lieu_depart', 'lieu_arrivee', 'distance_km', 'type'];
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    Router::sendJson(['error' => "Le champ $field est requis"], 400);
                }
            }

            if (!in_array($data['type'], ['maritime', 'aerienne', 'routiere'])) {
                Router::sendJson(['error' => 'Type de cargaison invalide'], 400);
            }

            $db = Database::getInstance();
            $existingCargaison = $db->fetch(
                "SELECT id FROM cargaisons WHERE numero = ?",
                [$data['numero']]
            );

            if ($existingCargaison) {
                Router::sendJson([
                    'error' => 'Ce numéro de cargaison existe déjà',
                    'details' => "Le numéro '{$data['numero']}' est déjà utilisé"
                ], 400);
            }

            // Factory pattern pour créer la cargaison
            switch ($data['type']) {
                case 'maritime':
                    $cargaison = new CargaisonMaritime(
                        $data['numero'], $data['poids_max'], $data['lieu_depart'],
                        $data['lieu_arrivee'], $data['distance_km']
                    );
                    break;
                case 'aerienne':
                    $cargaison = new CargaisonAerienne(
                        $data['numero'], $data['poids_max'], $data['lieu_depart'],
                        $data['lieu_arrivee'], $data['distance_km']
                    );
                    break;
                case 'routiere':
                    $cargaison = new CargaisonRoutiere(
                        $data['numero'], $data['poids_max'], $data['lieu_depart'],
                        $data['lieu_arrivee'], $data['distance_km']
                    );
                    break;
            }

            // Ajouter les coordonnées si fournies
            if (isset($data['coordonnees_depart'])) {
                $cargaison->coordonneesDepart = $data['coordonnees_depart'];
            }
            if (isset($data['coordonnees_arrivee'])) {
                $cargaison->coordonneesArrivee = $data['coordonnees_arrivee'];
            }

            $cargaison->save();

            Router::sendJson([
                'message' => 'Cargaison créée avec succès',
                'cargaison' => $cargaison->toArray()
            ], 201);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($params) {
        try {
            $cargaison = Cargaison::find($params['id']);

            if (!$cargaison) {
                Router::sendJson(['error' => 'Cargaison non trouvée'], 404);
            }

            Router::sendJson($cargaison->toArray());

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function getByNumero($params) {
        try {
            $cargaison = Cargaison::findByNumero($params['numero']);

            if (!$cargaison) {
                Router::sendJson(['error' => 'Cargaison non trouvée'], 404);
            }

            Router::sendJson($cargaison->toArray());

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function search() {
        try {
            $filters = $_GET;
            $db = Database::getInstance();

            $sql = "SELECT * FROM cargaisons WHERE 1=1";
            $params = [];

            if (isset($filters['lieu_depart']) && !empty($filters['lieu_depart'])) {
                $sql .= " AND lieu_depart LIKE ?";
                $params[] = '%' . $filters['lieu_depart'] . '%';
            }

            if (isset($filters['lieu_arrivee']) && !empty($filters['lieu_arrivee'])) {
                $sql .= " AND lieu_arrivee LIKE ?";
                $params[] = '%' . $filters['lieu_arrivee'] . '%';
            }

            if (isset($filters['type']) && !empty($filters['type'])) {
                $sql .= " AND type = ?";
                $params[] = $filters['type'];
            }

            if (isset($filters['date_depart']) && !empty($filters['date_depart'])) {
                $sql .= " AND DATE(date_depart) = ?";
                $params[] = $filters['date_depart'];
            }

            if (isset($filters['date_arrivee']) && !empty($filters['date_arrivee'])) {
                $sql .= " AND DATE(date_arrivee) = ?";
                $params[] = $filters['date_arrivee'];
            }

            $sql .= " ORDER BY created_at DESC";

            $results = $db->fetchAll($sql, $params);
            Router::sendJson($results);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function close($params) {
        try {
            $cargaison = Cargaison::find($params['id']);

            if (!$cargaison) {
                Router::sendJson(['error' => 'Cargaison non trouvée'], 404);
            }

            $cargaison->fermerCargaison();

            Router::sendJson([
                'message' => 'Cargaison fermée avec succès',
                'cargaison' => $cargaison->toArray()
            ]);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function reopen($params) {
        try {
            $cargaison = Cargaison::find($params['id']);

            if (!$cargaison) {
                Router::sendJson(['error' => 'Cargaison non trouvée'], 404);
            }

            $cargaison->rouvrirCargaison();

            Router::sendJson([
                'message' => 'Cargaison rouverte avec succès',
                'cargaison' => $cargaison->toArray()
            ]);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 400);
        }
    }
}



