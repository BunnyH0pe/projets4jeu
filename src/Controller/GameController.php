<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Round;
use App\Repository\CardRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/jeu")
 */
class GameController extends AbstractController
{
    /**
     * @Route("/new-game", name="new_game")
     */
    public function newGame(
        UserRepository $userRepository
    ): Response {
        $users = $userRepository->findAll();

        return $this->render('game/index.html.twig', [
            'users' => $users
        ]);
    }

    /**
     * @Route("/create-game", name="create_game")
     */
    public function createGame(
        Request $request,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        CardRepository $cardRepository
    ): Response {
        $user1 = $this->getUser();
        $user2 = $userRepository->find($request->request->get('user2'));

        if ($user1 !== $user2) {
            $game = new Game();
            $game->setUser1($user1);
            $game->setUser2($user2);
            $game->setCreated(new \DateTime('now'));

            $entityManager->persist($game);

            $set = new Round();
            $set->setGame($game);
            $set->setCreated(new \DateTime('now'));
            $set->setSetNumber(1);

            $cards = $cardRepository->findAll();
            $tCards = [];
            foreach ($cards as $card) {
                $tCards[$card->getId()] = $card;
            }
            shuffle($tCards);
            $carte = array_pop($tCards);
            $set->setRemovedCard($carte->getId());

            $tMainJ1 = [];
            $tMainJ2 = [];
            for ($i = 0; $i < 6; $i++) {
                //on distribue 6 cartes au deuxième joueur
                $carte = array_pop($tCards);
                $tMainJ2[] = $carte->getId();
            }
            for ($i = 0; $i < 7; $i++) {
                //on distribue 7 cartes au premier joueur
                $carte = array_pop($tCards);
                $tMainJ1[] = $carte->getId();
            }
            $set->setUser1HandCards($tMainJ1);
            $set->setUser2HandCards($tMainJ2);

            $tPioche = [];

            foreach ($tCards as $card) {
                $carte = array_pop($tCards);
                $tPioche[] = $carte->getId();
            }
            $set->setPioche($tPioche);
            $set->setUser1Action([
                'SECRET' => false,
                'DEPOT' => false,
                'OFFRE' => false,
                'ECHANGE' => false
            ]);

            $set->setUser2Action([
                'SECRET' => false,
                'DEPOT' => false,
                'OFFRE' => false,
                'ECHANGE' => false
            ]);

            $set->setBoard([
                'EMPL1' => ['N'],
                'EMPL2' => ['N'],
                'EMPL3' => ['N'],
                'EMPL4' => ['N'],
                'EMPL5' => ['N'],
                'EMPL6' => ['N'],
                'EMPL7' => ['N']
            ]);
            $entityManager->persist($set);
            $entityManager->flush();

            return $this->redirectToRoute('show_game', [
                'game' => $game->getId()
            ]);
        } else {
            return $this->redirectToRoute('new_game');
        }
    }

    /**
     * @Route("/show-game/{game}", name="show_game")
     */
    public function showGame(
        Game $game
    ): Response {

        return $this->render('game/show_game.html.twig', [
            'game' => $game
        ]);
    }



    /**
     * @Route("/get-tout-game/{game}", name="get_tour")
     */
    public function getTour(
        Game $game, EntityManagerInterface $entityManager
    ): Response {
        $round = $game->getRounds()[0];
        dump($round->getUser1BoardCards());
        if($round->getPioche()==[] && $round->getUser1HandCards()==[] && $round->getUser2HandCards()==[]){
            $board1 = $round->getUser1BoardCards();
            $board2 = $round->getUser1BoardCards();
            $action1 = $round->getUser1Action();
            $action2 = $round->getUser2Action();
            $board1[] = $action1['SECRET'][0];
            $board2[] = $action2['SECRET'][0];
            $round->setUser1BoardCards($board1);
            $round->setUser2BoardCards($board2);
            return $this->json('finderound');
        }

        if ($this->getUser()->getId() === $game->getUser1()->getId() && $game->getQuiJoue() === 1) {
            return $this->json(true);
        }

        if ($this->getUser()->getId() === $game->getUser2()->getId() && $game->getQuiJoue() === 2) {
            return $this->json(true);
        }

        return $this->json( false);
    }


    /**
     * @Route("/score_round/{game}", name="score_round")
     */
    public function scoreRound(
        Game $game, EntityManagerInterface $entityManager, CardRepository $cardRepository
    ): Response {
        $round = $game->getRounds()[0];
        $cartesj1 = $round->getUser1BoardCards();
        $cartesj2 = $round->getUser2BoardCards();
        $scorej1points = 0;
        $scorej2points = 0;
        $scorej1valeurs = 0;
        $scorej2valeurs = 0;
        $nbj1Bienveillance = 0;
        $nbj1Justice = 0;
        $nbj1Sincerite = 0;
        $nbj1Loyaute = 0;
        $nbj1Respect = 0;
        $nbj1Courage = 0;
        $nbj1Honneur = 0;
        $nbj2Bienveillance = 0;
        $nbj2Justice = 0;
        $nbj2Sincerite = 0;
        $nbj2Loyaute = 0;
        $nbj2Respect = 0;
        $nbj2Courage = 0;
        $nbj2Honneur = 0;
        foreach ($cartesj1 as $carte){
            $id = $carte;
            $card = $cardRepository->find($id);
            if ($card->getName()=='Bienveillance'){
                $nbj1Bienveillance ++;
            }elseif ($card->getName()=='Justice'){
                $nbj1Justice ++;
            }elseif ($card->getName()=='Sincérité'){
                $nbj1Sincerite ++;
            }elseif ($card->getName()=='Loyauté'){
                $nbj1Loyaute ++;
            }elseif ($card->getName()=='Respect'){
                $nbj1Respect ++;
            }elseif ($card->getName()=='Courage'){
                $nbj1Courage ++;
            }elseif ($card->getName()=='Honneur'){
                $nbj1Honneur ++;
            }
        }
        foreach ($cartesj2 as $carte){
            $id = $carte;
            $card = $cardRepository->find($id);
            if ($card->getName()=='Bienveillance'){
                $nbj2Bienveillance ++;
            }elseif ($card->getName()=='Justice'){
                $nbj2Justice ++;
            }elseif ($card->getName()=='Sincérité'){
                $nbj2Sincerite ++;
            }elseif ($card->getName()=='Loyauté'){
                $nbj2Loyaute ++;
            }elseif ($card->getName()=='Respect'){
                $nbj2Respect ++;
            }elseif ($card->getName()=='Courage'){
                $nbj2Courage ++;
            }elseif ($card->getName()=='Honneur'){
                $nbj2Honneur ++;
            }
        }

        if ($nbj1Bienveillance > $nbj2Bienveillance){
            $scorej1points += 2;
            $scorej1valeurs ++;
        }elseif ($nbj2Bienveillance > $nbj1Bienveillance){
            $scorej2points += 2;
            $scorej2valeurs ++;
        }

        if ($nbj1Justice > $nbj2Justice){
            $scorej1points += 2;
            $scorej1valeurs ++;
        }elseif ($nbj2Justice > $nbj2Justice){
            $scorej2points += 2;
            $scorej2valeurs ++;
        }

        if ($nbj1Sincerite > $nbj2Sincerite){
            $scorej1points += 2;
            $scorej1valeurs ++;
        }elseif ($nbj2Sincerite > $nbj2Sincerite){
            $scorej2points += 2;
            $scorej2valeurs ++;
        }

        if ($nbj1Loyaute > $nbj2Loyaute){
            $scorej1points += 3;
            $scorej1valeurs ++;
        }elseif ($nbj2Loyaute > $nbj2Loyaute){
            $scorej2points += 3;
            $scorej2valeurs ++;
        }

        if ($nbj1Respect > $nbj2Respect){
            $scorej1points += 3;
            $scorej1valeurs ++;
        }elseif ($nbj2Respect > $nbj2Respect){
            $scorej2points += 3;
            $scorej2valeurs ++;
        }

        if ($nbj1Courage > $nbj2Courage){
            $scorej1points += 4;
            $scorej1valeurs ++;
        }elseif ($nbj2Courage > $nbj2Courage){
            $scorej2points += 4;
            $scorej2valeurs ++;
        }

        if ($nbj1Honneur > $nbj2Honneur){
            $scorej1points += 5;
            $scorej1valeurs ++;
        }elseif ($nbj2Courage > $nbj2Courage){
            $scorej2points += 5;
            $scorej2valeurs ++;
        }

        if ($scorej1valeurs >= 4 || $scorej1points >= 11){
            $user = $game->getUser1();
            $game->setWinner($user);
            $entityManager->flush();
            return $this->json('j1win');
        }elseif ($scorej2valeurs >= 4 || $scorej2points >= 11){
            $user = $game->getUser2();
            $game->setWinner($user);
            $entityManager->flush();
            return $this->json('j2win');
        }else{
            $entityManager->flush();
            return $this->json('newround');
        }
        }




    /**
     * @Route("/change-tour-game/{game}", name="change_tour")
     */
    public function changeTour(
        EntityManagerInterface $entityManager,
        Request $request, Game $game
    ):Response{
        $event = $request->request->get('event');
        $joueur1 = 1;
        $joueur2 = 2;
        $round = $game->getRounds()[0];
        $pioche = $round->getPioche();
        $handj1 = $round->getUser1HandCards();
        $handj2 = $round->getUser2HandCards();
        if(!empty($pioche)){
            $carte = array_pop($pioche);
        }else{
            dump('plus de pioche');
        }
        if ($event == 'clicked'){
            dump($game->getQuiJoue());
            if ($game->getQuiJoue() == $joueur1){
                if (isset($carte)){
                    $handj2[]=$carte;
                    $round->setUser2HandCards($handj2);
                }
                $game->setQuiJoue($joueur2);
            }elseif ($game->getQuiJoue() == $joueur2){
                if (isset($carte)){
                    $handj1[]=$carte;
                    $round->setUser1HandCards($handj1);
                }
                $game->setQuiJoue($joueur1);
            }
        }
        if (isset($carte)){
            $round->setPioche($pioche);
        }
        $entityManager->flush();
        return $this->json(true);
    }

    /**
     * @param Game $game
     * @route("/refresh/{game}", name="refresh_plateau_game")
     */
    public function refreshPlateauGame(CardRepository $cardRepository, Game $game)
    {
        $cards = $cardRepository->findAll();
        $tCards = [];
        foreach ($cards as $card) {
            $tCards[$card->getId()] = $card;
        }

        if ($this->getUser()->getId() === $game->getUser1()->getId()) {
            $moi['handCards'] = $game->getRounds()[0]->getUser1HandCards();
            $moi['actions'] = $game->getRounds()[0]->getUser1Action();
            $moi['board'] = $game->getRounds()[0]->getUser1BoardCards();
            $adversaire['handCards'] = $game->getRounds()[0]->getUser2HandCards();
            $adversaire['actions'] = $game->getRounds()[0]->getUser2Action();
            $adversaire['board'] = $game->getRounds()[0]->getUser2BoardCards();
        } elseif ($this->getUser()->getId() === $game->getUser2()->getId()) {
            $moi['handCards'] = $game->getRounds()[0]->getUser2HandCards();
            $moi['actions'] = $game->getRounds()[0]->getUser2Action();
            dump($moi['actions']);
            $moi['board'] = $game->getRounds()[0]->getUser2BoardCards();
            $adversaire['handCards'] = $game->getRounds()[0]->getUser1HandCards();
            $adversaire['actions'] = $game->getRounds()[0]->getUser1Action();
            $adversaire['board'] = $game->getRounds()[0]->getUser1BoardCards();
            dump($moi['board']);
        } else {
            //redirection... je ne suis pas l'un des deux joueurs
        }

        return $this->render('game/plateau_game.html.twig', [
            'game' => $game,
            'set' => $game->getRounds()[0],
            'cards' => $tCards,
            'moi' => $moi,
            'adversaire' => $adversaire
        ]);
    }

    /**
     * @Route("/action-game/{game}", name="action_game")
     */
    public function actionGame(
        EntityManagerInterface $entityManager,
        Request $request, Game $game){


        $action = $request->request->get('action');
        $user = $this->getUser();
        $round = $game->getRounds()[0]; //a gérer selon le round en cours

        if ($game->getUser1()->getId() === $user->getId())
        {
            $joueur = 1;
        } elseif ($game->getUser2()->getId() === $user->getId()) {
            $joueur = 2;
        } else {
            /// On a un problème... On pourrait rediriger vers une page d'erreur.
        }

        switch ($action) {
            case 'secret':
                $carte = $request->request->get('carte');
                if ($joueur === 1) {
                    $actions = $round->getUser1Action(); //un tableau...
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $round->setUser1Action($actions); //je mets à jour le tableau
                    $main = $round->getUser1HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser1HandCards($main);
                }elseif ($joueur === 2){
                    $actions = $round->getUser2Action(); //un tableau...
                    $actions['SECRET'] = [$carte]; //je sauvegarde la carte cachée dans mes actions
                    $round->setUser2Action($actions); //je mets à jour le tableau
                    $main = $round->getUser2HandCards();
                    $indexCarte = array_search($carte, $main); //je récupère l'index de la carte a supprimer dans ma main
                    unset($main[$indexCarte]); //je supprime la carte de ma main
                    $round->setUser2HandCards($main);
                }
                break;
            case 'depot':
                $carte1 = $request->request->get('carte1');
                $carte2 = $request->request->get('carte2');
                if ($joueur === 1) {
                    $actions = $round->getUser1Action(); //un tableau...
                    $actions['DEPOT'] = [$carte1, $carte2]; //je sauvegarde les cartes masquées dans mes actions
                    $round->setUser1Action($actions); //je mets à jour le tableau
                    $main = $round->getUser1HandCards();
                    $indexCarte1 = array_search($carte1, $main); //je récupère l'index de ma première carte a supprimer dans ma main
                    $indexCarte2 = array_search($carte2, $main); //je récupère l'index de ma deuxième carte a supprimer dans ma main
                    unset($main[$indexCarte1], $main[$indexCarte2]); //je supprime les cartes de ma main
                    $round->setUser1HandCards($main);
                }elseif ($joueur === 2){
                    $actions = $round->getUser2Action(); //un tableau...
                    $actions['DEPOT'] = [$carte1, $carte2]; //je sauvegarde les cartes masquées dans mes actions
                    $round->setUser2Action($actions); //je mets à jour le tableau
                    $main = $round->getUser2HandCards();
                    $indexCarte1 = array_search($carte1, $main); //je récupère l'index de ma première carte a supprimer dans ma main
                    $indexCarte2 = array_search($carte2, $main); //je récupère l'index de ma deuxième carte a supprimer dans ma main
                    unset($main[$indexCarte1], $main[$indexCarte2]); //je supprime les cartes de ma main
                    $round->setUser2HandCards($main);
                }
                break;
            case 'offre':
                dump($round->getPioche());
                $carte1 = $request->request->get('carte1');
                $carte2 = $request->request->get('carte2');
                $carte3 = $request->request->get('carte3');
                if ($joueur === 1) {
                    $actions = $round->getUser1Action(); //un tableau...
                    $actions['OFFRE']['cartesInitiales'] = [$carte1, $carte2, $carte3]; //je sauvegarde les cartes masquées dans mes actions
                    $actions['OFFRE']['cartesAdversaire'] = [];
                    $round->setUser1Action($actions); //je mets à jour le tableau
                    $main = $round->getUser1HandCards();
                    $indexCarte1 = array_search($carte1, $main); //je récupère l'index de ma première carte a supprimer dans ma main
                    $indexCarte2 = array_search($carte2, $main); //je récupère l'index de ma deuxième carte a supprimer dans ma main
                    $indexCarte3 = array_search($carte3, $main); //je récupère l'index de ma troisième carte a supprimer dans ma main
                    unset($main[$indexCarte1], $main[$indexCarte2], $main[$indexCarte3]); //je supprime les cartes de ma main
                    $round->setUser1HandCards($main);
                }elseif ($joueur === 2){
                    $actions = $round->getUser2Action(); //un tableau...
                    $actions['OFFRE']['cartesInitiales'] = [$carte1, $carte2, $carte3]; //je sauvegarde les cartes masquées dans mes actions
                    $actions['OFFRE']['cartesAdversaire'] = [];
                    $round->setUser2Action($actions); //je mets à jour le tableau
                    $main = $round->getUser2HandCards();
                    $indexCarte1 = array_search($carte1, $main); //je récupère l'index de ma première carte a supprimer dans ma main
                    $indexCarte2 = array_search($carte2, $main); //je récupère l'index de ma deuxième carte a supprimer dans ma main
                    $indexCarte3 = array_search($carte3, $main); //je récupère l'index de ma troisième carte a supprimer dans ma main
                    unset($main[$indexCarte1], $main[$indexCarte2], $main[$indexCarte3]); //je supprime les cartes de ma main
                    $round->setUser2HandCards($main);
                }
                break;
            case 'echange':
                $carte1 = $request->request->get('carte1');
                $carte2 = $request->request->get('carte2');
                $carte3 = $request->request->get('carte3');
                $carte4 = $request->request->get('carte4');
                if ($joueur === 1) {
                    $actions = $round->getUser1Action(); //un tableau...
                    $actions['ECHANGE']['cartesInitiales']['premierdouble'] = [$carte1, $carte2]; //je sauvegarde les cartes masquées dans mes actions
                    $actions['ECHANGE']['cartesInitiales']['deuxiemedouble'] = [$carte3, $carte4]; //je sauvegarde les cartes masquées dans mes actions
                    $actions['ECHANGE']['cartesAdversaire'] = [];
                    $round->setUser1Action($actions); //je mets à jour le tableau
                    $main = $round->getUser1HandCards();
                    $indexCarte1 = array_search($carte1, $main); //je récupère l'index de ma première carte a supprimer dans ma main
                    $indexCarte2 = array_search($carte2, $main); //je récupère l'index de ma deuxième carte a supprimer dans ma main
                    $indexCarte3 = array_search($carte3, $main); //je récupère l'index de ma troisième carte a supprimer dans ma main
                    $indexCarte4 = array_search($carte4, $main); //je récupère l'index de ma troisième carte a supprimer dans ma main
                    unset($main[$indexCarte1], $main[$indexCarte2], $main[$indexCarte3], $main[$indexCarte4]); //je supprime les cartes de ma main
                    $round->setUser1HandCards($main);
                }elseif ($joueur === 2){
                    $actions = $round->getUser2Action(); //un tableau...
                    $actions['ECHANGE']['cartesInitiales']['premierdouble'] = [$carte1, $carte2]; //je sauvegarde les cartes masquées dans mes actions
                    $actions['ECHANGE']['cartesInitiales']['deuxiemedouble'] = [$carte3, $carte4]; //je sauvegarde les cartes masquées dans mes actions
                    $actions['ECHANGE']['cartesAdversaire'] = [];
                    $round->setUser2Action($actions); //je mets à jour le tableau
                    $main = $round->getUser2HandCards();
                    $indexCarte1 = array_search($carte1, $main); //je récupère l'index de ma première carte a supprimer dans ma main
                    $indexCarte2 = array_search($carte2, $main); //je récupère l'index de ma deuxième carte a supprimer dans ma main
                    $indexCarte3 = array_search($carte3, $main); //je récupère l'index de ma troisième carte a supprimer dans ma main
                    $indexCarte4 = array_search($carte4, $main); //je récupère l'index de ma troisième carte a supprimer dans ma main
                    unset($main[$indexCarte1], $main[$indexCarte2], $main[$indexCarte3], $main[$indexCarte4]); //je supprime les cartes de ma main
                    $round->setUser2HandCards($main);
                }
                break;
            case 'offrevalid':
                $carte = $request->request->get('carte');
                if ($joueur === 1) {
                    $actions = $round->getUser2Action(); //un tableau...
                    $boardUser1 = $round->getUser1BoardCards();
                    $boardUser2 = $round->getUser2BoardCards();
                    $actions['OFFRE']['cartesAdversaire'] = [$carte];
                    $indexCarte = array_search($carte, $actions['OFFRE']['cartesInitiales']);
                    unset($actions['OFFRE']['cartesInitiales'][$indexCarte]);
                    $boardUser1[] = array_pop($actions['OFFRE']['cartesAdversaire']);
                    $boardUser2[] = array_pop($actions['OFFRE']['cartesInitiales']);
                    $boardUser2[] = array_pop($actions['OFFRE']['cartesInitiales']);
                    $round->setUser1BoardCards($boardUser1);
                    $round->setUser2BoardCards($boardUser2);
                    $round->setUser2Action($actions); //je mets à jour le tableau
                }elseif ($joueur === 2){
                    $actions = $round->getUser1Action(); //On selectionne le tableau de l'adversaire
                    $boardUser1 = $round->getUser1BoardCards();
                    $boardUser2 = $round->getUser2BoardCards();
                    $actions['OFFRE']['cartesAdversaire'] = [$carte];
                    dump($actions['OFFRE']['cartesInitiales']);
                    $indexCarte = array_search($carte, $actions['OFFRE']['cartesInitiales']);
                    unset($actions['OFFRE']['cartesInitiales'][$indexCarte]); //je supprime les cartes de ma main
                    $boardUser2[] = array_pop($actions['OFFRE']['cartesAdversaire']);
                    $boardUser1[] = array_pop($actions['OFFRE']['cartesInitiales']);
                    $boardUser1[] = array_pop($actions['OFFRE']['cartesInitiales']);
                    $round->setUser1Action($actions); //je mets à jour le tableau
                    $round->setUser1BoardCards($boardUser1);
                    $round->setUser2BoardCards($boardUser2);
                }
                break;

            case 'echangevalid':
                $groupe = $request->request->get('groupe');
                if ($joueur === 1) {
                    $actions = $round->getUser2Action(); //un tableau...
                    $boardUser1 = $round->getUser1BoardCards();
                    $boardUser2 = $round->getUser2BoardCards();
                    if ($groupe == 'groupe1'){
                        $actions['ECHANGE']['cartesAdversaire'] = $actions['ECHANGE']['cartesInitiales']['premierdouble'];
                        $actions['ECHANGE']['cartesInitiales'] = $actions['ECHANGE']['cartesInitiales']['deuxiemedouble'];
                        unset($actions['ECHANGE']['cartesInitiales']['premierdouble'], $actions['ECHANGE']['cartesInitiales']['deuxiemedouble']);
                    }
                    if ($groupe == 'groupe2'){
                        $actions['ECHANGE']['cartesAdversaire'] = $actions['ECHANGE']['cartesInitiales']['deuxiemedouble'];
                        $actions['ECHANGE']['cartesInitiales'] = $actions['ECHANGE']['cartesInitiales']['premierdouble'];
                        unset($actions['ECHANGE']['cartesInitiales']['premierdouble'], $actions['ECHANGE']['cartesInitiales']['deuxiemedouble']);
                    }
                    $boardUser1[] = array_pop($actions['ECHANGE']['cartesAdversaire']);
                    $boardUser1[] = array_pop($actions['ECHANGE']['cartesAdversaire']);
                    $boardUser2[] = array_pop($actions['ECHANGE']['cartesInitiales']);
                    $boardUser2[] = array_pop($actions['ECHANGE']['cartesInitiales']);
                    $round->setUser1BoardCards($boardUser1);
                    $round->setUser2BoardCards($boardUser2);
                    $round->setUser2Action($actions); //je mets à jour le tableau
                }elseif ($joueur === 2){
                    $actions = $round->getUser1Action(); //un tableau...
                    $boardUser1 = $round->getUser1BoardCards();
                    $boardUser2 = $round->getUser2BoardCards();
                    if ($groupe == 'groupe1'){
                        $actions['ECHANGE']['cartesAdversaire'] = $actions['ECHANGE']['cartesInitiales']['premierdouble'];
                        $actions['ECHANGE']['cartesInitiales'] = $actions['ECHANGE']['cartesInitiales']['deuxiemedouble'];
                        dump($actions['ECHANGE']['cartesInitiales']);
                        dump($actions['ECHANGE']['cartesAdversaire']);
                        unset($actions['ECHANGE']['cartesInitiales']['premierdouble'], $actions['ECHANGE']['cartesInitiales']['deuxiemedouble']);
                    }
                    if ($groupe == 'groupe2'){
                        $actions['ECHANGE']['cartesAdversaire'] = $actions['ECHANGE']['cartesInitiales']['deuxiemedouble'];
                        $actions['ECHANGE']['cartesInitiales'] = $actions['ECHANGE']['cartesInitiales']['premierdouble'];
                        dump($actions['ECHANGE']['cartesInitiales']);
                        dump($actions['ECHANGE']['cartesAdversaire']);
                        unset($actions['ECHANGE']['cartesInitiales']['premierdouble'], $actions['ECHANGE']['cartesInitiales']['deuxiemedouble']);
                    }
                    $boardUser2[] = array_pop($actions['ECHANGE']['cartesAdversaire']);
                    $boardUser2[] = array_pop($actions['ECHANGE']['cartesAdversaire']);
                    $boardUser1[] = array_pop($actions['ECHANGE']['cartesInitiales']);
                    $boardUser1[] = array_pop($actions['ECHANGE']['cartesInitiales']);
                    $round->setUser1BoardCards($boardUser1);
                    $round->setUser2BoardCards($boardUser2);
                    $round->setUser1Action($actions); //je mets à jour le tableau
                }
                break;
        }

        $entityManager->flush();

        return $this->json(true);
    }
}
