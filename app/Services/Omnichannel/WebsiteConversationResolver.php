<?php

namespace App\Services\Omnichannel;

use App\Enums\ChannelType;
use App\Models\ChannelConnection;
use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use App\Models\Website;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WebsiteConversationResolver
{
    public function __construct(
        protected WebsiteOmnichannelProvisioner $provisioner
    ) {
    }

    /**
     * Resolve the native omnichannel connection belonging
     * to the website.
     *
     * If it does not exist yet, provision it automatically.
     */
    public function resolveConnection(
        Website $website
    ): ChannelConnection {
        if (!$website->exists) {
            throw new RuntimeException(
                'Website must exist before resolving its channel connection.'
            );
        }

        if (!$website->tenant_id) {
            throw new RuntimeException(
                "Website [{$website->id}] does not have a tenant."
            );
        }

        $connection = ChannelConnection::query()
            ->where(
                'website_id',
                $website->id
            )
            ->where(
                'tenant_id',
                $website->tenant_id
            )
            ->where(
                'type',
                ChannelType::Website->value
            )
            ->first();

        if (!$connection) {
            $connection =
                $this->provisioner
                    ->provision(
                        $website
                    );
        }

        if (
            strtolower(
                trim(
                    (string) $connection->status
                )
            ) !== 'active'
        ) {
            throw new RuntimeException(
                "Website channel connection [{$connection->id}] is not active."
            );
        }

        if (!$connection->ai_agent_id) {
            throw new RuntimeException(
                "Website channel connection [{$connection->id}] has no AI agent."
            );
        }

        return $connection;
    }

    /**
     * Resolve/create the omnichannel Contact,
     * ContactIdentity and Conversation associated with
     * one website visitor.
     *
     * Existing legacy website conversations are upgraded
     * lazily instead of being discarded.
     */
    public function resolve(
        Website $website,
        string $visitorId
    ): Conversation {
        $visitorId =
            trim(
                $visitorId
            );

        if ($visitorId === '') {
            throw new RuntimeException(
                'Website visitor ID cannot be empty.'
            );
        }

        $connection =
            $this->resolveConnection(
                $website
            );

        /*
         * For the native website channel the visitor ID is
         * both the external contact identity and the thread
         * identity.
         */
        $externalContactId =
            $visitorId;

        $externalThreadId =
            $visitorId;

        return DB::transaction(
            function () use (
                $website,
                $visitorId,
                $connection,
                $externalContactId,
                $externalThreadId
            ): Conversation {
                /*
                |--------------------------------------------------------------------------
                | Find current omnichannel conversation
                |--------------------------------------------------------------------------
                */

                $conversation =
                    Conversation::query()
                        ->where(
                            'tenant_id',
                            $website->tenant_id
                        )
                        ->where(
                            'channel_connection_id',
                            $connection->id
                        )
                        ->where(
                            'external_thread_id',
                            $externalThreadId
                        )
                        ->where(
                            'status',
                            'active'
                        )
                        ->lockForUpdate()
                        ->first();

                /*
                |--------------------------------------------------------------------------
                | Fall back to existing legacy website conversation
                |--------------------------------------------------------------------------
                */

                if (!$conversation) {
                    $conversation =
                        Conversation::query()
                            ->where(
                                'website_id',
                                $website->id
                            )
                            ->where(
                                'visitor_id',
                                $visitorId
                            )
                            ->where(
                                'status',
                                'active'
                            )
                            ->orderBy(
                                'id'
                            )
                            ->lockForUpdate()
                            ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Resolve existing ContactIdentity
                |--------------------------------------------------------------------------
                */

                $identity =
                    ContactIdentity::query()
                        ->where(
                            'tenant_id',
                            $website->tenant_id
                        )
                        ->where(
                            'channel_connection_id',
                            $connection->id
                        )
                        ->where(
                            'external_user_id',
                            $externalContactId
                        )
                        ->lockForUpdate()
                        ->first();

                $contact = null;

                if ($identity) {
                    $contact =
                        Contact::query()
                            ->whereKey(
                                $identity->contact_id
                            )
                            ->where(
                                'tenant_id',
                                $website->tenant_id
                            )
                            ->first();
                }

                /*
                 * A previously upgraded conversation might
                 * already reference a Contact even if its
                 * identity row is missing.
                 */
                if (
                    !$contact
                    && $conversation
                    && $conversation->contact_id
                ) {
                    $contact =
                        Contact::query()
                            ->whereKey(
                                $conversation->contact_id
                            )
                            ->where(
                                'tenant_id',
                                $website->tenant_id
                            )
                            ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Create Contact
                |--------------------------------------------------------------------------
                */

                if (!$contact) {
                    $contact =
                        new Contact();

                    $contact->tenant_id =
                        $website->tenant_id;

                    $contact->status =
                        'active';

                    $contact->metadata = [
                        'source' =>
                            'website_widget',

                        'website_id' =>
                            $website->id,

                        'visitor_id' =>
                            $visitorId,
                    ];

                    $contact->save();
                }

                /*
                |--------------------------------------------------------------------------
                | Create/update ContactIdentity
                |--------------------------------------------------------------------------
                */

                if (!$identity) {
                    $identity =
                        new ContactIdentity();
                }

                $identity->tenant_id =
                    $website->tenant_id;

                $identity->contact_id =
                    $contact->id;

                $identity->channel_connection_id =
                    $connection->id;

                $identity->channel =
                    ChannelType::Website->value;

                $identity->external_user_id =
                    $externalContactId;

                $identity->normalized_address =
                    $visitorId;

                if (!$identity->display_name) {
                    $identity->display_name =
                        'Website Visitor';
                }

                $identity->is_verified =
                    (bool) $identity->is_verified;

                $identityMetadata =
                    is_array(
                        $identity->metadata
                    )
                        ? $identity->metadata
                        : [];

                $identityMetadata['provider'] =
                    $connection->provider
                    ?: 'native';

                $identityMetadata['website_id'] =
                    $website->id;

                $identityMetadata['visitor_id'] =
                    $visitorId;

                $identity->metadata =
                    $identityMetadata;

                $identity->save();

                /*
                |--------------------------------------------------------------------------
                | Create conversation if necessary
                |--------------------------------------------------------------------------
                */

                if (!$conversation) {
                    $conversation =
                        new Conversation();

                    $conversation->status =
                        'active';

                    $conversation->mode =
                        'ai';

                    $conversation->lead_stage =
                        'discovery';

                    $conversation->priority =
                        'normal';

                    $conversation->unread_count =
                        0;
                }

                /*
                |--------------------------------------------------------------------------
                | Preserve old website fields
                |--------------------------------------------------------------------------
                */

                $conversation->website_id =
                    $website->id;

                $conversation->visitor_id =
                    $visitorId;

                /*
                |--------------------------------------------------------------------------
                | Omnichannel ownership
                |--------------------------------------------------------------------------
                */

                $conversation->tenant_id =
                    $website->tenant_id;

                $conversation->ai_agent_id =
                    $connection->ai_agent_id;

                $conversation->channel_connection_id =
                    $connection->id;

                $conversation->contact_id =
                    $contact->id;

                $conversation->external_thread_id =
                    $externalThreadId;

                if (!$conversation->status) {
                    $conversation->status =
                        'active';
                }

                if (!$conversation->mode) {
                    $conversation->mode =
                        'ai';
                }

                if (!$conversation->lead_stage) {
                    $conversation->lead_stage =
                        'discovery';
                }

                if (!$conversation->priority) {
                    $conversation->priority =
                        'normal';
                }

                if (
                    $conversation->unread_count
                    === null
                ) {
                    $conversation->unread_count =
                        0;
                }

                /*
                |--------------------------------------------------------------------------
                | Conversation metadata
                |--------------------------------------------------------------------------
                */

                $metadata =
                    is_array(
                        $conversation->metadata
                    )
                        ? $conversation->metadata
                        : [];

                $metadata['channel'] =
                    ChannelType::Website->value;

                $metadata['provider'] =
                    $connection->provider
                    ?: 'native';

                $metadata['source'] =
                    'website_widget';

                $metadata['visitor_id'] =
                    $visitorId;

                $conversation->metadata =
                    $metadata;

                $conversation->save();

                return $conversation->fresh([
                    'channelConnection',
                    'contact',
                ]);
            }
        );
    }
}