<?php

namespace App\Services\Omnichannel;

<<<<<<< HEAD
=======
use App\Enums\ChannelType;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use App\Models\ChannelConnection;
use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use App\Models\Website;
<<<<<<< HEAD
use App\Support\Omnichannel\WebsiteIdentity;
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WebsiteConversationResolver
{
<<<<<<< HEAD
    /**
     * Resolve the active native website channel.
     */
    public function connectionFor(
        Website $website
    ): ChannelConnection {
        $connection =
            ChannelConnection::query()
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
                    'website'
                )
                ->where(
                    'status',
                    'active'
                )
                ->first();

        if (!$connection) {
            throw new RuntimeException(
                "No active website channel connection exists for website [{$website->id}]. "
                . 'Run omnichannel:backfill-website-agents first.'
            );
=======
    public function __construct(
        protected WebsiteOmnichannelProvisioner $provisioner
    ) {
    }

    public function resolveConnection(
        Website $website
    ): ChannelConnection {
        $connection = ChannelConnection::query()
            ->where('website_id', $website->id)
            ->where('type', ChannelType::Website->value)
            ->first();

        if (!$connection) {
            $connection = $this->provisioner->provision($website);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        }

        return $connection;
    }

<<<<<<< HEAD
    /**
     * Resolve/create the Contact, ContactIdentity and
     * Conversation for one website visitor.
     *
     * This also upgrades old website conversations
     * lazily when they are encountered.
     */
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    public function resolve(
        Website $website,
        string $visitorId
    ): Conversation {
<<<<<<< HEAD
        $visitorId =
            trim($visitorId);
=======
        $visitorId = trim($visitorId);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        if ($visitorId === '') {
            throw new RuntimeException(
                'Website visitor ID cannot be empty.'
            );
        }

        $connection =
<<<<<<< HEAD
            $this->connectionFor(
                $website
            );

        $externalContactId =
            WebsiteIdentity::externalContactId(
                $visitorId
            );

        $externalThreadId =
            WebsiteIdentity::externalThreadId(
                $website->id,
                $visitorId
            );
=======
            $this->resolveConnection($website);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        return DB::transaction(
            function () use (
                $website,
                $visitorId,
<<<<<<< HEAD
                $connection,
                $externalContactId,
                $externalThreadId
            ): Conversation {
                /*
                |--------------------------------------------------------------------------
                | Look for the new omnichannel conversation first
                |--------------------------------------------------------------------------
                */

                $conversation =
                    Conversation::query()
=======
                $connection
            ): Conversation {
                $identity = ContactIdentity::query()
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
                        $visitorId
                    )
                    ->first();

                if (!$identity) {
                    $contact = Contact::create([
                        'tenant_id' =>
                            $website->tenant_id,

                        'name' => null,
                        'email' => null,
                        'phone' => null,
                        'company' => null,
                        'status' => 'active',

                        'metadata' => [
                            'source' => 'website',
                            'website_id' => $website->id,
                        ],
                    ]);

                    $identity =
                        ContactIdentity::create([
                            'tenant_id' =>
                                $website->tenant_id,

                            'contact_id' =>
                                $contact->id,

                            'channel_connection_id' =>
                                $connection->id,

                            'channel' =>
                                ChannelType::Website->value,

                            'external_user_id' =>
                                $visitorId,

                            'display_name' =>
                                null,

                            'username' =>
                                null,

                            'normalized_address' =>
                                $visitorId,

                            'is_verified' =>
                                false,

                            'metadata' => [
                                'provider' =>
                                    $connection->provider ?: 'native',

                                'website_id' =>
                                    $website->id,
                            ],
                        ]);
                }

                $conversation = Conversation::query()
                    ->where('website_id', $website->id)
                    ->where('visitor_id', $visitorId)
                    ->first();

                if (!$conversation) {
                    $conversation = Conversation::query()
                        ->where(
                            'tenant_id',
                            $website->tenant_id
                        )
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                        ->where(
                            'channel_connection_id',
                            $connection->id
                        )
                        ->where(
                            'external_thread_id',
<<<<<<< HEAD
                            $externalThreadId
                        )
                        ->lockForUpdate()
                        ->first();

                /*
                |--------------------------------------------------------------------------
                | Fall back to the legacy website conversation
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
                            ->orderBy('id')
                            ->lockForUpdate()
                            ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Resolve ContactIdentity
                |--------------------------------------------------------------------------
                */

                $identity =
                    ContactIdentity::query()
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
                 * An already-upgraded legacy conversation may
                 * already reference a valid Contact.
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
                | Create Contact if needed
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
                    'website';

                $identity->external_user_id =
                    $externalContactId;

                if (!$identity->display_name) {
                    $identity->display_name =
                        'Website Visitor';
                }

                $identityMetadata =
                    is_array(
                        $identity->metadata
                    )
                        ? $identity->metadata
                        : [];

                $identityMetadata['website_id'] =
                    $website->id;

                $identityMetadata['visitor_id'] =
                    $visitorId;

                $identity->metadata =
                    $identityMetadata;

                $identity->save();

                /*
                |--------------------------------------------------------------------------
                | Create conversation if needed
                |--------------------------------------------------------------------------
                */

                if (!$conversation) {
                    $conversation =
                        new Conversation();
=======
                            $visitorId
                        )
                        ->where('status', 'active')
                        ->first();
                }

                if (!$conversation) {
                    $conversation = new Conversation();

                    $conversation->website_id =
                        $website->id;

                    $conversation->visitor_id =
                        $visitorId;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

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

<<<<<<< HEAD
                /*
                |--------------------------------------------------------------------------
                | Populate both old and new conversation fields
                |--------------------------------------------------------------------------
                */

                $conversation->website_id =
                    $website->id;

                /*
                 * Preserve this field because the current widget,
                 * views and live-agent events still use visitor_id.
                 */
                $conversation->visitor_id =
                    $visitorId;

=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                $conversation->tenant_id =
                    $website->tenant_id;

                $conversation->ai_agent_id =
                    $connection->ai_agent_id;

                $conversation->channel_connection_id =
                    $connection->id;

                $conversation->contact_id =
<<<<<<< HEAD
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

                $metadata =
                    is_array(
                        $conversation->metadata
                    )
=======
                    $identity->contact_id;

                $conversation->external_thread_id =
                    $visitorId;

                $metadata =
                    is_array($conversation->metadata)
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                        ? $conversation->metadata
                        : [];

                $metadata['channel'] =
<<<<<<< HEAD
                    'website';

                $metadata['source'] =
                    'website_widget';

                $metadata['visitor_id'] =
                    $visitorId;
=======
                    ChannelType::Website->value;

                $metadata['provider'] =
                    $connection->provider ?: 'native';
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

                $conversation->metadata =
                    $metadata;

                $conversation->save();

<<<<<<< HEAD
                return $conversation;
            }
        );
    }
}
=======
                return $conversation->fresh([
                    'channelConnection',
                    'contact',
                ]);
            }
        );
    }
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
