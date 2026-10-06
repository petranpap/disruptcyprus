<?php

use App\Services\Content\HtmlSanitizer;

it('keeps editorial markup and strips dangerous content', function () {
    $html = '<h2>Title</h2><p onclick="x()">Text <strong>bold</strong> <a href="https://example.com" target="_blank">link</a></p>'
        .'<blockquote><p>Quote</p><cite>Someone</cite></blockquote><script>alert(1)</script>'
        .'<iframe src="https://evil.example"></iframe><img src="https://cdn.example/a.webp" alt="A" onerror="x()">'
        .'<a href="javascript:alert(1)">bad</a>';

    $clean = (new HtmlSanitizer)->sanitize($html);

    expect($clean)
        ->toContain('<h2>Title</h2>')
        ->toContain('<strong>bold</strong>')
        ->toContain('<blockquote><p>Quote</p><cite>Someone</cite></blockquote>')
        ->toContain('<img src="https://cdn.example/a.webp" alt="A" />')
        ->toContain('rel="noopener noreferrer nofollow"')
        ->not->toContain('script')
        ->not->toContain('iframe')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('javascript:')
        ->not->toContain('target=');
});
