<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

/*
 * MKBUSY - 70
 * 1) Create a ring group callflow with two or more devices and no strategy property.
 * 2) Call the ring group and ensure all devices ring at the same time.
 * 3) Set the ring group callflow data with a strategy "simultaneous".
 * 4) Call the ring group and ensure all devices ring at the same time.
 * 5) Set the ring group callflow data with a strategy "single".
 * 6) Call the ring group and ensure each device rings one at a time in the order defined on the callflow.
 */
class StrategySingle extends RingGroupTestCase {

    public function main($sip_uri) {
        $target = self::RG_EXT_4 . '@' . $sip_uri;
        self::ensureChannel( self::$device["a"]->originate($target) );

        foreach (range('f', 'g') as $leg) {
            $race[$leg] = self::ensureChannel(self::$device[$leg]->waitForInbound(self::$device[$leg]->getSipUsername(), 30));
        }

        foreach (range('f', 'g') as $leg) {
            $destroy[$leg] = self::ensureEvent($race[$leg]->waitDestroy(30));
            $start[$leg] = $destroy[$leg]->getHeader('variable_start_epoch');
            $end[$leg] = $destroy[$leg]->getHeader('variable_end_epoch');
        }
        // one after another
        $this->assertEquals($end['f'], $start['g']);
    }
}