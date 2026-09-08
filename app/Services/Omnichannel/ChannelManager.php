<?php

namespace App\Services\Omnichannel;

use App\Contracts\Omnichannel\ChannelAdapter;
use App\Models\ChannelConnection;
<<<<<<< HEAD
=======
use BackedEnum;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use InvalidArgumentException;

class ChannelManager
{
<<<<<<< HEAD
   
=======
    /**
     * @var array<string, ChannelAdapter>
     */
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    protected array $adapters = [];

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

<<<<<<< HEAD
    
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function driver(string $type): ChannelAdapter
    {
        $type = strtolower(trim($type));

<<<<<<< HEAD
        if (! isset($this->adapters[$type])) {
=======
        if (!isset($this->adapters[$type])) {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            throw new InvalidArgumentException(
                "No channel adapter registered for type [{$type}]."
            );
        }

        return $this->adapters[$type];
    }

    public function forConnection(
        ChannelConnection $connection
    ): ChannelAdapter {
<<<<<<< HEAD
        return $this->driver($connection->type);
    }

=======
        $type = $connection->type;

        if ($type instanceof BackedEnum) {
            $type = $type->value;
        }

        return $this->driver((string) $type);
    }
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

    public function has(string $type): bool
    {
        return isset(
            $this->adapters[
                strtolower(trim($type))
            ]
        );
    }

<<<<<<< HEAD
 
=======
    /**
     * @return array<int, string>
     */
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function registeredTypes(): array
    {
        return array_keys($this->adapters);
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
