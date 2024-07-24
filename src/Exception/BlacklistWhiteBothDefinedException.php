<?php

declare(strict_types=1);

namespace NicolasGuilloux\WiremockBundle\Exception;

final class BlacklistWhiteBothDefinedException extends \LogicException
{
    public function __construct(
        /** @var string[] */
        private array $whitelist,
        /** @var string[] */
        private array $blacklist,
    ) {
        parent::__construct(
            message: 'You cannot define both whitelist_clients and blacklist_clients at the same time for wiremock.'
        );
    }

    /** @return string[] */
    public function getWhitelist(): array
    {
        return $this->whitelist;
    }

    /** @return string[] */
    public function getBlacklist(): array
    {
        return $this->blacklist;
    }
}
