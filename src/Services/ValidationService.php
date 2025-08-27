<?php

namespace src\Services;

class ValidationService {

    public static function validateCargaisonData($data) {
        $errors = [];

        // Champs requis
        $required = ['numero', 'poids_max', 'lieu_depart', 'lieu_arrivee', 'distance_km', 'type'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "Le champ {$field} est requis";
            }
        }

        // Validation du type
        if (isset($data['type']) && !in_array($data['type'], ['maritime', 'aerienne', 'routiere'])) {
            $errors[] = "Type de cargaison invalide";
        }

        // Validation du poids maximum
        if (isset($data['poids_max']) && $data['poids_max'] <= 0) {
            $errors[] = "Le poids maximum doit être supérieur à 0";
        }

        // Validation de la distance
        if (isset($data['distance_km']) && $data['distance_km'] <= 0) {
            $errors[] = "La distance doit être supérieure à 0";
        }

        return $errors;
    }

    public static function validateColisData($data) {
        $errors = [];

        // Champs requis
        $required = ['client', 'nombre_colis', 'poids_total', 'type_produit', 'type_cargaison'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                $errors[] = "Le champ {$field} est requis";
            }
        }

        // Validation du client
        if (isset($data['client'])) {
            $clientRequired = ['nom', 'prenom', 'telephone', 'adresse'];
            foreach ($clientRequired as $field) {
                if (!isset($data['client'][$field]) || empty($data['client'][$field])) {
                    $errors[] = "Le champ client.{$field} est requis";
                }
            }

            // Validation du téléphone
            if (isset($data['client']['telephone']) && !self::isValidPhone($data['client']['telephone'])) {
                $errors[] = "Format de téléphone invalide";
            }

            // Validation de l'email si fourni
            if (isset($data['client']['email']) && !empty($data['client']['email']) && !filter_var($data['client']['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Format d'email invalide";
            }
        }

        // Validation du type de produit
        if (isset($data['type_produit']) && !in_array($data['type_produit'], ['alimentaire', 'chimique', 'materiel'])) {
            $errors[] = "Type de produit invalide";
        }

        // Validation du type de cargaison
        if (isset($data['type_cargaison']) && !in_array($data['type_cargaison'], ['maritime', 'aerienne', 'routiere'])) {
            $errors[] = "Type de cargaison invalide";
        }

        // Validation de la compatibilité produit/cargaison
        if (isset($data['type_produit']) && isset($data['type_cargaison'])) {
            if (!self::isCompatibleProductCargo($data['type_produit'], $data['type_cargaison'], $data)) {
                $errors[] = "Ce type de produit ne peut pas être transporté par ce type de cargaison";
            }
        }

        // Validation du poids
        if (isset($data['poids_total']) && $data['poids_total'] <= 0) {
            $errors[] = "Le poids total doit être supérieur à 0";
        }

        // Validation du nombre de colis
        if (isset($data['nombre_colis']) && $data['nombre_colis'] <= 0) {
            $errors[] = "Le nombre de colis doit être supérieur à 0";
        }

        return $errors;
    }

    private static function isValidPhone($phone) {
        // Validation basique du téléphone (commence par + suivi de chiffres)
        return preg_match('/^\+[0-9]{10,15}$/', $phone);
    }

    private static function isCompatibleProductCargo($typeProduit, $typeCargaison, $data) {
        switch ($typeProduit) {
            case 'alimentaire':
                return in_array($typeCargaison, ['maritime', 'aerienne', 'routiere']);
            case 'chimique':
                return $typeCargaison === 'maritime';
            case 'materiel':
                $sousType = isset($data['sous_type']) ? $data['sous_type'] : 'incassable';
                if ($sousType === 'fragile') {
                    return in_array($typeCargaison, ['aerienne', 'routiere']);
                }
                return in_array($typeCargaison, ['maritime', 'aerienne', 'routiere']);
            default:
                return false;
        }
    }

    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }

        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}