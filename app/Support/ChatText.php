<?php

namespace App\Support;

use Mews\Purifier\Facades\Purifier;

final class ChatText
{
    /**
     * Sanitize a participant name.
     */
    public static function name(string $value): string
    {
        $value = self::purify($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    /**
     * Sanitize a chat message while preserving line breaks.
     */
    public static function message(string $value): string
    {
        $value = self::purify($value);
        $value = str_replace(["\r\n", "\r"], "\n", $value);

        return trim($value);
    }

    /**
     * Remove HTML and dangerous markup from a value.
     *
     * HTMLPurifier returns HTML-encoded text (for example `&amp;` for `&`).
     * Because every value is escaped again by Blade or rendered with
     * `textContent` in the browser, the entities have to be decoded once so
     * they are not shown literally or double-escaped.
     */
    private static function purify(string $value): string
    {
        // Normalise entities first so HTMLPurifier sees real markup (for
        // example `&lt;script&gt;`) and strips it. Decoding only after
        // purification would turn that text back into executable markup.
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = Purifier::clean($value, ['HTML.Allowed' => '']);
        $value = strip_tags($value);

        // Purifier encodes plain characters such as `&`; decode the output
        // once so Blade and `textContent` do not escape them a second time.
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // A double-encoded tag (for example `&amp;lt;script&amp;gt;`) becomes
        // literal markup after the decode above. Strip any real tag that is
        // left while keeping harmless text such as `O'Neil <3`.
        $value = preg_replace('/<\/?[a-zA-Z][^>]*>/', '', $value) ?? $value;

        // Drop control characters that cannot be rendered safely.
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        return trim($value);
    }
}
