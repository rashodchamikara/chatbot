<?php

namespace App\Services\Omnichannel;

use App\Contracts\Omnichannel\ChannelAdapter;
use App\Models\ChannelConnection;
use InvalidArgumentException;

class ChannelManager
{
   
    protected array $adapters = [];

    /**
     * Register a channel adapter.
     */
    public function register(ChannelAdapter $adapter): void
    {
        $type = strtolower(trim($adapter->type()));

        if ($type === '') {
            throw new InvalidArgumentException(
                'Channel adapter type cannot be empty.'
            );
        }

        $this->adapters[$type] = $adapter;
    }

    
    public function driver(string $type): ChannelAdapter
    {
        $type = strtolower(trim($type));

        if (! isset($this->adapters[$type])) {
            throw new InvalidArgumentException(
                "No channel adapter registered for type [{$type}]."
            );
        }

        return $this->adapters[$type];
    }

    /**
     * Resolve the adapter for a ChannelConnection.
     */
    public function forConnection(
        ChannelConnection $connection
    ): ChannelAdapter {
        return $this->driver($connection->type);
    }

    /**
     * Check whether an adapter has been registered.
     */
    public function has(string $type): bool
    {
        return isset(
            $this->adapters[
                strtolower(trim($type))
            ]
        );
    }

    /**
     * Return all registered adapter types.
     *
     * @return array<int, string>
     */
    public function registeredTypes(): array
    {
        return array_keys($this->adapters);
    }
}