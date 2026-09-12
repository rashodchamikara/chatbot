<?php

namespace App\Console\Commands;

use App\Data\Omnichannel\WhatsAppConnectionData;
use App\Models\ChannelConnection;
use App\Services\Omnichannel\WhatsApp\WhatsAppConnectionConfigService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;
use Throwable;

class WhatsAppConfigureCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature =
        'omnichannel:whatsapp-configure
        {connection_id : Existing WhatsApp ChannelConnection ID}
        {--waba= : Meta WhatsApp Business Account ID}
        {--phone-number-id= : Meta WhatsApp Phone Number ID}
        {--display-phone-number= : Optional visible phone number}
        {--verified-name= : Optional WhatsApp business name}
        {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description =
        'Securely configure an existing WhatsApp channel connection for Meta Cloud API';

    /**
     * Execute the console command.
     */
    public function handle(
        WhatsAppConnectionConfigService $configService,
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
         * Show only safe connection information.
         *
         * NEVER output credentials.
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
                    'Type',
                    $connection->type,
                ],
                [
                    'Provider',
                    $connection->provider,
                ],
                [
                    'Current status',
                    $connection->status,
                ],
            ]
        );

        if (
            !$this->option('force')
            && !$this->confirm(
                'Configure this WhatsApp connection?',
                true
            )
        ) {
            $this->info(
                'Configuration cancelled.'
            );

            return self::SUCCESS;
        }

        /*
         * WABA ID.
         */
        $businessAccountId =
            trim(
                (string) (
                    $this->option('waba')
                    ?: $this->ask(
                        'WhatsApp Business Account ID (WABA ID)'
                    )
                )
            );

        /*
         * Meta Phone Number ID.
         */
        $phoneNumberId =
            trim(
                (string) (
                    $this->option(
                        'phone-number-id'
                    )
                    ?: $this->ask(
                        'Meta WhatsApp Phone Number ID'
                    )
                )
            );

        /*
         * IMPORTANT:
         *
         * Access token is deliberately obtained
         * through a hidden console prompt.
         *
         * Do not add a --token option because that
         * would expose the token in shell history.
         */
        $accessToken =
            trim(
                (string)
                $this->secret(
                    'Meta access token (input hidden)'
                )
            );

        if ($accessToken === '') {
            $this->error(
                'Meta access token is required.'
            );

            return self::FAILURE;
        }

        $displayPhoneNumber =
            $this->nullableOption(
                'display-phone-number'
            );

        $verifiedName =
            $this->nullableOption(
                'verified-name'
            );

        $data =
            new WhatsAppConnectionData(
                accessToken:
                    $accessToken,

                businessAccountId:
                    $businessAccountId,

                phoneNumberId:
                    $phoneNumberId,

                displayPhoneNumber:
                    $displayPhoneNumber,

                verifiedName:
                    $verifiedName,
            );

        try {
            $connection =
                $configService
                    ->configure(
                        connection:
                            $connection,

                        data:
                            $data,
                    );
        } catch (
            ValidationException $exception
        ) {
            $this->error(
                'WhatsApp configuration validation failed.'
            );

            foreach (
                $exception->errors()
                as $field => $messages
            ) {
                foreach ($messages as $message) {
                    $this->line(
                        sprintf(
                            ' - %s: %s',
                            $field,
                            $message
                        )
                    );
                }
            }

            return self::FAILURE;
        } catch (Throwable $exception) {
            $this->error(
                'Unable to configure the WhatsApp connection.'
            );

            $this->line(
                $exception->getMessage()
            );

            return self::FAILURE;
        } finally {
            /*
             * Explicitly remove our local reference.
             *
             * PHP will clean this up anyway, but this
             * makes the security intention clear.
             */
            $accessToken =
                null;
        }

        $this->newLine();

        $this->info(
            'WhatsApp connection configuration saved.'
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
                    'Status',
                    $connection->status,
                ],
            ]
        );

        $this->warn(
            'Status remains pending until Meta verification succeeds.'
        );

        return self::SUCCESS;
    }

    /**
     * Return a nullable trimmed console option.
     */
    private function nullableOption(
        string $name
    ): ?string {
        $value =
            $this->option(
                $name
            );

        if ($value === null) {
            return null;
        }

        $value =
            trim(
                (string) $value
            );

        return $value !== ''
            ? $value
            : null;
    }
}