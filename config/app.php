<?php

declare(strict_types=1);

const APP_NAME = 'Village Connect SRP';
const BASE_URL = '';

const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'village_connect';
const DB_USER = 'root';
const DB_PASS = 'root';

const MAX_UPLOAD_SIZE = 50 * 1024 * 1024; // 50MB
const ALLOWED_MEDIA_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'audio/mpeg' => 'mp3',
    'audio/wav' => 'wav',
    'audio/ogg' => 'ogg',
];

const UPLOAD_DIR = __DIR__ . '/../uploads/media';
