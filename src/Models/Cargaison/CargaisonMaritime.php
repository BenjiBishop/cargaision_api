<?php

namespace src\Models\Cargaison;

class CargaisonMaritime extends Cargaison
{
    public function __construct($numero, $poidsMax, $lieuDepart, $lieuArrivee, $distanceKm) {
        parent::__construct($numero, $poidsMax, $lieuDepart, $lieuArrivee, $distanceKm);
        $this->type = 'maritime';
    }

    public function calculerFrais($produit) {
        $prix = $produit->calculerPrix($this->type, $this->distanceKm);

        // Ajouter les frais de chargement maritime
        $db = Database::getInstance();
        $fraisChargement = $db->fetch("SELECT valeur FROM parametres WHERE cle = 'frais_chargement_maritime'")['valeur'];
        $prix += $fraisChargement;

        return $prix;
    }

}