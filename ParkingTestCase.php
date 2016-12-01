<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;

class ParkingTestCase extends TestCase {
    public static $a_device;
    public static $b_device;
    public static $c_device;
    public static $realm;

    const A_EXT             = '4001';
    const B_EXT             = '4002';
    const C_EXT             = '4003';
    const PARKING_SPOT_1    = '*3101';
    const VALET             = '*4';
    const RETRIEVE          = '*5';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $acc = new TestAccount(get_called_class());

        self::$a_device = $acc->createDevice("auth", true);
        self::$a_device->createCallflow([self::A_EXT]);

        self::$b_device = $acc->createDevice("auth", true);
        self::$b_device->createCallflow([self::B_EXT]);

        self::$c_device = $acc->createDevice("auth", true);
        self::$c_device->createCallflow([self::C_EXT]);

        self::syncSofiaProfile("auth", $acc->isLoaded(), 3);
    }

}