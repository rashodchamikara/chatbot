<?php

namespace App\Providers;

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
use App\Models\Website;
use App\Observers\WebsiteObserver;

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
            function (
                $app
            ): ExtractorManager {
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
        Schema::defaultStringLength(
            191
        );

        /*
        |--------------------------------------------------------------------------
        | Model observers
        |--------------------------------------------------------------------------
        */

        Website::observe(
            WebsiteObserver::class
        );
    }
}