<?php

namespace src\Controllers;


use Exception;
use src\Models\Client;
use src\Models\Colis;
use src\Router\Router;

class ColisController {

    public function create() {
        try {
            $data = Router::getInput();

            // Validation des données
            $required = ['client', 'nombre_colis', 'poids_total', 'type_produit', 'type_cargaison'];
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    Router::sendJson(['error' => "Le champ $field est requis"], 400);
                }
            }

            // Créer ou récupérer le client
            $clientData = $data['client'];
            $client = Client::findByTelephone($clientData['telephone']);

            if (!$client) {
                $client = new Client(
                    $clientData['nom'],
                    $clientData['prenom'],
                    $clientData['telephone'],
                    $clientData['adresse'],
                    isset($clientData['email']) ? $clientData['email'] : null
                );
                $client->save();
            }

            // Créer le colis
            $colis = new Colis(
                $client->getId(),
                $data['nombre_colis'],
                $data['poids_total'],
                $data['type_produit'],
                $data['type_cargaison']
            );

            // Ajouter les informations du destinataire si fournies
            if (isset($data['destinataire'])) {
                $dest = $data['destinataire'];
                $colis->setDestinataire($dest['nom'], $dest['telephone'], $dest['adresse']);
            }

            // Calculer le prix (distance par défaut à 100km si non fournie)
            $distance = isset($data['distance']) ? $data['distance'] : 100;
            $colis->calculerPrix($distance);

            $colis->save();

            // Générer le reçu
            $recu = $this->genererRecu($colis, $client);

            Router::sendJson([
                'message' => 'Colis créé avec succès',
                'colis' => $colis->toArray(),
                'recu' => $recu
            ], 201);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    private function genererRecu($colis, $client) {
        return [
            'numero_recu' => 'RECU-' . $colis->getCode(),
            'date_emission' => date('Y-m-d H:i:s'),
            'expediteur' => $client->toArray(),
            'colis' => [
                'code' => $colis->getCode(),
                'nombre_colis' => $colis->getNombreColis(),
                'poids_total' => $colis->getPoidsTotal(),
                'type_produit' => $colis->getTypeProduit(),
                'type_cargaison' => $colis->getTypeCargaison(),
                'prix' => $colis->getPrix()
            ],
            'code_destinataire' => $colis->getCodeDestinataire()
        ];
    }

    public function getByCode($params) {
        try {
            $colis = Colnis::findByCode($params['code']);

            if (!$colis) {
                Router::sendJson(['error' => 'Colis non trouvé ou annulé'], 404);
            }

            if ($colis->getEtat() === 'annule') {
                Router::sendJson(['error' => 'Colis non trouvé ou annulé'], 404);
            }

            Router::sendJson($colis->toArray());

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function markAsReceived($params) {
        try {
            $colis = Colis::find($params['id']);

            if (!$colis) {
                Router::sendJson(['error' => 'Colis non trouvé'], 404);
            }

            $colis->marquerCommeRecupere();

            Router::sendJson([
                'message' => 'Colis marqué comme récupéré',
                'colis' => $colis->toArray()
            ]);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function markAsLost($params) {
        try {
            $colis = Colis::find($params['id']);

            if (!$colis) {
                Router::sendJson(['error' => 'Colis non trouvé'], 404);
            }

            $colis->marquerCommePerdu();

            Router::sendJson([
                'message' => 'Colis marqué comme perdu',
                'colis' => $colis->toArray()
            ]);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function archive($params) {
        try {
            $colis = Colis::find($params['id']);

            if (!$colis) {
                Router::sendJson(['error' => 'Colis non trouvé'], 404);
            }

            $colis->archiver();

            Router::sendJson([
                'message' => 'Colis archivé',
                'colis' => $colis->toArray()
            ]);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function cancel($params) {
        try {
            $colis = Colis::find($params['id']);

            if (!$colis) {
                Router::sendJson(['error' => 'Colis non trouvé'], 404);
            }

            $colis->annuler();

            Router::sendJson([
                'message' => 'Colis annulé',
                'colis' => $colis->toArray()
            ]);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 400);
        }
    }

    public function updateStatus($params) {
        try {
            $data = Router::getInput();
            $colis = Colis::find($params['id']);

            if (!$colis) {
                Router::sendJson(['error' => 'Colis non trouvé'], 404);
            }

            if (!isset($data['etat'])) {
                Router::sendJson(['error' => 'État requis'], 400);
            }

            $etatsValides = ['en_attente', 'en_cours', 'arrive', 'recupere', 'perdu', 'archive'];
            if (!in_array($data['etat'], $etatsValides)) {
                Router::sendJson(['error' => 'État invalide'], 400);
            }

            $colis->setEtat($data['etat']);
            $colis->save();

            Router::sendJson([
                'message' => 'État du colis mis à jour',
                'colis' => $colis->toArray()
            ]);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }
}
