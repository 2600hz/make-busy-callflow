<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;

class UserTestCase extends TestCase
{
    public static $test_account;
    public static $a_user;
    public static $a_device_1;
    public static $a_device_2;
    public static $b_user;
    public static $b_device_1;
    public static $b_device_2;
    public static $c_user;
    public static $c_device_1;
    public static $c_device_2;
    public static $offline_user;
    public static $offline_device_1;
    public static $offline_device_2;
    public static $offnet_resource;
    public static $emergency_resource;
    public static $ring_group;

    const A_NUMBER         = '2000';
    const B_NUMBER         = '2001';
    const C_NUMBER         = '2002';
    const OFFLINE_NUMBER   = '2003';
    const EMERGENCY_NUMBER = '911';
    const RINGGROUP_NUMBER  = '2111';
    const CALL_FWD_ENABLE  = '*72';
    const CALL_FWD_DISABLE = '*73';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $acc = new TestAccount("UserTestCase");
        self::$test_account = $acc;

        self::$a_user= $acc->createUser();
        self::$a_user->createUserCallFlow([self::A_NUMBER]);

        self::$b_user= $acc->createUser();
        self::$b_user->createUserCallFlow([self::B_NUMBER]);

        self::$c_user= $acc->createUser();
        self::$c_user->createUserCallFlow([self::C_NUMBER]);

        self::$offline_user= $acc->createUser();
        self::$offline_user->createUserCallFlow([self::OFFLINE_NUMBER]);

        self::$a_device_1 = $acc->createDevice("auth", TRUE, ['owner_id' => self::$a_user->getId()]);
        self::$a_device_2 = $acc->createDevice("auth", TRUE, ['owner_id' => self::$a_user->getId()]);

        self::$b_device_1 = $acc->createDevice("auth", TRUE, ['owner_id' => self::$b_user->getId()]);
        self::$b_device_2 = $acc->createDevice("auth", TRUE, ['owner_id' => self::$b_user->getId()]);

        self::$c_device_1 = $acc->createDevice("auth", TRUE, ['owner_id' => self::$c_user->getId()]);
        self::$c_device_2 = $acc->createDevice("auth", TRUE, ['owner_id' => self::$c_user->getId()]);

        self::$offline_device_1 = $acc->createDevice("auth", FALSE, ['owner_id' => self::$offline_user->getId()]);
        self::$offline_device_2 = $acc->createDevice("auth", FALSE, ['owner_id' => self::$offline_user->getId()]);

        self::$offnet_resource  = $acc->createResource("carrier", ["^\\+1(\d{10})$"], "+1");
        self::$emergency_resource = $acc->createResource("carrier", ["^(911)$"], null, TRUE);

        self::sync_sofia_profile("auth", self::$a_device_1->isLoaded(), 6);

        $b_user_id = self::$b_user->getId();
        $offline_user_id = self::$offline_user->getId();
        self::$ring_group = $acc->createRingGroup(
            [ self::RINGGROUP_NUMBER ],
            [
                ["id" => $b_user_id, "type" => "user"],
                ["id" => $offline_user_id, "type" => "user"]
            ]
        );
    }

}