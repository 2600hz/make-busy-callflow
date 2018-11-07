<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class BlockAnonymousCidNames extends IncomingTestCase {

    public function setUpTest() {
        self::setConfig("block_anonymous_caller_id", true);
        self::setConfig("check_additional_anonymous_cid_names", true);
    }

    public function main($sip_uri) {
        $number = self::$carrier_number->toNpan();
        $target = self::$number .'@'. $sip_uri;

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_name' => 'anonymous']) );
        $channel_b = self::$a_device->waitForInbound();
        self::assertEmpty($channel_b);

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_name' => 'Anonymous']) );
        $channel_b = self::$a_device->waitForInbound();
        self::assertEmpty($channel_b);

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_name' => 'restricted']) );
        $channel_b = self::$a_device->waitForInbound();
        self::assertEmpty($channel_b);

        $channel_a = self::ensureChannel( self::$offnet->originate($target, 5, ['origination_caller_id_name' => 'Restricted']) );
        $channel_b = self::$a_device->waitForInbound();
        self::assertEmpty($channel_b);
    }
}
