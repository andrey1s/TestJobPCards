<?php

namespace Acme\GameBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToOne;

/**
 * Log
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Log
{

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var GUser
     *
     * @ManyToOne(targetEntity="GUser")
     */
    private $user;

    /**
     * @var string
     *
     * @ORM\Column(name="actions", type="string", length=255)
     */
    private $actions;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    public function __construct($actions, GUser $user)
    {
        $this->setDate(new \DateTime);
        $this->setUser($user);
        $this->setActions($actions);
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * get User
     * 
     * @return GUser
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * set User
     *
     * @param GUser $user
     * @return Log
     */
    public function setUser(GUser $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Set actions
     *
     * @param string $actions
     * @return Log
     */
    public function setActions($actions)
    {
        $this->actions = $actions;

        return $this;
    }

    /**
     * Get actions
     *
     * @return string 
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Game
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

}
