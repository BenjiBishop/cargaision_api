<?php

namespace src\Services;

use Exception;
use src\Database\Database;
use src\Models\Colis;

class TrackingService {

    public static function updateColisStatus($colisId, $nouvelEtat, $estimationArrivee = null) {
        $colis = Colis::find($colisId);

        if (!$colis) {
            throw new Exception("Colis non trouvé");
        }

        $colis->setEtat($nouvelEtat);

        if ($estimationArrivee && $nouvelEtat === 'en_cours') {
            $colis->dateArriveePrevue = $estimationArrivee;
        }

        $colis->save();

        // Envoyer notification si configuré
        self::sendNotification($colis, $nouvelEtat);

        return $colis;
    }

    public static function getColisWithDelay() {
        $db = Database::getInstance();

        $sql = "SELECT c.*, cl.nom, cl.prenom, cl.telephone 
                FROM colis c 
                JOIN clients cl ON c.client_id = cl.id 
                WHERE c.etat = 'en_cours' 
                AND c.date_arrivee_prevue < NOW()";

        return $db->fetchAll($sql);
    }

    public static function autoArchiveExpiredColis() {
        $db = Database::getInstance();

        // Récupérer la durée d'archivage automatique
        $dureeArchivage = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'duree_archivage_auto'")['valeur'];

        $sql = "UPDATE colis 
                SET etat = 'archive' 
                WHERE etat = 'arrive' 
                AND DATE_ADD(updated_at, INTERVAL ? DAY) <= NOW()";

        $stmt = $db->query($sql, [$dureeArchivage]);

        return $stmt->rowCount();
    }

    private static function sendNotification($colis, $etat) {
        // Ici, vous pourriez intégrer un service de notification (SMS, Email)
        // Pour l'instant, on log juste l'événement
        error_log("Notification: Colis {$colis->getCode()} - Nouvel état: {$etat}");
    }

    public static function getTrackingHistory($colisCode) {
        $db = Database::getInstance();

        // Cette requête nécessiterait une table d'historique des états
        // Pour l'instant, on retourne juste l'état actuel
        $colis = Colis::findByCode($colisCode);

        if (!$colis) {
            return null;
        }

        return [
            'code' => $colis->getCode(),
            'etat_actuel' => $colis->getEtat(),
            'derniere_mise_a_jour' => date('Y-m-d H:i:s'),
            'historique' => [
                [
                    'etat' => $colis->getEtat(),
                    'date' => date('Y-m-d H:i:s'),
                    'message' => self::getEtatMessage($colis->getEtat())
                ]
            ]
        ];
    }

    private static function getEtatMessage($etat) {
        $messages = [
            'en_attente' => 'Colis en attente de traitement',
            'en_cours' => 'Colis en transit',
            'arrive' => 'Colis arrivé à destination',
            'recupere' => 'Colis récupéré par le destinataire',
            'perdu' => 'Colis déclaré perdu',
            'archive' => 'Colis archivé'
        ];

        return isset($messages[$etat]) ? $messages[$etat] : 'État inconnu';
    }
}
