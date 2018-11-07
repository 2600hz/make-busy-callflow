<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class PassEmptyCidName extends IncomingTestCase {

    public function setUpTest() {
        self::setConfig("block_anonymous_caller_id", true);
        self::setConfig("check_additional_anonymous_cid_names", true);
    }

    public function main($sip_uri) {
        $number = self::$carrier_number->toNpan();
        $target = self::$number .'@'. $sip_uri;
        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_name' => '']) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        self::ensureEvent($channel_a->waitPark());
        self::ensureAnswer($channel_a, $channel_b);
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }
}
