<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;
use \MakeBusy\Common\Log;

class IncomingTestCase extends TestCase
{
    protected static $carrier;
    protected static $carrier_number;
    protected static $connectivity;
    protected static $offnet;

    protected static $a_device;

    protected static $carrier_numbers = [ "IncomingTestCase" => "+15552223001" ];
    protected static $number;

    const A_EXT = '1001';

    protected static function system_configs() {
        return ["privacy", "number_manager"];
    }

    public static function setUpCase() {
    	Log::debug("SETTING UP ACCOUNT INCOMING");
    	
        self::$account->system_config("number_manager/default")->fetch()->patch(["local_feature_override"], true);

        self::$number = self::getCarrierNumber(self::$account->getBaseType());
        if (is_null(self::$number)) {
            Log::warning("Carrier number is not defined for test case: " . self::$account->getBaseType() . ", skip setup");
            return;
        }

        self::$offnet = self::$account->createResource("carrier", ["^\\+1(\d{10})$"], "+1", false, false);

        self::$a_device = self::$account->createDevice("auth");
        self::$a_device->createCallflow([self::A_EXT, self::$number]);

        self::$connectivity = self::$account->createConnectivity();
        self::$carrier_number = self::$account->createPhoneNumber(self::$number, ['cnam' => 'CNAM', 'change_lookup' => true]);
        self::$carrier = self::$connectivity->addGateway("carrier", 'IP', self::getProfile("carrier")->getSipIp());
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
        $cfg = self::$account->system_config("privacy/default");
        $cfg->$name = $value;
        $cfg->save();
    }

}
