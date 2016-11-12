<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class DirectCallsOnlyTest extends CallflowTestCase {

    public function testMain() {
        self::$b_device->resetCfParams(self::C_EXT);
        self::$b_device->setCfParam("direct_calls_only", TRUE);

        foreach (self::getSipTargets() as $sip_uri) {
            $target = self::B_EXT .'@'. $sip_uri;
            $ch_a = self::ensureChannel( self::$a_device->originate($target) );
            $ch_b = self::ensureChannel( self::$b_device->waitForInbound() );
            $ch_c = self::ensureChannel( self::$c_device->waitForInbound() );

            self::ensureAnswer($ch_a, $ch_c);
            self::ensureTwoWayAudio($ch_a, $ch_c);
            self::hangupBridged($ch_a, $ch_c);
        }

        foreach (self::getSipTargets() as $sip_uri) {
            Log::debug("placing a call via ring-group, expecting cf device %s to not ring", $c_username);
            $target  = self::RINGGROUP_EXT .'@'. $sip_uri;
            Log::debug("trying target %s", $target);
            $options = array("origination_uuid" => $uuid_base . "rgext-" . Utils::randomString(8));
            $uuid      = $channels->gatewayOriginate($a_device_id, $target, $options);
            $c_channel = $channels->waitForInbound($c_username);
            $b_channel = $channels->waitForInbound($b_username);
            $this->assertNull($c_channel);
            $this->assertInstanceOf("\\MakeBusy\\FreeSWITCH\\Channels\\Channel", $b_channel);
            $a_channel = $this->ensureAnswer("auth", $uuid, $b_channel);
            $this->ensureTwoWayAudio($a_channel, $b_channel);
            $this->hangupBridged($a_channel, $b_channel);
        }
    }

}