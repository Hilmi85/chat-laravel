<?php

namespace Tests\Unit;

use App\Support\ChatText;
use Tests\TestCase;

class ChatTextTest extends TestCase
{
    public function test_plain_text_special_characters_are_not_encoded(): void
    {
        $this->assertSame('Tom & Jerry <3', ChatText::message('Tom & Jerry <3'));
        $this->assertSame('A < B > C', ChatText::message('A < B > C'));
        $this->assertSame('1 < 2 and 3 > 2', ChatText::message('1 < 2 and 3 > 2'));
    }

    public function test_names_collapse_whitespace_and_keep_ampersands(): void
    {
        $this->assertSame('Rafif & Co', ChatText::name("  Rafif \n &   Co  "));
    }

    public function test_real_markup_and_scripts_are_removed(): void
    {
        $this->assertSame('Hello world', ChatText::message('<script>alert(1)</script>Hello <b>world</b>'));
        $this->assertSame('', ChatText::message('<img src=x onerror=alert(1)>'));
    }

    public function test_encoded_markup_never_becomes_a_real_tag(): void
    {
        $this->assertSame('', ChatText::message('&lt;script&gt;alert(1)&lt;/script&gt;'));
        $this->assertSame('', ChatText::message('&amp;lt;script&amp;gt;'));
        $this->assertSame('', ChatText::message('&#60;script&#62;alert(1)&#60;/script&#62;'));
    }

    public function test_encoded_ampersands_are_decoded_once(): void
    {
        $this->assertSame('Tom & Jerry', ChatText::message('Tom &amp; Jerry'));
    }
}
