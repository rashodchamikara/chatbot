<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Services\Knowledge\Extraction\DocxExtractor;
use App\Services\Knowledge\Extraction\ExtractorManager;
use App\Services\Knowledge\Extraction\PdfTextExtractor;
use App\Services\Knowledge\Extraction\PlainTextExtractor;
use App\Services\Knowledge\Extraction\SpreadsheetExtractor;
use App\Services\Knowledge\Extraction\ImageOcrExtractor;
use App\Services\Omnichannel\ChannelManager;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
        ExtractorManager::class,
            function ($app): ExtractorManager {
                return new ExtractorManager(
                    pdfExtractor: $app->make(
                        PdfTextExtractor::class
                    ),

                    plainTextExtractor: $app->make(
                        PlainTextExtractor::class
                    ),

                    docxExtractor: $app->make(
                        DocxExtractor::class
                    ),

                    spreadsheetExtractor: $app->make(
                        SpreadsheetExtractor::class
                    ),

                    imageOcrExtractor: $app->make(
                        ImageOcrExtractor::class
                    ),
                    
                );
            }
        
        );
        $this->app->singleton(
            ChannelManager::class,
            function (): ChannelManager {
                return new ChannelManager();
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
       Schema::defaultStringLength(191);
    }

    
}
