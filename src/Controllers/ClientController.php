<?php

namespace src\Controllers;


use Exception;
use src\Models\Client;
use src\Router\Router;

class ClientController {

    public function getAll() {
        try {
            $clients = Client::getAll();
            $result = array_map(function($client) {
                return $client->toArray();
            }, $clients);

            Router::sendJson($result);

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public function getById($params) {
        try {
            $client = Client::find($params['id']);

            if (!$client) {
                Router::sendJson(['error' => 'Client non trouvé'], 404);
            }

            Router::sendJson($client->toArray());

        } catch (Exception $e) {
            Router::sendJson(['error' => $e->getMessage()], 500);
        }
    }

    public  function create()
    {
        $data = Router::getInput();
        $requiredFields = ['nom','prenom','telephone','adresse','email'];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                Router::sendJson(['error' => "le champs $field est obligatoire"],400);
            }
        }

        $client = new Client(
            $data['nom'],
            $data['prenom'],
            $data['telephone'],
            $data['adresse'],
            $data['email']
        );
        $client->save();
        Router::sendJson([
            'message' => 'Client creer avec succes',
            'client' => $client->toArray()]);

    }
}
