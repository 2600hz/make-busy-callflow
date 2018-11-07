<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class PassNonAnonymousCids extends IncomingTestCase {

    public function setUpTest() {
        self::setConfig("block_anonymous_caller_id", true);
        self::setConfig("check_additional_anonymous_cid_names", true);
        self::setConfig("check_additional_anonymous_cid_numbers", true);
    }

    public function main($sip_uri) {
        $number = self::$carrier_number->toNpan();
        $target = self::$number .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_name' => 'anonymou', 'origination_caller_id_number' => '12345']) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        self::ensureEvent($channel_a->waitPark());
        self::ensureAnswer($channel_a, $channel_b);
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_number' => 'Anonymou', 'origination_caller_id_number' => '12345']) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        self::ensureEvent($channel_a->waitPark());
        self::ensureAnswer($channel_a, $channel_b);
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_name' => 'restricte', 'origination_caller_id_number' => '12345']) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        self::ensureEvent($channel_a->waitPark());
        self::ensureAnswer($channel_a, $channel_b);
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_number' => 'Restricte', 'origination_caller_id_number' => '12345']) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        self::ensureEvent($channel_a->waitPark());
        self::ensureAnswer($channel_a, $channel_b);
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_number' => '5024445555', 'origination_caller_id_number' => '12345']) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        self::ensureEvent($channel_a->waitPark());
        self::ensureAnswer($channel_a, $channel_b);
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_name' => 'unknown', 'origination_caller_id_number' => '12345']) );
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        self::ensureEvent($channel_a->waitPark());
        self::ensureAnswer($channel_a, $channel_b);
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }
}
