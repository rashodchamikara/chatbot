<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Services\Omnichannel\WebsiteConversationResolver;
use Illuminate\Console\Command;
use Throwable;

class BackfillWebsiteConversations extends Command
{
    protected $signature =
<<<<<<< HEAD
        'omnichannel:backfill-website-conversations';

    protected $description =
        'Migrate existing website conversations into the shared omnichannel contact/channel architecture.';

    public function handle(
        WebsiteConversationResolver
            $resolver
    ): int {
        $processed = 0;
        $skipped = 0;
        $duplicates = 0;
        $failed = 0;

        Conversation::query()
            ->whereNotNull(
                'website_id'
            )
            ->whereNotNull(
                'visitor_id'
            )
            ->with('website')
            ->orderBy('id')
            ->chunkById(
                100,
                function (
                    $conversations
                ) use (
                    $resolver,
                    &$processed,
                    &$skipped,
                    &$duplicates,
                    &$failed
                ): void {
                    foreach (
                        $conversations
                        as $conversation
                    ) {
                        if (
                            !$conversation
                                ->website
                        ) {
                            $this->warn(
                                "Conversation {$conversation->id}: website is missing."
                            );

                            $skipped++;

                            continue;
                        }

                        try {
                            $resolved =
                                $resolver
                                    ->resolve(
                                        $conversation
                                            ->website,

                                        $conversation
                                            ->visitor_id
                                    );

                            /*
                             * Two old conversations with the same
                             * website + visitor would resolve to
                             * one canonical conversation.
                             *
                             * Do not delete data automatically.
                             */
                            if (
                                (int)
                                $resolved->id
                                !==
                                (int)
                                $conversation->id
                            ) {
                                $duplicates++;

                                $this->warn(
                                    "Possible duplicate: conversation {$conversation->id} "
                                    . "resolves to canonical conversation {$resolved->id}."
                                );

                                continue;
                            }

                            $processed++;

                            $this->line(
                                "Backfilled conversation {$conversation->id}."
                            );
                        } catch (Throwable $exception) {
                            $failed++;

                            $this->error(
                                "Conversation {$conversation->id} failed: "
                                . $exception->getMessage()
                            );
                        }
                    }
                }
            );
=======
        'omnichannel:backfill-website-conversations
        {--conversation= : Backfill only one conversation ID}';

    protected $description =
        'Attach legacy website conversations to contacts and native omnichannel channel connections.';

    public function handle(
        WebsiteConversationResolver $resolver
    ): int {
        $query = Conversation::query()
            ->with('website')
            ->whereNotNull('website_id')
            ->whereNotNull('visitor_id')
            ->orderBy('id');

        if ($this->option('conversation')) {
            $query->where(
                'id',
                (int) $this->option('conversation')
            );
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $query->chunkById(
            100,
            function ($conversations) use (
                $resolver,
                &$processed,
                &$skipped,
                &$failed
            ): void {
                foreach ($conversations as $conversation) {
                    if (!$conversation->website) {
                        $skipped++;
                        continue;
                    }

                    try {
                        $resolved =
                            $resolver->resolve(
                                $conversation->website,
                                (string)
                                $conversation->visitor_id
                            );

                        $processed++;

                        $this->line(
                            "Conversation {$resolved->id}: "
                            . "channel {$resolved->channel_connection_id}, "
                            . "contact {$resolved->contact_id}"
                        );
                    } catch (Throwable $exception) {
                        $failed++;

                        $this->error(
                            "Conversation {$conversation->id} failed: "
                            . $exception->getMessage()
                        );
                    }
                }
            }
        );
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

        $this->newLine();

        $this->info(
            "Completed. Processed: {$processed}; "
<<<<<<< HEAD
            . "skipped: {$skipped}; "
            . "duplicates: {$duplicates}; "
            . "failed: {$failed}."
        );

        if (
            $duplicates > 0
        ) {
            $this->warn(
                'Duplicate legacy conversations were detected. '
                . 'Review them before adding the unique thread index.'
            );
        }

=======
            . "skipped: {$skipped}; failed: {$failed}."
        );

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
