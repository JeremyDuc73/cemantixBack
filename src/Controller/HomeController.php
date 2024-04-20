<?php

namespace App\Controller;

use App\Entity\Embedding;
use App\Entity\Word;
use App\Repository\WordRepository;
use App\Service\FetchVectors;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }

    #[Route('/fetch/{playerWordValue}', name: 'app_fetch')]
    public function fetchPlayerVectors($playerWordValue, FetchVectors $service): \Symfony\Component\HttpFoundation\JsonResponse
    {

        $playerWord = $service->saveWord($playerWordValue);
        $gameWord = $service->saveWord(json_decode($service->fetchWord()->getContent())->name);

        $arrayPlayer = [];
        $arrayGame = [];

        $vecPlayer = $playerWord->getEmbeddings()->toArray();
        $vecGame = $gameWord->getEmbeddings()->toArray();

        foreach ($vecPlayer as $item)
        {
            $arrayPlayer [] = $item->getValue();
        }
        foreach ($vecGame as $item)
        {
            $arrayGame [] =$item->getValue();
        }

        return $this->json($service->cosine_similarity($arrayPlayer, $arrayGame));
    }
}
