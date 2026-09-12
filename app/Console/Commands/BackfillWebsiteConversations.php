<?php

namespace App\Console\Commands;

use App\Models\Conversation;
use App\Services\Omnichannel\WebsiteConversationResolver;
use Illuminate\Console\Command;
use Throwable;

class BackfillWebsiteConversations extends Command
{
    protected $signature =
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

        $this->newLine();

        $this->info(
            "Completed. Processed: {$processed}; "
            . "skipped: {$skipped}; failed: {$failed}."
        );

        return $failed > 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
