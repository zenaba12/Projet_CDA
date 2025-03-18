<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTimeImmutable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MongoDBService
{
    private HttpClientInterface $httpClient;
    private string $apiUrl;
    private string $apiKey;

    /**
     * Constructeur : Injecte le client HTTP et récupère les variables d'environnement
     */
    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $params)
    {
        $this->httpClient = $httpClient;
        $this->apiUrl = $params->get('MONGO_API_URL'); // Récupère l'URL depuis .env.local
        $this->apiKey = $params->get('MONGO_API_KEY'); // Récupère la clé API depuis .env.local
    }

    /**
     * Insère une visite dans la base MongoDB
     * 
     * @param string $pageName Nom de la page visitée
     */
    public function insertVisit(string $pageName)
    {
        $this->httpClient->request('POST', $this->apiUrl, [
            'headers' => [
                'X-API-KEY' => $this->apiKey, // Utilisation de la clé API sécurisée
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'pageName' => $pageName, // Enregistre le nom de la page visitée
                'visitedAt' => (new DateTimeImmutable())->format("Y-m-d H:i:s"), // Date et heure de la visite
            ],
        ]);
    }
}
