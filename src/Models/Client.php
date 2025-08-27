<?php
namespace src\Models;
use src\Database\Database;

class Client {
    private $id;
    private $nom;
    private $prenom;
    private $telephone;
    private $adresse;
    private $email;

    public function __construct($nom, $prenom, $telephone, $adresse, $email = null) {
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->telephone = $telephone;
        $this->adresse = $adresse;
        $this->email = $email;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getNom() { return $this->nom; }
    public function getPrenom() { return $this->prenom; }
    public function getTelephone() { return $this->telephone; }
    public function getAdresse() { return $this->adresse; }
    public function getEmail() { return $this->email; }

    // Setters
    public function setId($id) { $this->id = $id; }
    public function setNom($nom) { $this->nom = $nom; }
    public function setPrenom($prenom) { $this->prenom = $prenom; }
    public function setTelephone($telephone) { $this->telephone = $telephone; }
    public function setAdresse($adresse) { $this->adresse = $adresse; }
    public function setEmail($email) { $this->email = $email; }

    public function getNomComplet() {
        return $this->prenom . ' ' . $this->nom;
    }

    public function toArray() {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'email' => $this->email,
            'nom_complet' => $this->getNomComplet()
        ];
    }

    public function save() {
        $db = Database::getInstance();

        if ($this->id) {
            // Update
            $sql = "UPDATE clients SET nom = ?, prenom = ?, telephone = ?, adresse = ?, email = ? WHERE id = ?";
            $params = [$this->nom, $this->prenom, $this->telephone, $this->adresse, $this->email, $this->id];
        } else {
            // Insert
            $sql = "INSERT INTO clients (nom, prenom, telephone, adresse, email) VALUES (?, ?, ?, ?, ?)";
            $params = [$this->nom, $this->prenom, $this->telephone, $this->adresse, $this->email];
        }

        $db->query($sql, $params);

        if (!$this->id) {
            $this->id = $db->lastInsertId();
        }

        return $this;
    }

    public static function find($id) {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM clients WHERE id = ?", [$id]);

        if (!$data) {
            return null;
        }

        $client = new Client(
            $data['nom'],
            $data['prenom'],
            $data['telephone'],
            $data['adresse'],
            $data['email']
        );
        $client->setId($data['id']);

        return $client;
    }

    public static function findByTelephone($telephone) {
        $db = Database::getInstance();
        $data = $db->fetch("SELECT * FROM clients WHERE telephone = ?", [$telephone]);

        if (!$data) {
            return null;
        }

        return self::find($data['id']);
    }

    public static function getAll() {
        $db = Database::getInstance();
        $results = $db->fetchAll("SELECT * FROM clients ORDER BY nom, prenom");

        $clients = [];
        foreach ($results as $data) {
            $client = new Client(
                $data['nom'],
                $data['prenom'],
                $data['telephone'],
                $data['adresse'],
                $data['email']
            );
            $client->setId($data['id']);
            $clients[] = $client;
        }

        return $clients;
    }
}