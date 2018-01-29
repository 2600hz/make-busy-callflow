<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class DeviceCall extends QuickCallTestCase {
    public function main($sip_uri) {
        self::$admin_device->getDevice()->quickcall(self::A_EXT);
        $channel_a = self::ensureChannel( self::$admin_device->waitForInbound() );
        $channel_a->answer();
        self::ensureAnswered($channel_a);
        
        $channel_b = self::ensureChannel( self::$a_device->waitForInbound(null, 20) );
        $channel_b->answer();
        self::ensureAnswered($channel_b);
        
        self::ensureTwoWayAudio($channel_a, $channel_b);
        self::hangupBridged($channel_a, $channel_b);
    }
}