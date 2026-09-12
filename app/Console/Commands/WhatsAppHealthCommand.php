<?php

namespace App\Console\Commands;

use App\Models\ChannelConnection;
use App\Services\Omnichannel\WhatsApp\WhatsAppConnectionHealthService;
use Illuminate\Console\Command;
use Throwable;

class WhatsAppHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature =
        'omnichannel:whatsapp-health
        {connection_id : WhatsApp ChannelConnection ID}
        {--force : Skip confirmation prompt}
        {--show-provider-data : Show safe Meta provider metadata}';

    /**
     * The console command description.
     */
    protected $description =
        'Verify a WhatsApp channel connection against Meta Cloud API';

    /**
     * Execute the console command.
     */
    public function handle(
        WhatsAppConnectionHealthService $healthService,
    ): int {
        $connectionId =
            trim(
                (string)
                $this->argument(
                    'connection_id'
                )
            );

        if (
            $connectionId === ''
            || !ctype_digit(
                $connectionId
            )
            || (int) $connectionId <= 0
        ) {
            $this->error(
                'A valid ChannelConnection ID is required.'
            );

            return self::FAILURE;
        }

        $connection =
            ChannelConnection::query()
                ->find(
                    (int) $connectionId
                );

        if (!$connection) {
            $this->error(
                sprintf(
                    'ChannelConnection #%s was not found.',
                    $connectionId
                )
            );

            return self::FAILURE;
        }

        if (
            strtolower(
                trim(
                    (string)
                    $connection->type
                )
            ) !== 'whatsapp'
        ) {
            $this->error(
                sprintf(
                    'ChannelConnection #%d is type "%s", not "whatsapp".',
                    $connection->id,
                    $connection->type
                )
            );

            return self::FAILURE;
        }

        /*
         * Display safe information only.
         *
         * credentials/access_token must never
         * appear in this output.
         */
        $this->newLine();

        $this->table(
            [
                'Field',
                'Value',
            ],
            [
                [
                    'Connection ID',
                    $connection->id,
                ],
                [
                    'Tenant ID',
                    $connection->tenant_id,
                ],
                [
                    'AI Agent ID',
                    $connection->ai_agent_id,
                ],
                [
                    'Name',
                    $connection->name,
                ],
                [
                    'Provider',
                    $connection->provider,
                ],
                [
                    'WABA ID',
                    $connection->external_account_id,
                ],
                [
                    'Phone Number ID',
                    $connection->external_sender_id,
                ],
                [
                    'Current status',
                    $connection->status,
                ],
                [
                    'Last health check',
                    $connection->last_health_check_at
                        ? $connection
                            ->last_health_check_at
                            ->toDateTimeString()
                        : 'Never',
                ],
            ]
        );

        $this->newLine();

        $this->warn(
            'This command will contact Meta and update the connection status.'
        );

        if (
            !$this->option('force')
            && !$this->confirm(
                'Continue with WhatsApp health verification?',
                true
            )
        ) {
            $this->info(
                'Health check cancelled.'
            );

            return self::SUCCESS;
        }

        $this->newLine();

        $this->info(
            'Checking Meta WhatsApp connection...'
        );

        try {
            $result =
                $healthService
                    ->check(
                        $connection
                    );
        } catch (Throwable $exception) {
            /*
             * This should normally only catch
             * unexpected infrastructure/database
             * failures because provider failures are
             * converted to unhealthy results by the
             * health service.
             */
            $this->error(
                'The health check could not be completed.'
            );

            $this->line(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $connection =
            $result->connection;

        $this->newLine();

        if (!$result->healthy) {
            $this->error(
                'WhatsApp connection verification failed.'
            );

            $this->table(
                [
                    'Field',
                    'Value',
                ],
                [
                    [
                        'Connection ID',
                        $connection->id,
                    ],
                    [
                        'Status',
                        $connection->status,
                    ],
                    [
                        'Health check',
                        $connection->last_health_check_at
                            ? $connection
                                ->last_health_check_at
                                ->toDateTimeString()
                            : '-',
                    ],
                    [
                        'Error',
                        $connection->last_error
                            ?: $result->message,
                    ],
                ]
            );

            return self::FAILURE;
        }

        $this->info(
            'WhatsApp connection verified successfully.'
        );

        $settings =
            is_array(
                $connection->settings
            )
                ? $connection->settings
                : [];

        $this->table(
            [
                'Field',
                'Value',
            ],
            [
                [
                    'Connection ID',
                    $connection->id,
                ],
                [
                    'Status',
                    $connection->status,
                ],
                [
                    'Verified name',
                    $settings[
                        'verified_name'
                    ] ?? '-',
                ],
                [
                    'Display phone',
                    $settings[
                        'display_phone_number'
                    ] ?? '-',
                ],
                [
                    'Quality rating',
                    $settings[
                        'quality_rating'
                    ] ?? '-',
                ],
                [
                    'Verification status',
                    $settings[
                        'code_verification_status'
                    ] ?? '-',
                ],
                [
                    'Connected at',
                    $connection->connected_at
                        ? $connection
                            ->connected_at
                            ->toDateTimeString()
                        : '-',
                ],
                [
                    'Health checked at',
                    $connection->last_health_check_at
                        ? $connection
                            ->last_health_check_at
                            ->toDateTimeString()
                        : '-',
                ],
            ]
        );

        /*
         * Never dump arbitrary providerData.
         *
         * Only show fields we explicitly know are
         * non-secret.
         */
        if (
            $this->option(
                'show-provider-data'
            )
        ) {
            $this->displayProviderData(
                $result->providerData
            );
        }

        return self::SUCCESS;
    }

    /**
     * Display explicitly allow-listed provider
     * values only.
     */
    private function displayProviderData(
        array $providerData
    ): void {
        $allowedFields = [
            'id',
            'verified_name',
            'display_phone_number',
            'quality_rating',
            'code_verification_status',
        ];

        $rows = [];

        foreach (
            $allowedFields
            as $field
        ) {
            if (
                !array_key_exists(
                    $field,
                    $providerData
                )
            ) {
                continue;
            }

            $value =
                $providerData[
                    $field
                ];

            if (
                is_array($value)
                || is_object($value)
            ) {
                continue;
            }

            $rows[] = [
                $field,
                (string) $value,
            ];
        }

        if ($rows === []) {
            return;
        }

        $this->newLine();

        $this->info(
            'Safe Meta provider data:'
        );

        $this->table(
            [
                'Field',
                'Value',
            ],
            $rows
        );
    }
}