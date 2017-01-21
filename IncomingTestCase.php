<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Common\Log;

class IncomingTestCase extends TestCase
{
    protected static $account;
    protected static $test_account;
    protected static $carrier;
    protected static $carrier_number;
    protected static $connectivity;
    protected static $offnet;

    protected static $a_device;

    protected static $carrier_numbers = [ "IncomingTestCase" => "+15552223001" ];
    protected static $number;

    const A_EXT = '1001';

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();

        $acc = new TestAccount(get_called_class());
        self::$account = $acc;

        self::$number = self::getCarrierNumber($acc->getBaseType());
        if (is_null(self::$number)) {
            Log::warning("Carrier number is not defined for test case: " . $acc->getBaseType() . ", skip setup");
            return;
        }

        self::$offnet = $acc->createResource("carrier", ["^\\+1(\d{10})$"], "+1", false, false);

        self::$a_device = $acc->createDevice("auth");
        self::$a_device->createCallflow([self::A_EXT, self::$number]);

        self::$connectivity = $acc->createConnectivity();
        self::$carrier_number = $acc->createPhoneNumber(self::$number, ['cnam' => 'CNAM', 'change_lookup' => true]);
        self::$carrier = self::$connectivity->addGateway("carrier", 'IP', self::getProfile("carrier")->getSipIp());

        self::syncSofiaProfile("auth", $acc->isLoaded());
        self::syncSofiaProfile("carrier", $acc->isLoaded());
    }

    public static function getCarrierNumber($test_case) {
        if(isset(self::$carrier_numbers[$test_case])) {
            return self::$carrier_numbers[$test_case];
        } else {
            return null;
        }
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
