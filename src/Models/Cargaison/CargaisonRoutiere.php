<?php

namespace src\Models\Cargaison;

class CargaisonRoutiere extends Cargaison
{
    public function __construct($numero, $poidsMax, $lieuDepart, $lieuArrivee, $distanceKm) {
        parent::__construct($numero, $poidsMax, $lieuDepart, $lieuArrivee, $distanceKm);
        $this->type = 'routiere';
    }

    public function calculerFrais($produit) {
        return $produit->calculerPrix($this->type, $this->distanceKm);
    }

}