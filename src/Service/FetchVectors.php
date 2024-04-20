<?php

namespace App\Service;

use App\Entity\Embedding;
use App\Entity\Word;
use App\Repository\WordRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FetchVectors
{
    public function __construct(private HttpClientInterface $client, private WordRepository $repository, private EntityManagerInterface $manager){}

    public function fetchWord()
    {
        $response = $this->client->request(
            'GET',
            'https://trouve-mot.fr/api/daily',
        );
        return $response;
    }


    public function fetchVectors($wordValue)
    {
        $response = $this->client->request(
            'POST',
            'http://localhost:11434/api/embeddings',
            [
                'json' =>[
                    'model'=>'mistral',
                    'prompt'=>$wordValue
                ]
            ]
        );
        return $response->toArray();
    }

    public function saveWord($wordValue)
    {
        $savedWords = $this->repository->findAll();
        foreach ($savedWords as $savedWord)
        {
            if ($savedWord->getValue() === $wordValue){
                return $savedWord;
            }
        }
        $newWord = new Word();
        $newWord->setValue($wordValue);
        $embeddings = $this->fetchVectors($wordValue);
        foreach ($embeddings["embedding"] as $embedding)
        {
            $newEmbedding = new Embedding();
            $newEmbedding->setValue($embedding);
            $newEmbedding->setWord($newWord);
            $this->manager->persist($newEmbedding);
            $newWord->addEmbedding($newEmbedding);
            $this->manager->persist($newWord);
        }
        $this->manager->flush();
        return $newWord;
    }

    public function cosine_similarity($vector1, $vector2)
    {
        // Calculate the dot product of the two vectors
        $dotProduct = array_sum(array_map(function($x, $y) {
            return $x * $y;
        }, $vector1, $vector2));

        // Calculate the magnitudes of the vectors
        $magnitude1 = sqrt(array_sum(array_map(function($x) {
            return $x * $x;
        }, $vector1)));

        $magnitude2 = sqrt(array_sum(array_map(function($x) {
            return $x * $x;
        }, $vector2)));

        // Check for division by zero
        if ($magnitude1 == 0 || $magnitude2 == 0) {
            return 0;
        }

        // Compute the cosine similarity
        return round(($dotProduct / ($magnitude1 * $magnitude2))*100, 2);
    }
}