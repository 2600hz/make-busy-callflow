<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;

class QuickCallTestCase extends TestCase {

    public static $test_account;

    public static $admin_user;
    public static $admin_device;

    public static $anon_user;
    public static $anon_device;

    public static $a_user;
    public static $a_device;

    public static $b_user;
    public static $b_dev_1;
    public static $b_dev_2;
    public static $b_dev_3;

    const A_EXT     = '5001';
    const B_EXT     = '5002';
    const PASSWORD  = 'passwerd';
    const CNAM      = 'Administrator';
    const CNUM      = '6288888888';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        $acc = new TestAccount(get_called_class());

        self::$admin_user = $acc->createUser(['password' => self::PASSWORD, 'allow_anonymous_quickcall' => FALSE]);
        self::$admin_device = self::$admin_user->createDevice("auth", TRUE, ['allow_anonymous_quickcalls' => FALSE]);

        self::$anon_user = $acc->createUser(['priv_level' => 'user', 'password' => self::PASSWORD, 'allow_anonymous_quickcalls' => TRUE]);
        self::$anon_device = self::$anon_user->createDevice("auth", TRUE, ['allow_anonymous_quickcalls' => TRUE]);

        self::$a_user = $acc->createUser();

        self::$a_device = $acc->createDevice("auth");
        self::$a_device->createCallflow([self::A_EXT]);

        self::$b_user = $acc->createUser(['password' => self::PASSWORD, 'allow_anoymous_quickcall' =>FALSE]);
        self::$b_dev_1 = self::$b_user->createDevice("auth", TRUE);
        self::$b_dev_2 = self::$b_user->createDevice("auth", TRUE);
        self::$b_dev_3 = self::$b_user->createDevice("auth", TRUE);

        self::syncSofiaProfile("auth", $acc->isLoaded(), 5);
    }
}