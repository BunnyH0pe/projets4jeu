<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Repository\UserRepository;
use App\Entity\Game;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @Route("/profil")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/", name="user_profil")
     */
    public function index( GameRepository $gameRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();

        $gameofuser = [];
        $gameofuser[] = $user->getGames1();
        $gameofuser[] = $user->getGames2();

        return $this->render('user/index.html.twig', [
            'user' => $this->getUser(),
            'playedgames' => $gameofuser
        ]);
    }
}
