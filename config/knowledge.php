<?php

return [


    'disk' => env('KNOWLEDGE_DISK', 's3'),

    'max_file_size_kb' => (int) env(
        'KNOWLEDGE_MAX_FILE_SIZE_KB',
        51200 // 50 MB
    ),

    'max_files_per_upload' => (int) env(
        'KNOWLEDGE_MAX_FILES_PER_UPLOAD',
        10
    ),

    'allowed_extensions' => [
        'pdf',
        'docx',
        'txt',
        'csv',
        'xlsx',
        'jpg',
        'jpeg',
        'png',
        'webp',
    ],


    'chunking' => [
        'max_tokens'    => (int) env('KNOWLEDGE_CHUNK_TOKENS', 700),
        'overlap_tokens'=> (int) env('KNOWLEDGE_CHUNK_OVERLAP', 100),
    ],


    'embedding' => [
        'model'      => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'dimensions' => (int) env('OPENAI_EMBEDDING_DIMENSIONS', 1536),
        'batch_size' => (int) env('OPENAI_EMBEDDING_BATCH_SIZE', 32),
    ],



    'qdrant' => [
        'url'        => env('QDRANT_URL', 'http://127.0.0.1:6333'),
        'api_key'    => env('QDRANT_API_KEY'),
        'collection' => env(
            'QDRANT_COLLECTION',
            'chatbot_knowledge_text_embedding_3_small_1536'
        ),
    ],

    'retrieval' => [
        'limit' => (int) env(
            'KNOWLEDGE_RETRIEVAL_LIMIT',
            8
        ),
        'minimum_score' => (float) env(
            'KNOWLEDGE_MINIMUM_SCORE',
            0.20
        ),

    ],
    'max_chunks_per_source' => (int) env(
            'KNOWLEDGE_MAX_CHUNKS_PER_SOURCE',
            3
    ),

];