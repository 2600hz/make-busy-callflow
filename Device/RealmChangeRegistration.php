<?php
namespace KazooTests\Applications\Callflow;
use \MakeBusy\Common\Utils;
use \MakeBusy\Common\Log;

class RealmChangeRegistrationTest extends CallflowTestCase {

    public function testMain() {
        $test_account = self::getTestAccount();
        $a_device_id = self::$a_device->getId();
        $gateways = Profiles::getProfile('auth')->getGateways();

        // test basic registration
        $this->assertTrue($gateways->findByName($a_device_id)->register());

        // unregister
        $this->assertTrue($gateways->findByName($a_device_id)->unregister());

        // change realm
        $test_account->setAccountRealm('blah.com');

        // ensure fail registration
        $this->assertFalse($gateways->findByName($a_device_id)->register());

        // update gateway with new realm
        $gateways->findByName($a_device_id)->setParam('realm', 'blah.com');
        $gateways->findByName($a_device_id)->setParam('from-domain', 'blah.com');
        // sync freeswitch with new gateway information
        Profiles::syncGateways();
        // re-register
        $this->assertTrue($gateways->findByName($a_device_id)->register());

        // change realm back and re-sync for non-existent future device test failures.
        $test_account->setAccountRealm(self::$realm);
        $gateways->findByName($a_device_id)->setParam('realm', self::$realm);
        $gateways->findByName($a_device_id)->setParam('from-domain', self::$realm);
        Profiles::syncGateways();
    }

}