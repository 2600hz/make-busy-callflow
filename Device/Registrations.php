<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Log;
use \MakeBusy\FreeSWITCH\Esl\Connection as EslConnection;

class DeviceTest extends CallflowTestCase {

    public function testRegistrations() {
        Log::notice("%s", __METHOD__);
        $gateways = Profiles::getProfile('auth')->getGateways();
        $register_device_id = self::$register_device->getId();
        $this->assertTrue($gateways->findByName($register_device_id)->register());
    }

}