<?php

namespace Acme\GameBundle\Form\Object;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Description of Game
 *
 * @author andrey <asamusev@archer-soft.com>
 */
class Game
{

    /**
     * @Assert\NotBlank
     * @Assert\Length(min = "2",max = "255")
     * @Assert\Regex("/^\w+/")
     */
    private $Game;

    /**
     * @Assert\NotBlank
     * @Assert\Length(min = "2",max = "255")
     * @Assert\Regex("/^\w+/")
     */
    private $Name;
    public function getGame()
    {
        return $this->Game;
    }

    public function setGame($Game)
    {
        $this->Game = $Game;
    }

    public function getName()
    {
        return $this->Name;
    }

    public function setName($Name)
    {
        $this->Name = $Name;
    }


}
