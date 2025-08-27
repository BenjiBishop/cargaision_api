<?php
namespace src\Models\Produit;
use src\Database\Database;

class ProduitAlimentaire extends Produit {

    public function __construct($libelle, $poids) {
        parent::__construct($libelle, $poids);
        $this->type = 'alimentaire';
    }

    public function info() {
        return "Produit Alimentaire: {$this->libelle}, Poids: {$this->poids} kg";
    }

    public function calculerPrix($typeCargaison, $distance) {
        $db = Database::getInstance();

        switch ($typeCargaison) {
            case 'routiere':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_routiere'")['valeur'];
                return $this->poids * $distance * $tarif;
            case 'maritime':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_maritime'")['valeur'];
                return $this->poids * $distance * $tarif;
            case 'aerienne':
                $tarif = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'tarif_alimentaire_aerienne'")['valeur'];
                return $this->poids * $distance * $tarif;
            default:
                return 0;
        }
    }

    public function peutEtreTransporteePar($typeCargaison) {
        // Les produits alimentaires peuvent être transportés par tous les types de cargaison
        return in_array($typeCargaison, ['routiere', 'maritime', 'aerienne']);
    }
}