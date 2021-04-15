<?php

namespace App\Controller;

use App\Repository\GameRepository;
use App\Repository\UserRepository;
use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
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

    /**
     * @Route("/info_modif", name="info_modif")
     */
    public function infoModif( EntityManagerInterface $entityManager, Request $request, GameRepository $gameRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $modifiedlastname = $request->request->get('lastname');
        $modifiedfirstname = $request->request->get('firstname');
        $modifiedgender = $request->request->get('gender');
        $modifiedbirthday = $request->request->get('birthday');
        $actuallastname = $user->getLastName();
        $actualfirstname = $user->getFirstName();
        $actualgender = $user->getGender();
        $actualbirthday = $user->getBirthday();
            if ($modifiedlastname != $actuallastname && $modifiedlastname != NULL) {
                $user->setLastName($modifiedlastname);
                $entityManager->flush();
            }
            if ($modifiedfirstname != $actualfirstname && $modifiedfirstname!= NULL) {
                $user->setFirstName($modifiedfirstname);
                $entityManager->flush();
            }
            if ($modifiedgender != $actualgender && $modifiedgender!= NULL) {
                $user->setGender($modifiedgender);
                $entityManager->flush();
            }
            if ($modifiedbirthday != $actualbirthday && $modifiedbirthday!= NULL) {
                $user->setBirthday($modifiedbirthday);
                $entityManager->flush();
            }
            return $this->json(true);
    }

    /**
     * @Route("/avatar_modif", name="avatar_modif")
     */
    public function avatarModif( EntityManagerInterface $entityManager, Request $request, GameRepository $gameRepository, UserRepository $userRepository): Response
    {
        $user = $this->getUser();
        $newavatar = $request->request->get('avatar');
        $user->setAvatar($newavatar);
        $entityManager->flush();
        return $this->json(true);
    }
}
