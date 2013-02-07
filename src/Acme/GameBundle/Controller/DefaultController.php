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

class DefaultController extends Controller
{
    /**
     * keys memcache
     */
    const MEMCACHE_KEY_USERS = 'users';
    const MEMCACHE_KEY_GAME = 'key';
    const MEMCACHE_KEY_LOG = 'log';

    /**
     * @Route("/", name="game_index")
     * @Template()
     */
    public function indexAction()
    {
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
                    $users = $memcache->get($game->getId() . ':' . self::MEMCACHE_KEY_USERS);
                    $user = $em->getRepository('AcmeGameBundle:GUser')
                            ->findOneBy(array('game' => $game, 'username' => $obj->getName()));
                } else {
                    $cards = $em->getRepository('AcmeGameBundle:Card')->findAll();
                    shuffle($cards);
                    $cards = array_slice($cards, 0, 10, true);
                    $game = new Entity\Game($obj->getGame(), $cards);
                    $em->persist($game);
                    $memcache->set($game->getId() . ':' . self::MEMCACHE_KEY_GAME, uniqid());
                }
                $gameId = $game->getId();
                $userStatus = false;
                if (!$user) {
                    $ip = $this->getRequest()->getClientIp();
                    $user = new Entity\GUser($obj->getName(), $game, $ip);
                    $em->persist($user);
                    $em->flush();
                } else {
                    $keyGame = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_GAME);
                    $userStatus = $memcache->get($gameId . ':' . $keyGame . ':' . $user->getId());
                }
                if (!$userStatus) {
                    $game->getPaths();
                    $users[$user->getId()] = array('ip' => $user->getIp(), 'username' => $user->getUsername());
                    $memcache->set($gameId . ':' . self::MEMCACHE_KEY_USERS, $users);
                    $session->set('user', $user);
                    return $this->redirect($this->generateUrl('game_game'));
                }
                $error = new FormError('Please wait a name already in use, 90 sec');
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
        /* @var $memcache \Memcache */
        $memcache = $this->container->get('memcache');
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
            $path = array($id => $user->getGame()->getPath($id));
            $entityLog = new Entity\Log('change status card ' . $id, $em->find('AcmeGameBundle:GUser', $user->getId()));
            $em->persist($entityLog);
            $em->flush();
            $log = array(
                'username' => $entityLog->getUser()->getUsername(),
                'action' => $entityLog->getActions()
            );
            $status = $user->getGame()->getStatusCards();
            $memcache->set($gameId . ':status', $status);
            $memcache->set($gameId . ':' . self::MEMCACHE_KEY_GAME, $keyGame);
            $memcache->set($gameId . ':' . $keyGame . ':' . $user->getId(), true, 0, 90);
            $memcache->set($gameId . ':' . $keyGame . ':' . self::MEMCACHE_KEY_LOG, $log);
            $data = array(
                'status' => true,
                'data' => $status,
                'path' => $path,
                'log' => $log
            );
            return new Response(json_encode($data), 200, array('Content-type' => 'application/json'));
        } else {
            $users = $em->getRepository('AcmeGameBundle:GUser')
                    ->findBy(array('game' => $gameId));
            $logs = $em->getRepository('AcmeGameBundle:Log')
                            ->createQueryBuilder('l')
                            ->where('l.user IN (:user)')
                            ->setParameter('user', $users)
                            ->getQuery()->execute();
        }
        $users = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_USERS);

        return compact('user', 'users', 'logs');
    }

    /**
     * @Route("/game/status", name="game_status")
     * @Template()
     */
    public function gameStatusAction()
    {
        $session = $this->getRequest()->getSession();
        if (!$session->has('user')) {
            return new Response('Game Not Fount', 200);
        }
        $data = array('status' => true);
        /* @var $user \Acme\GameBundle\Entity\GUser */
        $user = $session->get('user');
        $gameId = $user->getGame()->getId();
        /* @var $memcache \Memcache */
        $memcache = $this->container->get('memcache');
        $keyGame = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_GAME);
        $data['users'] = $this->getUsersOnline($memcache, $gameId, $user);
        ;
        $userStatus = $memcache->get($gameId . ':' . $keyGame . ':' . $user->getId());
        $status = $memcache->get($gameId . ':status');
        if ($keyGame && $userStatus) {
            $data['status'] = false;
        } elseif ($keyGame && $status) {
            $data['data'] = $status;
            $data['log'] = $memcache->get($gameId . ':' . $keyGame . ':' . self::MEMCACHE_KEY_LOG);
        } else {
            $em = $this->getDoctrine()->getManager();
            if (!$keyGame) {
                $keyGame = uniqid();
                $memcache->set($gameId . ':' . self::MEMCACHE_KEY_GAME, $keyGame);
            }
            $status = $em->find('AcmeGameBundle:Game', $gameId)->getStatusCards();
            $memcache->set($gameId . ':status', $status);
            $em->flush();
            $data['data'] = $status;
        }
        $user->getGame()->setStatusCards($status);
        if (isset($data['data'])) {
            $data['path'] = $user->getGame()->getOpenPath();
        }
        $memcache->set($gameId . ':' . $keyGame . ':' . $user->getId(), true, 0, 90);

        return new Response(json_encode($data), 200, array('Content-type' => 'application/json'));
    }

    /**
     * get User Online
     *
     * @param \Memcache $memcache
     * @param string $gameId
     * @param Entity\GUser $user
     * @return boolean if update user
     */
    private function getUsersOnline(\Memcache $memcache, $gameId, $user)
    {
        $users = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_USERS);
        $gameKey = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_GAME);
        foreach ($users as $key => $value) {
            if (!$memcache->get($gameId . ':' . $gameKey . ':' . $key)) {
                unset($users[$key]);
            }
        }
        $users[$user->getId()] = array('ip' => $user->getIp(), 'username' => $user->getUsername());
        $memcache->set($gameId . ':' . self::MEMCACHE_KEY_USERS, $users, 0, 300);
        return $users;
    }

}
