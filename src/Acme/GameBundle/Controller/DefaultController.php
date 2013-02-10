<?php

namespace Acme\GameBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Acme\GameBundle\Form\Type\Game as GameType;
use Acme\GameBundle\Form\Object\Game as GameObj;
use Symfony\Component\Form\FormError;
use Acme\GameBundle\Entity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * keys memcache
     */

    const MEMCACHE_KEY_USER = 'user';

    /**
     * @Route("/", name="game_index")
     * @Template()
     */
    public function indexAction()
    {
        $req = new Request();
        $req->getHttpHost();
        $obj = new GameObj();
        $form = $this->createForm(new GameType(), $obj);
        if ($this->getRequest()->isMethod('POST')) {
            $form->bind($this->getRequest());
            if ($form->isValid()) {
                $user = false;
                $session = $this->getRequest()->getSession();
                $em = $this->getDoctrine()->getManager();
                $game = $em->getRepository('AcmeGameBundle:Game')
                        ->findOneBy(array('keyGame' => $obj->getGame()));
                /* @var $memcache \Memcache */
                $memcache = $this->container->get('memcache');
                $users = array();
                if ($game) {
                    $user = $em->getRepository('AcmeGameBundle:GUser')
                            ->findOneBy(array('game' => $game, 'username' => $obj->getName()));
                } else {
                    $cards = $em->getRepository('AcmeGameBundle:Card')->findAll();
                    shuffle($cards);
                    $cards = array_slice($cards, 0, 10, true);
                    $game = new Entity\Game($obj->getGame(), $cards);
                    $em->persist($game);
                }
                $userStatus = false;
                if (!$user) {
                    $ip = $this->getRequest()->getClientIp();
                    $user = new Entity\GUser($obj->getName(), $game, $ip);
                    $em->persist($user);
                    $em->flush();
                } else {
                    $userStatus = $memcache->get(self::MEMCACHE_KEY_USER . ':' . $user->getId());
                }
                if (!$userStatus) {
                    $game->getPaths();
                    $session->set('user', $user);
                    return $this->redirect($this->generateUrl('game_game'));
                }
                $error = new FormError('Name already in use');
                $form->addError($error);
            }
        }
        $form = $form->createView();

        return compact('form');
    }

    /**
     * @Route("/game", name="game_game")
     * @Template()
     */
    public function gameAction()
    {
        $session = $this->getRequest()->getSession();
        if (!$session->has('user')) {
            return new Response('Game Not Fount', 200);
        }
        /* @var $user \Acme\GameBundle\Entity\GUser */
        $user = $session->get('user');
        $id = $this->getRequest()->get('id', false);
        $gameId = $user->getGame()->getId();
        /* @var $em Doctrine\ORM\EntityManager */
        $em = $this->getDoctrine()->getManager();
        if ($id !== false) {
            $keyGame = uniqid();
            $game = $em->find('AcmeGameBundle:Game', $gameId);
            $game->triggerStatusCard($id);
            $user->getGame()->triggerStatusCard($id);
            $entityLog = new Entity\Log('change status card ' . $id, $em->find('AcmeGameBundle:GUser', $user->getId()));
            $em->persist($entityLog);
            $em->flush();
            $data = array('id' => $id, 'path' => $user->getGame()->getPath($id));
            return new Response(json_encode($data), 200, array('Content-type' => 'application/json'));
        } else {
            /* @var $memcache \Memcache */
            $memcache = $this->container->get('memcache');
            $users = $em->getRepository('AcmeGameBundle:GUser')->findBy(array('game' => $gameId));
            $logs = $em->getRepository('AcmeGameBundle:Log')
                            ->createQueryBuilder('l')
                            ->where('l.user IN (:user)')
                            ->orderBy('l.date', "DESC")
                            ->setParameter('user', $users)
                            ->getQuery()->execute();
            $online = array();
            foreach ($users as $key => $value) {
                $userStatus = $memcache->get(self::MEMCACHE_KEY_USER . ':' . $value->getId());
                if ($userStatus) {
                    $online[$value->getId()] = $value;
                }
            }
            $online[$user->getId()] = $user;
        }

        return compact('user', 'online', 'logs');
    }

}
