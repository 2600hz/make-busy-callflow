<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;
use \MakeBusy\Common\Utils;
use \MakeBusy\Kazoo\Applications\Crossbar\SystemConfigs;

class PassCall extends IncomingTestCase {

    public function setUpTest() {
        self::setConfig("block_anonymous_caller_id", false);
    }

    public function tearDownTest() {
        self::setConfig("block_anonymous_caller_id", false);
    }

    public function main($sip_uri) {
        $number = self::$carrier_number->toNpan();
        $target = self::$number .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_privacy' => 'hide_name:hide_number:screen', 'sip_cid_type' => 'pid']) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );

        self::ensureAnswer($channel_a, $channel_b);
        self::ensureEvent($channel_a->waitPark());
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }

}