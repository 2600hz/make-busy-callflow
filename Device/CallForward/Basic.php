<?php
namespace KazooTests\Applications\Callflow\Device\CallForward;

use KazooTests\Applications\Callflow\DeviceTestCase;

class Basic extends DeviceTestCase {

    public function setUpTest() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$a_device->getGateway()->register();
    }

    public function tearDownTest() {
        self::$b_device->resetCfParams();
    }

    public function main($sip_uri) {
        $target = self::B_EXT .'@' . $sip_uri;

        $channel_a = self::ensureChannel( self::$a_device->originate($target) );
        $channel_c = self::ensureChannel( self::$c_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_c);
        self::hangupBridged($channel_a, $channel_c);
    }

}