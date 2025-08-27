<?php

namespace src\Models\Produit;

use src\Database\Database;

class Incassable
{
    public function __construct($libelle, $poids) {
        parent::__construct($libelle, $poids);
        $this->sousType = 'incassable';
    }

    public function info() {
        return "Produit Matériel Incassable: {$this->libelle}, Poids: {$this->poids} kg";
    }

    public function calculerPrix($typeCargaison, $distance) {
        $db = Database::getInstance();

        switch ($typeCargaison) {
            case 'routiere':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_routiere'")['valeur'];
                return $this->poids * $distance * $tarif;
            case 'maritime':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_maritime'")['valeur'];
                return $this->poids * $distance * $tarif;
            case 'aerienne':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_aerienne'")['valeur'];
                return $this->poids * $tarif;
            default:
                return 0;
        }
    }

    public function peutEtreTransporteePar($typeCargaison) {
        // Les produits incassables peuvent être transportés par tous les types de cargaison
        return in_array($typeCargaison, ['routiere', 'maritime', 'aerienne']);
    }

}