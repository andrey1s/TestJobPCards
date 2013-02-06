<?php

namespace Acme\GameBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Acme\GameBundle\Form\Type\Game as GameType;
use Acme\GameBundle\Form\Object\Game as GameObj;
use Acme\GameBundle\Entity;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{

    const MEMCACHE_KEY_USERS = 'users';
    const MEMCACHE_KEY_GAME = 'key';

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
                $em = $this->getDoctrine()->getEntityManager();
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
                if (!$user) {
                    $ip = $this->getRequest()->getClientIp();
                    $user = new Entity\GUser($obj->getName(), $game, $ip);
                    $em->persist($user);
                    $em->flush();
                }
                $game->getPaths();
                $gameId = $game->getId();
                $users[$user->getId()] = array('ip' => $user->getIp(), 'username' => $user->getUsername());
                $memcache->set($gameId . ':' . self::MEMCACHE_KEY_USERS, $users);
                $session->set('user', $user);
                return $this->redirect($this->generateUrl('game_game'));
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
            return $this->createNotFoundException('Game Not Fount');
        }
        /* @var $user \Acme\GameBundle\Entity\GUser */
        $user = $session->get('user');
        $id = $this->getRequest()->get('id', false);
        $gameId = $user->getGame()->getId();
        if ($id !== false) {
            $key = uniqid();
            $user->getGame()->triggerStatusCard($id);
            $path = array($id => $user->getGame()->getPath($id));
            $em = $this->getDoctrine()->getEntityManager();
            $log = new Entity\Log('change status card ' . $id, $em->find('AcmeGameBundle:GUser', $user->getId()));
            $em->persist($log);
            $em->flush();
            $status = $user->getGame()->getStatusCards();
            $memcache->set($gameId . ':status', $status);
            $memcache->set($gameId . ':' . self::MEMCACHE_KEY_GAME, $key);
            $memcache->set($gameId . ':' . $key . ':' . $user->getId(), true);
            $data = array('status' => true, 'data' => $status, 'path' => $path);
            return new Response(json_encode($data), 200, array('Content-type' => 'application/json'));
        }
        $users = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_USERS);

        return compact('user', 'users');
    }

    /**
     * @Route("/game/status", name="game_status")
     * @Template()
     */
    public function gameStatusAction()
    {
        $session = $this->getRequest()->getSession();
        if (!$session->has('user')) {
            $this->createNotFoundException('Game Not Fount');
        }
        $data = array('status' => true);
        /* @var $user \Acme\GameBundle\Entity\GUser */
        $user = $session->get('user');
        $gameId = $user->getGame()->getId();
        /* @var $memcache \Memcache */
        $memcache = $this->container->get('memcache');
        $key = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_GAME);
        if ($this->getUsersOnline($memcache, $gameId, $user)) {
            $data['users'] = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_USERS);
        }
        $userStatus = $memcache->get($gameId . ':' . $key . ':' . $user->getId());
        $status = $memcache->get($gameId . ':status');
        if ($key && $userStatus && !isset($data['users'])) {
            $data['status'] = false;
        } elseif ($key && $status) {
            $data['data'] = $status;
        } else {
            $em = $this->getDoctrine()->getEntityManager();
            if (!$key) {
                $key = uniqid();
                $memcache->set($gameId . ':' . self::MEMCACHE_KEY_GAME, $key);
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
        $memcache->set($gameId . ':' . $key . ':' . $user->getId(), true, 0, 90);

        return new Response(json_encode($data), 200, array('Content-type' => 'application/json'));
    }

    /**
     * get User Online
     *
     * @param \Memcache $memcache
     * @param string $gameId
     * @return boolean if update user
     */
    private function getUsersOnline(\Memcache $memcache, $gameId, $user)
    {
        $return = false;
        $users = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_USERS);
        $gameKey = $memcache->get($gameId . ':' . self::MEMCACHE_KEY_GAME);
        foreach ($users as $key => $value) {
            if (!$memcache->get($gameId . ':' . $gameKey . ':' . $key)) {
                unset($users[$key]);
                $return = true;
            }
        }
        $users[$user->getId()] = array('ip' => $user->getIp(), 'username' => $user->getUsername());
        $memcache->set($gameId . ':' . self::MEMCACHE_KEY_USERS, $users, 0, 300);
        return $return;
    }

}
