<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;

class IncomingTestCase extends TestCase
{
    protected static $account;
    protected static $test_account;
    protected static $carrier;
    protected static $carrier_number;
    protected static $connectivity;
    protected static $offnet;

    protected static $a_device;

    const A_EXT = '1001';
    const CARRIER_NUMBER = '+15552223001'; // this should be unique among *TestCase, or CLEAN=1 will be required

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        $acc = new TestAccount(get_called_class());
        self::$account = $acc;

        self::$offnet = $acc->createResource("carrier", ["^\\+1(\d{10})$"], "+1", false, false);

        self::$a_device = $acc->createDevice("auth");
        self::$a_device->createCallflow([self::A_EXT, self::CARRIER_NUMBER]);

        self::$connectivity = $acc->createConnectivity();
        self::$carrier_number = $acc->createPhoneNumber(self::CARRIER_NUMBER, ['cnam' => 'CNAM', 'change_lookup' => true]);
        self::$carrier = self::$connectivity->addGateway("carrier", 'IP', self::getProfile("carrier")->getSipIp());

        self::syncSofiaProfile("auth", $acc->isLoaded(), 1);
        self::syncSofiaProfile("carrier", $acc->isLoaded());
    }

    public static function getGateway($id) {
        return self::$connectivity->getGateway($id);
    }

    public static function setConfig($name, $value) {
        $cfg = self::$account->getAccount()->SystemConfig("stepswitch");
        $cfg->default->$name = $value;
        $cfg->save();
    }

}
