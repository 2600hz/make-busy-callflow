<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class DeviceCall extends QuickCallTestCase {
    public function main($sip_uri) {
        self::$admin_device->getDevice()->quickcall(self::A_EXT);
        $ch_a = self::ensureChannel( self::$admin_device->waitForInbound() );
        $ch_a->answer();
        self::ensureEvent($ch_a->waitAnswer());

        $ch_b = self::ensureChannel( self::$a_device->waitForInbound() );
        $ch_b->answer();
        self::ensureEvent($ch_b->waitAnswer());

        self::hangupBridged($ch_a, $ch_b);
    }
}