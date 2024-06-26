<?php

namespace DvsaApplicationLogger\Interfaces;

/**
 * Interface for the logged-in user's identity. Does *not* cover roles, etc.
 */
interface MotIdentityInterface
{
    /**
     * Returns the username e.g. user1@example.com.
     */
    public function getUsername(): string;

    /**
     * Returns the user ID e.g. 5001.
     */
    public function getUserId(): int;

    /**
     * @return string identity unique identifier (UUID)
     */
    public function getUuid();

    /**
     * @return bool
     */
    public function isPasswordChangeRequired();

    /**
     * @return bool
     */
    public function isAccountClaimRequired();
}
