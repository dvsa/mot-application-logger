<?php

namespace DvsaApplicationLogger\Interfaces;

/**
 * Provides the current user's identity to objects that require it.
 */
interface MotIdentityProviderInterface
{
    public function getIdentity(): MotIdentityInterface|null;
}
