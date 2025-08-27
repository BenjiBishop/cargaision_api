<?php

namespace src\Controllers;


use src\Models\Colis;
use src\Router\Router;

class SuiviController {

    public function trackByCode($params) {
        try {
            $colis = Colis::findByCode($params['code']);

            if (!$colis || $colis->getEtat() === 'annule') {
                Router::sendJson(['error' => 'Code non trouvé ou colis annulé'], 404);
            }

            $etat = $this->getEtatFormatted($colis);

            Router::sendJson([
                'code' => $colis->getCode(),
                'etat' => $etat,
                'type_produit' => $colis->getTypeProduit(),
                'type_cargaison' => $colis->getTypeCargaison(),
                'poids_total' => $colis->getPoidsTotal(),
                'prix' => $colis->getPrix()
            ]);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    private function getEtatFormatted($colis) {
        switch ($colis->getEtat()) {
            case 'en_attente':
                return ['status' => 'En attente', 'message' => 'Votre colis est en attente de traitement'];
            case 'en_cours':
                // Calculer le temps d'arrivée estimé
                return ['status' => 'En cours', 'message' => 'Votre colis est en transit'];
            case 'arrive':
                return ['status' => 'Arrivé', 'message' => 'Votre colis est arrivé à destination'];
            case 'recupere':
                return ['status' => 'Récupéré', 'message' => 'Votre colis a été récupéré'];
            case 'perdu':
                return ['status' => 'Perdu', 'message' => 'Votre colis est malheureusement perdu'];
            case 'archive':
                return ['status' => 'Archivé', 'message' => 'Votre colis a été archivé'];
            default:
                return ['status' => 'Inconnu', 'message' => 'État inconnu'];
        }
    }
}