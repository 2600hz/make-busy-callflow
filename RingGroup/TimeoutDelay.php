<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;

/*
* MKBUSY - 69
* 1) Create a ring group with a mix of devices and user endpoints.
* 2) On one endpoint, set the leg delay and on all endpoints set a leg timeout.
* 3) Call the ring group and ensure the devices without a leg delay ring first as well as all devices only ring for their leg timeout.
*/
class TimeoutDelay extends RingGroupTestCase {

    public function main($sip_uri) {
        $target = self::RG_EXT_1 . '@' . $sip_uri;
        self::ensureChannel( self::$device["a"]->originate($target) );

        foreach (range('b', 'g') as $leg) {
            $race[$leg] = self::ensureChannel(self::$device[$leg]->waitForInbound(self::$device[$leg]->getSipUsername(), 30));
        }

        foreach (range('b', 'g') as $leg) {
            $destroy[$leg] = $race[$leg]->waitDestroy();
            $start[$leg] = $destroy[$leg]->getHeader('variable_start_epoch');
            $duration[$leg] = $destroy[$leg]->getHeader('variable_duration');
        }

        $start_time = $start['b'];
        $expected_duration = self::DURATION;

        foreach (range('b', 'g') as $leg) {
            Log::debug("trying leg %s", $leg);
            $dur_low  = $expected_duration - 2;
            $dur_high = $expected_duration + 2;

            self::assertLessThanOrEqual($dur_high, $duration[$leg]);
            self::assertgreaterthanorequal($dur_low, $duration[$leg]);

            $start_low  = $start_time - 2;
            $start_high = $start_time + 2;

            self::assertLessThanOrEqual($start_high, $start[$leg]);
            self::assertgreaterthanorequal($start_low, $start[$leg]);

            $expected_duration = $expected_duration - 5;
            $start_time = $start_time + 5;
        }
    }
}