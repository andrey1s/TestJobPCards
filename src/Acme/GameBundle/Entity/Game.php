<?php

namespace Acme\GameBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Game
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Game
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
     * @var string
     *
     * @ORM\Column(name="keyGame", type="string", length=255, unique=true)
     */
    private $keyGame;

    /**
     * @var Card
     *
     * @ManyToMany(targetEntity="Card")
     */
    private $cards;

    /**
     * @var array
     *
     * @ORM\Column(name="statusCards", type="array")
     */
    private $statusCards;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="datetime")
     */
    private $date;

    /**
     *
     * @var array
     */
    private $paths = array();

    public function __construct($name, $cards)
    {
        $this->cards = new ArrayCollection();
        $this->setKeyGame($name);
        $this->setDate(new \DateTime);
        $status = array();
        foreach ($cards as $card) {
            $this->addCard($card);
            $status[] = false;
        }
        $this->setStatusCards($status);
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
     * Set keyGame
     *
     * @param string $keyGame
     * @return Game
     */
    public function setKeyGame($keyGame)
    {
        $this->keyGame = $keyGame;

        return $this;
    }

    /**
     * Get keyGame
     *
     * @return string 
     */
    public function getKeyGame()
    {
        return $this->keyGame;
    }

    /**
     * Set cards
     *
     * @param array $cards
     * @return Game
     */
    public function setCards($cards)
    {
        $this->cards = $cards;

        return $this;
    }

    /**
     * Get cards
     *
     * @return ArrayCollection
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * Add card
     *
     * @param Card $card
     * @return Game
     */
    public function addCard(Card $card)
    {
        $this->getCards()->add($card);

        return $this;
    }

    /**
     * Id card
     *
     * @param integer $id
     * @return Card|false
     */
    public function getCard($id)
    {
        return $this->getCards()->get($id);
    }

    /**
     * get Card Paths
     * @return array
     */
    public function getPaths()
    {
        if (count($this->paths)) {
            return $this->paths;
        } else {
            foreach ($this->cards as $card) {
                $this->paths[] = $card->getPath();
            }
        }
        return $this->paths;
    }

    /**
     * get Card Patch by Id
     *
     * @param integer $id
     * @return boolean|string
     */
    public function getPath($id)
    {
        if (isset($this->paths[$id])) {
            return $this->paths[$id];
        }

        return false;
    }

    /**
     * get Opened cards
     *
     * @return array
     */
    public function getOpenPath()
    {
        $return = array();
        foreach ($this->statusCards as $key => $value) {
            if ($value && isset($this->paths[$key])) {
                $return[$key] = $this->paths[$key];
            }
        }
        return $return;
    }

    /**
     * Set statusCards
     *
     * @param boolean $statusCards
     * @return Game
     */
    public function setStatusCards(array $statusCards)
    {
        $this->statusCards = $statusCards;

        return $this;
    }

    /**
     * Get statusCards
     *
     * @return boolean 
     */
    public function getStatusCards()
    {
        return $this->statusCards;
    }

    /**
     * trigger Status
     *
     * @return Game
     */
    public function triggerStatusCard($id)
    {
        if (isset($this->statusCards[$id])) {
            $this->statusCards[$id] = !$this->statusCards[$id];
        }

        return $this;
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
