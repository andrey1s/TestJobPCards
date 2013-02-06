<?php

namespace Acme\GameBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Acme\GameBundle\Entity\Card;

/**
 * Description of LoadCardData
 *
 * @author andrey <asamusev@archer-soft.com>
 */
class LoadCardData implements FixtureInterface
{

    public function load(ObjectManager $manager)
    {

        $dir = dirname(dirname(__DIR__)) . '/Resources/public/cards/';
        chdir($dir);
        $data = glob('*.png');
        array_walk($data, function(&$val) {
                    $val = 'bundles/acmegame/cards/' . $val;
                });
        foreach ($data as $val) {
            $card = new Card($val);
            $manager->persist($card);
        }
        $manager->flush();
    }

}