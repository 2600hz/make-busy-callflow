<?php

namespace KazooTests\Applications\Callflow;

use \KazooTests\TestCase;

use \MakeBusy\Kazoo\Applications\Crossbar\TestAccount;
use \MakeBusy\Kazoo\Applications\Crossbar\SystemConfigs;

class CallflowTestCase extends TestCase
{
    private static $test_account;

    public static function setUpBeforeClass() {
        if (is_null(self::$test_account)) {
            self::configureMakeBusy();
            TestAccount::nukeTestAccounts(get_class());
            self::$test_account = new TestAccount(get_class());
        }
        TestAccount::nukeGlobalResources();
    }

    public static function getTestAccount() {
        return self::$test_account;
    }

}
