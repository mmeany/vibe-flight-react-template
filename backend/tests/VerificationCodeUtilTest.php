<?php

declare(strict_types=1);

namespace App\Tests;

use App\Utils\VerificationCodeUtil;
use PHPUnit\Framework\TestCase;

class VerificationCodeUtilTest extends TestCase
{
    public function testGenerateReturnsSixDigitCode(): void
    {
        $code = VerificationCodeUtil::generate();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
    }

    public function testHashAndVerifyRoundTrip(): void
    {
        $code = '482913';
        $hash = VerificationCodeUtil::hash($code);

        $this->assertTrue(VerificationCodeUtil::verify($code, $hash));
        $this->assertFalse(VerificationCodeUtil::verify('000000', $hash));
    }
}
