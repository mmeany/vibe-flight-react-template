<?php

declare(strict_types=1);

namespace App\Tests;

use App\Utils\UserValidation;
use PHPUnit\Framework\TestCase;

class UserValidationPasswordReminderTest extends TestCase
{
    public function testValidatePasswordReminderRejectsEmpty(): void
    {
        $errors = UserValidation::validatePasswordReminder('   ');

        $this->assertContains('Password reminder is required.', $errors);
    }

    public function testValidatePasswordReminderRejectsTooLong(): void
    {
        $errors = UserValidation::validatePasswordReminder(str_repeat('a', 256));

        $this->assertContains('Password reminder must be 255 characters or fewer.', $errors);
    }

    public function testValidatePasswordReminderAcceptsValid(): void
    {
        $errors = UserValidation::validatePasswordReminder('My pet name');

        $this->assertSame([], $errors);
    }

    public function testValidateRegistrationIncludesPasswordReminder(): void
    {
        $errors = UserValidation::validateRegistration('user', 'a@b.com', 'Password1', '');

        $this->assertContains('Password reminder is required.', $errors);
    }
}
