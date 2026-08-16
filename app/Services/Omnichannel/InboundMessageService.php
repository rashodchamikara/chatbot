<?php

namespace App\Services\Omnichannel;

use App\Data\Omnichannel\InboundMessageData;
use App\Models\ChannelConnection;
use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InboundMessageService
{
    /**
     * Process one normalized inbound message.
     *
     * Every external channel should eventually pass its
     * normalized inbound messages through this method.
     */
    public function handle(
        InboundMessageData $data
    ): Message {
        return DB::transaction(function () use ($data): Message {

            /*
            |--------------------------------------------------------------------------
            | 1. Resolve and validate the channel connection
            |--------------------------------------------------------------------------
            */

            $connection = ChannelConnection::query()
                ->whereKey($data->channelConnectionId)
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
            | 2. Idempotency / duplicate protection
            |--------------------------------------------------------------------------
            |
            | Providers can send the same webhook more than once.
            |
            | Never create the same provider message twice.
            |
            */

            $existingMessage = Message::query()
                ->where(
                    'channel_connection_id',
                    $connection->id
                )
                ->where(
                    'external_message_id',
                    $data->externalMessageId
                )
                ->first();

            if ($existingMessage) {
                return $existingMessage;
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Resolve customer/contact
            |--------------------------------------------------------------------------
            */

            $contact = $this->resolveContact(
                connection: $connection,
                data: $data
            );

            /*
            |--------------------------------------------------------------------------
            | 4. Resolve conversation
            |--------------------------------------------------------------------------
            */

            $conversation = $this->resolveConversation(
                connection: $connection,
                contact: $contact,
                data: $data
            );

            /*
            |--------------------------------------------------------------------------
            | 5. Store inbound message
            |--------------------------------------------------------------------------
            */

            $message = new Message();

            $message->conversation_id =
                $conversation->id;

            $message->channel_connection_id =
                $connection->id;

            $message->external_message_id =
                $data->externalMessageId;

            $message->direction =
                'inbound';

            $message->sender_type =
                'contact';

            $message->message_type =
                $data->messageType;

            /*
             * Existing AI Sales Agent message body column.
             */
            $message->message =
                $data->text ?? '';

            /*
             * Keep provider/channel-specific data separately.
             */
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

            $message->is_ai_generated =
                false;

            $message->save();

            /*
            |--------------------------------------------------------------------------
            | 6. Store attachment metadata
            |--------------------------------------------------------------------------
            */

            $this->storeAttachments(
                message: $message,
                attachments: $data->attachments
            );

            /*
            |--------------------------------------------------------------------------
            | 7. Update conversation inbox state
            |--------------------------------------------------------------------------
            */

            $now = now();

            $conversation->last_message_at =
                $now;

            $conversation->last_inbound_at =
                $now;

            $conversation->unread_count =
                ((int) $conversation->unread_count) + 1;

            $conversation->save();

            return $message;
        });
    }

    /**
     * Resolve the internal contact represented by
     * the provider/channel identity.
     */
    protected function resolveContact(
        ChannelConnection $connection,
        InboundMessageData $data
    ): Contact {
        /*
        |--------------------------------------------------------------------------
        | First preference: existing channel identity
        |--------------------------------------------------------------------------
        */

        $identity = ContactIdentity::query()
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
                    $data->tenantId
                )
                ->first();

            if (!$contact) {
                throw new RuntimeException(
                    'Contact identity references an invalid contact.'
                );
            }

            $this->updateContactDetails(
                contact: $contact,
                data: $data
            );

            return $contact;
        }

        /*
        |--------------------------------------------------------------------------
        | Second preference: known email
        |--------------------------------------------------------------------------
        */

        $contact = null;

        if ($data->contactEmail) {
            $contact = Contact::query()
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
        | Third preference: known phone
        |--------------------------------------------------------------------------
        */

        if (
            !$contact &&
            $data->contactPhone
        ) {
            $contact = Contact::query()
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
        | Otherwise create a new contact
        |--------------------------------------------------------------------------
        */

        if (!$contact) {
            $contact = new Contact();

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
                contact: $contact,
                data: $data
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Create channel identity
        |--------------------------------------------------------------------------
        */

        $identity = new ContactIdentity();

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

        return $contact;
    }

    /**
     * Fill missing contact information without
     * destroying data already collected.
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
     * Resolve or create the conversation that owns
     * this inbound message.
     */
    protected function resolveConversation(
        ChannelConnection $connection,
        Contact $contact,
        InboundMessageData $data
    ): Conversation {
        /*
        |--------------------------------------------------------------------------
        | Provider thread ID is the strongest identifier
        |--------------------------------------------------------------------------
        */

        if ($data->externalThreadId) {
            $conversation = Conversation::query()
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
        | Otherwise use the customer's current active conversation
        |--------------------------------------------------------------------------
        */

        $conversation = Conversation::query()
            ->where(
                'tenant_id',
                $data->tenantId
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

        if ($conversation) {
            /*
             * A provider may give us the external thread ID
             * after the conversation was originally created.
             */
            if (
                !$conversation->external_thread_id &&
                $data->externalThreadId
            ) {
                $conversation->external_thread_id =
                    $data->externalThreadId;

                $conversation->save();
            }

            return $conversation;
        }

        /*
        |--------------------------------------------------------------------------
        | Create new conversation
        |--------------------------------------------------------------------------
        */

        $conversation = new Conversation();

        $conversation->tenant_id =
            $data->tenantId;

        $conversation->ai_agent_id =
            $connection->ai_agent_id;

        $conversation->channel_connection_id =
            $connection->id;

        $conversation->contact_id =
            $contact->id;

        /*
         * Preserve existing website-based architecture.
         */
        if ($connection->website_id) {
            $conversation->website_id =
                $connection->website_id;
        }

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

        $conversation->save();

        return $conversation;
    }

    /**
     * Store normalized attachment references.
     *
     * Actual remote file downloading/storage can happen
     * asynchronously later.
     */
    protected function storeAttachments(
        Message $message,
        array $attachments
    ): void {
        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }

            $record =
                new MessageAttachment();

            $record->message_id =
                $message->id;

            $record->external_attachment_id =
                $attachment['external_attachment_id']
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

            /*
             * File itself has not necessarily been downloaded yet.
             */
            $record->status =
                'pending';

            $record->metadata =
                $attachment['metadata']
                    ?? null;

            $record->save();
        }
    }
}