<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

class DeviceCallerId extends QuickCallTestCase {
    public function main($sip_uri) {
        self::markTestIncomplete('Known issue: KAZOO-5118');

        self::$admin_device->getDevice()->quickcall(self::A_EXT, ['cid-number' => self::CNUM, 'cid-name' => self::CNAM]);
        $ch_a = self::ensureChannel( self::$admin_device->waitForInbound() );
        $ch_a->answer();
        self::ensureEvent($ch_a->waitAnswer());

        $ch_b = self::ensureChannel( self::$a_device->waitForInbound() );
        $ch_b->answer();
        self::ensureEvent($ch_b->waitAnswer());

        $this->assertEquals($ch_b->getCallerIdNumber(), self::CNUM);
        $this->assertEquals($ch_b->getCallerIdName(), self::CNAM);

        self::hangupBridged($ch_a, $ch_b);
    }
}