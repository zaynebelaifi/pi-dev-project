<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GoogleTranslateService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
        private readonly string $fallbackEndpoint,
    ) {
    }

    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'en'): string
    {
        $normalizedText = trim($text);
        $normalizedTarget = $this->normalizeLanguageCode($targetLanguage);

        if ('' === $normalizedText || '' === $normalizedTarget || 'en' === $normalizedTarget) {
            return $normalizedText;
        }

        // When no Google Translate key is configured, try the fallback AI translator.
        if ('' === trim($this->apiKey)) {
            $fallbackTranslation = $this->translateViaFallbackAi($normalizedText, $normalizedTarget);

            return $fallbackTranslation ?: $normalizedText;
        }

        try {
            $response = $this->httpClient->request('POST', 'https://translation.googleapis.com/language/translate/v2', [
                'query' => ['key' => $this->apiKey],
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ],
                'body' => [
                    'q' => $normalizedText,
                    'target' => $normalizedTarget,
                    'source' => $this->normalizeLanguageCode($sourceLanguage),
                    'format' => 'text',
                ],
                'timeout' => 20,
            ]);

            if (200 !== $response->getStatusCode()) {
                throw new \RuntimeException('Google Translate API returned a non-200 response.');
            }

            $payload = $response->toArray(false);
            $translatedText = $payload['data']['translations'][0]['translatedText'] ?? null;

            if (!is_string($translatedText) || '' === trim($translatedText)) {
                return $normalizedText;
            }

            return html_entity_decode($translatedText, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } catch (\Throwable $e) {
            $this->logger->warning('Google Translate API failed, returning original text.', [
                'message' => $e->getMessage(),
                'targetLanguage' => $normalizedTarget,
            ]);

            $fallbackTranslation = $this->translateViaFallbackAi($normalizedText, $normalizedTarget);

            return $fallbackTranslation ?: $normalizedText;
        }
    }

    private function translateViaFallbackAi(string $text, string $targetLanguage): ?string
    {
        $endpoint = trim($this->fallbackEndpoint);
        if ('' === $endpoint) {
            return null;
        }

        $normalizedLanguageCode = $this->normalizeLanguageCode($targetLanguage);
        $languageLabel = $this->describeLanguage($normalizedLanguageCode);
        $prompt = sprintf(
            'Translate the following text from English to target language code "%s" (%s). Return only the translated text without quotes or extra commentary. Text: %s',
            $normalizedLanguageCode,
            $languageLabel,
            $text
        );

        try {
            $response = $this->httpClient->request('GET', rtrim($endpoint, '/').'/'.rawurlencode($prompt), [
                'headers' => [
                    'Accept' => 'text/plain, application/json',
                ],
                'timeout' => 20,
            ]);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $body = trim($response->getContent(false));
            if ('' === $body) {
                return null;
            }

            if (str_starts_with($body, '{') || str_starts_with($body, '[')) {
                $decoded = json_decode($body, true);
                if (is_array($decoded)) {
                    foreach (['text', 'response', 'output', 'answer'] as $key) {
                        $candidate = $decoded[$key] ?? null;
                        if (is_string($candidate) && '' !== trim($candidate)) {
                            return trim($candidate);
                        }
                    }
                }
            }

            return $body;
        } catch (\Throwable $e) {
            $this->logger->warning('Fallback AI translation failed.', [
                'message' => $e->getMessage(),
                'targetLanguage' => $targetLanguage,
            ]);

            return null;
        }
    }

    private function normalizeLanguageCode(string $language): string
    {
        $language = strtolower(trim($language));

        if ('' === $language) {
            return 'en';
        }

        return match ($language) {
            'zh-cn', 'zh_cn', 'zh' => 'zh-CN',
            default => preg_replace('/[^a-z-]/', '', $language) ?: 'en',
        };
    }

    private function describeLanguage(string $language): string
    {
        return match ($this->normalizeLanguageCode($language)) {
            'ar' => 'Arabic',
            'fr' => 'French',
            'es' => 'Spanish',
            'de' => 'German',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ja' => 'Japanese',
            'zh-CN' => 'Chinese',
            'ko' => 'Korean',
            default => 'the requested language',
        };
    }
}