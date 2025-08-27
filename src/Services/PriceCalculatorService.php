<?php
namespace src\Services;
use src\Database\Database;

class PriceCalculatorService {

    public static function calculateProductPrice($typeProduit, $typeCargaison, $poids, $distance, $options = []) {
        $db = Database::getInstance();
        $prix = 0;

        switch ($typeProduit) {
            case 'alimentaire':
                $prix = self::calculateAlimentairePrice($typeCargaison, $poids, $distance, $db);
                break;
            case 'chimique':
                $degreToxicite = $options['degre_toxicite'] ?? 1;
                $prix = self::calculateChimiquePrice($typeCargaison, $poids, $degreToxicite, $db);
                break;
            case 'materiel':
                $sousType = $options['sous_type'] ?? 'incassable';
                $prix = self::calculateMaterielPrice($typeCargaison, $poids, $distance, $sousType, $db);
                break;
        }

        // Appliquer le prix minimum
        $prixMinimum = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'prix_minimum'")['valeur'];
        return max($prix, $prixMinimum);
    }

    private static function calculateAlimentairePrice($typeCargaison, $poids, $distance, $db) {
        switch ($typeCargaison) {
            case 'routiere':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_routiere'")['valeur'];
                return $poids * $distance * $tarif;
            case 'maritime':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_maritime'")['valeur'];
                $frais = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'frais_chargement_maritime'")['valeur'];
                return ($poids * $distance * $tarif) + $frais;
            case 'aerienne':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_aerienne'")['valeur'];
                return $poids * $distance * $tarif;
            default:
                return 0;
        }
    }

    private static function calculateChimiquePrice($typeCargaison, $poids, $degreToxicite, $db) {
        if ($typeCargaison !== 'maritime') {
            return 0; // Produits chimiques uniquement par voie maritime
        }

        $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_chimique_maritime'")['valeur'];
        $fraisEntretien = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'frais_entretien_chimique'")['valeur'];
        $fraisChargement = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'frais_chargement_maritime'")['valeur'];

        return ($poids * $tarif * $degreToxicite) + $fraisEntretien + $fraisChargement;
    }

    private static function calculateMaterielPrice($typeCargaison, $poids, $distance, $sousType, $db) {
        // Les produits fragiles ne peuvent pas être transportés par voie maritime
        if ($sousType === 'fragile' && $typeCargaison === 'maritime') {
            return 0;
        }

        switch ($typeCargaison) {
            case 'routiere':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_routiere'")['valeur'];
                return $poids * $distance * $tarif;
            case 'maritime':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_maritime'")['valeur'];
                $frais = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'frais_chargement_maritime'")['valeur'];
                return ($poids * $distance * $tarif) + $frais;
            case 'aerienne':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_aerienne'")['valeur'];
                return $poids * $tarif;
            default:
                return 0;
        }
    }
}