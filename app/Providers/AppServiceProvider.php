<?php

namespace App\Providers;

<<<<<<< HEAD
=======
use App\Models\Website;
use App\Observers\WebsiteObserver;
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
use App\Services\Knowledge\Extraction\DocxExtractor;
use App\Services\Knowledge\Extraction\ExtractorManager;
use App\Services\Knowledge\Extraction\ImageOcrExtractor;
use App\Services\Knowledge\Extraction\PdfTextExtractor;
use App\Services\Knowledge\Extraction\PlainTextExtractor;
use App\Services\Knowledge\Extraction\SpreadsheetExtractor;
use App\Services\Omnichannel\Adapters\WebsiteAdapter;
use App\Services\Omnichannel\ChannelManager;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
<<<<<<< HEAD
use App\Models\Website;
use App\Observers\WebsiteObserver;
=======
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Knowledge extractors
        |--------------------------------------------------------------------------
        */

        $this->app->singleton(
            ExtractorManager::class,
<<<<<<< HEAD
            function (
                $app
            ): ExtractorManager {
=======
            function ($app): ExtractorManager {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                return new ExtractorManager(
                    pdfExtractor:
                        $app->make(
                            PdfTextExtractor::class
                        ),

                    plainTextExtractor:
                        $app->make(
                            PlainTextExtractor::class
                        ),

                    docxExtractor:
                        $app->make(
                            DocxExtractor::class
                        ),

                    spreadsheetExtractor:
                        $app->make(
                            SpreadsheetExtractor::class
                        ),

                    imageOcrExtractor:
                        $app->make(
                            ImageOcrExtractor::class
                        ),
                );
            }
        );

<<<<<<< HEAD
        /*
        |--------------------------------------------------------------------------
        | Omnichannel ChannelManager
        |--------------------------------------------------------------------------
        |
        | Sprint 3 registers the native website adapter.
        |
        | Sprint 4 will add WhatsApp without changing
        | controllers or the common services.
        |
        */

        $this->app->singleton(
            ChannelManager::class,
            function (
                $app
            ): ChannelManager {
=======
        $this->app->singleton(
            ChannelManager::class,
            function ($app): ChannelManager {
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
                $manager =
                    new ChannelManager();

                $manager->register(
                    $app->make(
                        WebsiteAdapter::class
                    )
                );

                return $manager;
            }
        );
    }

    public function boot(): void
    {
<<<<<<< HEAD
        Schema::defaultStringLength(
            191
        );

        /*
        |--------------------------------------------------------------------------
        | Model observers
        |--------------------------------------------------------------------------
        */

=======
        Schema::defaultStringLength(191);

>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
        Website::observe(
            WebsiteObserver::class
        );
    }
<<<<<<< HEAD
}
=======
}
>>>>>>> b81e2aa (Restore omnichannel Sprint 2 files)
