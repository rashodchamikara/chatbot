<?php

namespace App\Services\Omnichannel;

use App\Data\Omnichannel\InboundMessageData;
use App\Events\OmnichannelMessageChanged;
use App\Models\ChannelConnection;
use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class InboundMessageService
{
    public function handle(
<<<<<<< HEAD
        InboundMessageData $data
    ): Message {
        /*
         * Explicitly track whether THIS request created
         * the message.
         *
         * This is safer than relying on wasRecentlyCreated
         * after refresh/load operations.
         */
=======
        ChannelConnection $connection,
        InboundMessageData $data
    ): Message {
        if ((int) $connection->id !== $data->channelConnectionId) {
            throw new RuntimeException(
                'Inbound message channel connection does not match the supplied connection.'
            );
        }

        if ((int) $connection->tenant_id !== $data->tenantId) {
            throw new RuntimeException(
                'Inbound message tenant does not match the channel connection tenant.'
            );
        }

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        $wasCreated = false;

        $message = DB::transaction(
            function () use (
<<<<<<< HEAD
                $data,
                &$wasCreated
            ): Message {
                /*
                |--------------------------------------------------------------------------
                | Validate channel connection
                |--------------------------------------------------------------------------
                */

                $connection =
                    ChannelConnection::query()
                        ->whereKey(
                            $data->channelConnectionId
                        )
                        ->where(
                            'tenant_id',
                            $data->tenantId
                        )
                        ->first();

                if (!$connection) {
                    throw new RuntimeException(
                        'Channel connection does not exist or does not belong to the tenant.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Idempotency
                |--------------------------------------------------------------------------
                |
                | Providers may resend the same webhook.
                |
                | A provider message must therefore only
                | create one local Message record.
                |
                */

                $existingMessage =
                    Message::query()
=======
                $connection,
                $data,
                &$wasCreated
            ): Message {
                if ($data->externalMessageId) {
                    $existing = Message::query()
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                        ->where(
                            'channel_connection_id',
                            $connection->id
                        )
                        ->where(
                            'external_message_id',
                            $data->externalMessageId
                        )
                        ->first();

<<<<<<< HEAD
                if ($existingMessage) {
                    return $existingMessage;
                }

                /*
                |--------------------------------------------------------------------------
                | Resolve contact
                |--------------------------------------------------------------------------
                */

                $contact =
                    $this->resolveContact(
                        connection:
                            $connection,

                        data:
                            $data
                    );

                /*
                |--------------------------------------------------------------------------
                | Resolve conversation
                |--------------------------------------------------------------------------
                */

                $conversation =
                    $this->resolveConversation(
                        connection:
                            $connection,

                        contact:
                            $contact,

                        data:
                            $data
                    );

                /*
                |--------------------------------------------------------------------------
                | Create inbound message
                |--------------------------------------------------------------------------
                */

                $message =
                    new Message();
=======
                    if ($existing) {
                        return $existing;
                    }
                }

                $contact = $this->resolveContact(
                    $connection,
                    $data
                );

                $conversation = $this->resolveConversation(
                    $connection,
                    $contact,
                    $data
                );

                $message = new Message();
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

                $message->conversation_id =
                    $conversation->id;

                $message->channel_connection_id =
                    $connection->id;

<<<<<<< HEAD
=======
                // Legacy website/live-chat compatibility.
                $message->user_id = null;
                $message->sender = 'visitor';
                $message->role = 'user';
                $message->is_system = false;

                // Omnichannel fields.
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                $message->external_message_id =
                    $data->externalMessageId;

                $message->direction =
                    'inbound';

                $message->sender_type =
                    'contact';

                $message->message_type =
<<<<<<< HEAD
                    $data->messageType;

                $message->message =
                    $data->text ?? '';
=======
                    $data->messageType ?: 'text';

                $message->message =
                    $data->text;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

                $message->payload = [
                    'external_contact_id' =>
                        $data->externalContactId,

                    'external_thread_id' =>
                        $data->externalThreadId,

                    'metadata' =>
                        $data->metadata,
                ];

                $message->status =
                    'received';

<<<<<<< HEAD
                $message->is_ai_generated =
                    false;

=======
                $message->provider_status =
                    'received';

                $message->is_ai_generated =
                    false;

                $message->provider_created_at =
                    $data->providerCreatedAt;

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                $message->save();

                $wasCreated = true;

<<<<<<< HEAD
                /*
                |--------------------------------------------------------------------------
                | Attachments
                |--------------------------------------------------------------------------
                */

                $this->storeAttachments(
                    message:
                        $message,

                    attachments:
                        $data->attachments
                );

                /*
                |--------------------------------------------------------------------------
                | Update conversation activity
                |--------------------------------------------------------------------------
                */

                $now = now();

                $conversation->last_message_at =
                    $now;

                $conversation->last_inbound_at =
                    $now;

                $conversation->unread_count =
                    ((int) $conversation->unread_count)
                    + 1;
=======
                $this->storeAttachments(
                    $message,
                    $data->attachments
                );

                $conversation->last_message_at =
                    now();

                $conversation->last_inbound_at =
                    now();

                $conversation->unread_count =
                    ((int) $conversation->unread_count) + 1;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

                $conversation->save();

                return $message;
            }
        );

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Realtime broadcast
        |--------------------------------------------------------------------------
        |
        | Only broadcast when THIS request actually
        | created the message.
        |
        | Provider webhook retries are therefore
        | silent.
        |
        */

        if ($wasCreated) {
            $this->broadcastMessageChange(
                message:
                    $message,

                changeType:
                    'created'
            );
        }

        return $message;
    }

    /**
     * Resolve/create the customer represented by
     * the incoming provider message.
     */
=======
        if ($wasCreated) {
            $this->broadcastMessageChange(
                $message,
                'created'
            );
        }

        return $message->fresh([
            'conversation',
            'attachments',
        ]);
    }

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    protected function resolveContact(
        ChannelConnection $connection,
        InboundMessageData $data
    ): Contact {
<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Existing channel identity
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
                    $data->externalContactId
                )
                ->first();

        if ($identity) {
            $contact =
                Contact::query()
                    ->whereKey(
                        $identity->contact_id
                    )
                    ->where(
                        'tenant_id',
                        $data->tenantId
                    )
                    ->first();

            if (!$contact) {
                throw new RuntimeException(
                    'Contact identity references an invalid contact.'
                );
            }

            $this->updateContactDetails(
                contact:
                    $contact,

                data:
                    $data
            );

            return $contact;
        }

        /*
        |--------------------------------------------------------------------------
        | Attempt matching by email
        |--------------------------------------------------------------------------
        */

        $contact = null;

        if ($data->contactEmail) {
            $contact =
                Contact::query()
                    ->where(
                        'tenant_id',
                        $data->tenantId
                    )
                    ->where(
                        'email',
                        $data->contactEmail
                    )
                    ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Attempt matching by phone
        |--------------------------------------------------------------------------
        */

        if (
            !$contact &&
            $data->contactPhone
        ) {
            $contact =
                Contact::query()
                    ->where(
                        'tenant_id',
                        $data->tenantId
                    )
                    ->where(
                        'phone',
                        $data->contactPhone
                    )
                    ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Create contact
        |--------------------------------------------------------------------------
        */

        if (!$contact) {
            $contact =
                new Contact();

            $contact->tenant_id =
                $data->tenantId;

            $contact->name =
                $data->contactName;

            $contact->email =
                $data->contactEmail;

            $contact->phone =
                $data->contactPhone;

            $contact->status =
                'active';

            $contact->metadata = [
                'created_from_channel' =>
                    $connection->type,
            ];

            $contact->save();
        } else {
            $this->updateContactDetails(
                contact:
                    $contact,

                data:
                    $data
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create channel identity
        |--------------------------------------------------------------------------
        */

        $identity =
            new ContactIdentity();

        $identity->tenant_id =
            $data->tenantId;

        $identity->contact_id =
            $contact->id;

        $identity->channel_connection_id =
            $connection->id;

        $identity->channel =
            $connection->type;

        $identity->external_user_id =
            $data->externalContactId;

        $identity->display_name =
            $data->contactName;

        $identity->metadata = [
            'source' =>
                $connection->provider
                ?? $connection->type,
        ];

        $identity->save();
=======
        $identity = ContactIdentity::query()
            ->where(
                'tenant_id',
                $connection->tenant_id
            )
            ->where(
                'channel_connection_id',
                $connection->id
            )
            ->where(
                'external_user_id',
                $data->externalContactId
            )
            ->first();

        if ($identity) {
            $contact = Contact::query()
                ->whereKey($identity->contact_id)
                ->where(
                    'tenant_id',
                    $connection->tenant_id
                )
                ->first();

            if ($contact) {
                $changed = false;

                if (
                    !$contact->name
                    && $data->contactName
                ) {
                    $contact->name =
                        $data->contactName;
                    $changed = true;
                }

                if (
                    !$contact->email
                    && $data->contactEmail
                ) {
                    $contact->email =
                        $data->contactEmail;
                    $changed = true;
                }

                if (
                    !$contact->phone
                    && $data->contactPhone
                ) {
                    $contact->phone =
                        $data->contactPhone;
                    $changed = true;
                }

                if ($changed) {
                    $contact->save();
                }

                return $contact;
            }
        }

        $contact = Contact::create([
            'tenant_id' =>
                $connection->tenant_id,

            'name' =>
                $data->contactName,

            'email' =>
                $data->contactEmail,

            'phone' =>
                $data->contactPhone,

            'company' =>
                null,

            'status' =>
                'active',

            'metadata' => [
                'created_from_channel_connection_id' =>
                    $connection->id,

                'provider' =>
                    $connection->provider,
            ],
        ]);

        $channelType =
            $connection->type instanceof \BackedEnum
                ? $connection->type->value
                : (string) $connection->type;

        ContactIdentity::create([
            'tenant_id' =>
                $connection->tenant_id,

            'contact_id' =>
                $contact->id,

            'channel_connection_id' =>
                $connection->id,

            'channel' =>
                $channelType,

            'external_user_id' =>
                $data->externalContactId,

            'display_name' =>
                $data->contactName,

            'username' =>
                null,

            'normalized_address' =>
                $data->externalContactId,

            'is_verified' =>
                false,

            'metadata' => [
                'provider' =>
                    $connection->provider,
            ],
        ]);
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        return $contact;
    }

<<<<<<< HEAD
    /**
     * Add new contact information without overwriting
     * information previously collected.
     */
    protected function updateContactDetails(
        Contact $contact,
        InboundMessageData $data
    ): void {
        $changed = false;

        if (
            !$contact->name &&
            $data->contactName
        ) {
            $contact->name =
                $data->contactName;

            $changed = true;
        }

        if (
            !$contact->email &&
            $data->contactEmail
        ) {
            $contact->email =
                $data->contactEmail;

            $changed = true;
        }

        if (
            !$contact->phone &&
            $data->contactPhone
        ) {
            $contact->phone =
                $data->contactPhone;

            $changed = true;
        }

        if ($changed) {
            $contact->save();
        }
    }

    /**
     * Resolve or create the conversation owning
     * this inbound message.
     */
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    protected function resolveConversation(
        ChannelConnection $connection,
        Contact $contact,
        InboundMessageData $data
    ): Conversation {
<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Provider thread ID
        |--------------------------------------------------------------------------
        |
        | This is the strongest conversation identifier.
        |
        */

        if ($data->externalThreadId) {
            $conversation =
                Conversation::query()
                    ->where(
                        'tenant_id',
                        $data->tenantId
                    )
                    ->where(
                        'channel_connection_id',
                        $connection->id
                    )
                    ->where(
                        'external_thread_id',
                        $data->externalThreadId
                    )
                    ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Existing active contact conversation
        |--------------------------------------------------------------------------
        */

        $conversation =
            Conversation::query()
                ->where(
                    'tenant_id',
                    $data->tenantId
=======
        $conversation = null;

        if ($data->externalThreadId) {
            $conversation = Conversation::query()
                ->where(
                    'tenant_id',
                    $connection->tenant_id
                )
                ->where(
                    'channel_connection_id',
                    $connection->id
                )
                ->where(
                    'external_thread_id',
                    $data->externalThreadId
                )
                ->first();
        }

        if (!$conversation) {
            $conversation = Conversation::query()
                ->where(
                    'tenant_id',
                    $connection->tenant_id
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                )
                ->where(
                    'channel_connection_id',
                    $connection->id
                )
                ->where(
                    'contact_id',
                    $contact->id
                )
                ->where(
                    'status',
                    'active'
                )
                ->latest('id')
                ->first();
<<<<<<< HEAD

        if ($conversation) {
            /*
             * Some providers may give us their thread ID
             * after our local conversation was created.
             */

            if (
                !$conversation
                    ->external_thread_id &&
                $data->externalThreadId
            ) {
                $conversation
                    ->external_thread_id =
                    $data->externalThreadId;

                $conversation->save();
            }

            return $conversation;
        }

        /*
        |--------------------------------------------------------------------------
        | Create conversation
        |--------------------------------------------------------------------------
        */

        $conversation =
            new Conversation();

        $conversation->tenant_id =
            $data->tenantId;
=======
        }

        if (!$conversation && $connection->website_id) {
            $conversation = Conversation::query()
                ->where(
                    'website_id',
                    $connection->website_id
                )
                ->where(
                    'visitor_id',
                    $data->externalContactId
                )
                ->first();
        }

        if (!$conversation) {
            $conversation = new Conversation();

            $conversation->status =
                'active';

            $conversation->mode =
                'ai';

            $conversation->priority =
                'normal';

            $conversation->unread_count =
                0;

            if ($connection->website_id) {
                $conversation->website_id =
                    $connection->website_id;

                $conversation->visitor_id =
                    $data->externalContactId;

                $conversation->lead_stage =
                    'discovery';
            }
        }

        $conversation->tenant_id =
            $connection->tenant_id;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        $conversation->ai_agent_id =
            $connection->ai_agent_id;

        $conversation->channel_connection_id =
            $connection->id;

        $conversation->contact_id =
            $contact->id;

<<<<<<< HEAD
        /*
         * Preserve compatibility with existing
         * website conversations.
         */
        if ($connection->website_id) {
=======
        if ($data->externalThreadId) {
            $conversation->external_thread_id =
                $data->externalThreadId;
        }

        if (
            $connection->website_id
            && !$conversation->website_id
        ) {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            $conversation->website_id =
                $connection->website_id;
        }

<<<<<<< HEAD
        $conversation->external_thread_id =
            $data->externalThreadId;

        $conversation->status =
            'active';

        $conversation->mode =
            'ai';

        $conversation->priority =
            'normal';

        $conversation->unread_count =
            0;

        $conversation->last_message_at =
            null;

        $conversation->last_inbound_at =
            null;

        $conversation->metadata = [
            'channel' =>
                $connection->type,

            'provider' =>
                $connection->provider,
        ];
=======
        if (
            $connection->website_id
            && !$conversation->visitor_id
        ) {
            $conversation->visitor_id =
                $data->externalContactId;
        }

        $metadata =
            is_array($conversation->metadata)
                ? $conversation->metadata
                : [];

        $metadata['channel'] =
            $connection->type instanceof \BackedEnum
                ? $connection->type->value
                : $connection->type;

        $metadata['provider'] =
            $connection->provider;

        $conversation->metadata =
            $metadata;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        $conversation->save();

        return $conversation;
    }

<<<<<<< HEAD
    /**
     * Store provider attachment metadata.
     *
     * The actual file does not necessarily need to be
     * downloaded during webhook processing.
     */
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    protected function storeAttachments(
        Message $message,
        array $attachments
    ): void {
<<<<<<< HEAD
        foreach (
            $attachments as $attachment
        ) {
=======
        foreach ($attachments as $attachment) {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
            if (!is_array($attachment)) {
                continue;
            }

<<<<<<< HEAD
            $record =
                new MessageAttachment();

            $record->message_id =
                $message->id;

            $record->external_attachment_id =
                $attachment[
                    'external_attachment_id'
                ]
                ?? $attachment['id']
                ?? null;

            $record->type =
                $attachment['type']
                ?? 'file';

            $record->mime_type =
                $attachment['mime_type']
                ?? null;

            $record->original_name =
                $attachment['original_name']
                ?? $attachment['name']
                ?? null;

            $record->external_url =
                $attachment['external_url']
                ?? $attachment['url']
                ?? null;

            $record->size =
                $attachment['size']
                ?? null;

            $record->status =
                'pending';

            $record->metadata =
                $attachment['metadata']
                ?? null;

            $record->save();
        }
    }

    /**
     * Broadcast without allowing a temporary Reverb
     * failure to reject an otherwise valid provider
     * webhook.
     */
=======
            MessageAttachment::create([
                'message_id' =>
                    $message->id,

                'external_attachment_id' =>
                    $attachment['external_attachment_id']
                    ?? null,

                'type' =>
                    $attachment['type']
                    ?? 'file',

                'mime_type' =>
                    $attachment['mime_type']
                    ?? null,

                'original_name' =>
                    $attachment['original_name']
                    ?? null,

                'storage_disk' =>
                    $attachment['storage_disk']
                    ?? null,

                'storage_path' =>
                    $attachment['storage_path']
                    ?? null,

                'external_url' =>
                    $attachment['external_url']
                    ?? null,

                'size' =>
                    $attachment['size']
                    ?? null,

                'checksum' =>
                    $attachment['checksum']
                    ?? null,

                'status' =>
                    $attachment['status']
                    ?? 'pending',

                'error_message' =>
                    $attachment['error_message']
                    ?? null,

                'metadata' =>
                    $attachment['metadata']
                    ?? [],
            ]);
        }
    }

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
    protected function broadcastMessageChange(
        Message $message,
        string $changeType
    ): void {
        try {
            $message->refresh();
<<<<<<< HEAD

            $message->load(
                'conversation'
            );
=======
            $message->load('conversation');
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

            OmnichannelMessageChanged::dispatch(
                $message,
                $changeType
            );
        } catch (Throwable $exception) {
            Log::warning(
                'Omnichannel realtime broadcast failed.',
                [
                    'message_id' =>
                        $message->id,

                    'change_type' =>
                        $changeType,

                    'error' =>
                        $exception->getMessage(),
                ]
            );
        }
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
