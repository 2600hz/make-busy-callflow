<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class DeviceCallerId extends QuickCallTestCase {
    public function main($sip_uri) {
        self::$admin_device->getDevice()->quickcall(self::A_EXT, ['cid-number' => self::CNUM, 'cid-name' => self::CNAM]);
        $channel_a = self::ensureChannel( self::$admin_device->waitForInbound() );
        $channel_a->answer();
        self::ensureEvent($channel_a->waitAnswer());

        $channel_b = self::ensureChannel( self::$a_device->waitForInbound() );
        $channel_b->answer();
        self::ensureEvent($channel_b->waitAnswer());

        $this->assertEquals($channel_b->getCallerIdNumber(), self::CNUM);
        $this->assertEquals($channel_b->getCallerIdName(), self::CNAM);

        self::hangupBridged($channel_a, $channel_b);
    }
}
