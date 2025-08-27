<?php

namespace src\Models\Produit;

use src\Database\Database;

class Fragile extends ProduitMateriel
{
    public function __construct($libelle, $poids) {
        parent::__construct($libelle, $poids);
        $this->sousType = 'fragile';
    }
    public function info() {
        return "Produit Matériel Fragile: {$this->libelle}, Poids: {$this->poids} kg";
    }

    public function calculerPrix($typeCargaison, $distance) {
        $db = Database::getInstance();

        switch ($typeCargaison) {
            case 'routiere':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_routiere'")['valeur'];
                return $this->poids * $distance * $tarif;
            case 'aerienne':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_materiel_aerienne'")['valeur'];
                return $this->poids * $tarif;
            case 'maritime':
                // Les produits fragiles ne peuvent pas être transportés par voie maritime
                return 0;
            default:
                return 0;
        }
    }

    public function peutEtreTransporteePar($typeCargaison) {
        // Les produits fragiles ne peuvent pas être transportés par voie maritime
        return in_array($typeCargaison, ['routiere', 'aerienne']);
    }

}