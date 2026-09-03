<?php

namespace App\Services\Omnichannel;

use App\Models\ChannelConnection;
use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use App\Models\Website;
use App\Support\Omnichannel\WebsiteIdentity;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WebsiteConversationResolver
{
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
        }

        return $connection;
    }

    /**
     * Resolve/create the Contact, ContactIdentity and
     * Conversation for one website visitor.
     *
     * This also upgrades old website conversations
     * lazily when they are encountered.
     */
    public function resolve(
        Website $website,
        string $visitorId
    ): Conversation {
        $visitorId =
            trim($visitorId);

        if ($visitorId === '') {
            throw new RuntimeException(
                'Website visitor ID cannot be empty.'
            );
        }

        $connection =
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
                | Look for the new omnichannel conversation first
                |--------------------------------------------------------------------------
                */

                $conversation =
                    Conversation::query()
                        ->where(
                            'channel_connection_id',
                            $connection->id
                        )
                        ->where(
                            'external_thread_id',
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

                $metadata =
                    is_array(
                        $conversation->metadata
                    )
                        ? $conversation->metadata
                        : [];

                $metadata['channel'] =
                    'website';

                $metadata['source'] =
                    'website_widget';

                $metadata['visitor_id'] =
                    $visitorId;

                $conversation->metadata =
                    $metadata;

                $conversation->save();

                return $conversation;
            }
        );
    }
}