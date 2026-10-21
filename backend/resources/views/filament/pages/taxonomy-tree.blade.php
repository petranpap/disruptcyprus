<x-filament-panels::page>
    {{-- Own styles: Filament ships precompiled CSS, so arbitrary utility classes would not exist. Light + dark. --}}
    <style>
        .tx { --line: #d4d4d8; --muted: #71717a; --card: #fff; --border: #e4e4e7; --hover: #f4f4f5; --accent: #0077B6; font-size: 0.875rem; }
        .dark .tx { --line: #3f3f46; --muted: #a1a1aa; --card: #18181b; --border: #27272a; --hover: #27272a; --accent: #38bdf8; }
        .tx ul { list-style: none; margin: 0; padding: 0; }
        .tx-root { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.875rem; border-radius: 0.75rem;
            background: var(--accent); color: #fff; font-weight: 700; font-size: 1rem; }
        .tx-tree { margin-left: 1.25rem; }
        .tx-tree li { position: relative; padding: 0.375rem 0 0.375rem 1.75rem; }
        /* Connector lines: a vertical rail per level and an elbow into every node. */
        .tx-tree li::before { content: ""; position: absolute; left: 0; top: 0; bottom: 0; border-left: 2px solid var(--line); }
        .tx-tree li:last-child::before { bottom: auto; height: 1.4rem; }
        .tx-tree li::after { content: ""; position: absolute; left: 0; top: 1.4rem; width: 1.25rem; border-top: 2px solid var(--line); }
        .tx-branch { display: flex; flex-direction: column; gap: 0.125rem; padding: 0.625rem 0.875rem; border: 1px solid var(--border);
            border-radius: 0.75rem; background: var(--card); max-width: 46rem; }
        .tx-branch-title { font-weight: 700; font-size: 0.95rem; }
        .tx-help { color: var(--muted); font-size: 0.8rem; line-height: 1.4; }
        .tx-node { display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem 0.75rem; padding: 0.375rem 0.625rem; border-radius: 0.5rem;
            border: 1px solid var(--border); background: var(--card); text-decoration: none; color: inherit; max-width: 46rem; }
        a.tx-node:hover { background: var(--hover); border-color: var(--accent); }
        .tx-name { font-weight: 600; }
        .tx-meta { color: var(--muted); font-size: 0.8rem; }
        .tx-dot { width: 0.625rem; height: 0.625rem; border-radius: 9999px; flex-shrink: 0; }
        .tx-badge { font-size: 0.7rem; font-weight: 600; padding: 0.0625rem 0.5rem; border-radius: 9999px; background: var(--hover); color: var(--muted); }
        .tx-group > summary { list-style: none; cursor: pointer; }
        .tx-group > summary::-webkit-details-marker { display: none; }
        .tx-group > summary .tx-node::before { content: "▸"; color: var(--muted); transition: transform 150ms ease; }
        .tx-group[open] > summary .tx-node::before { transform: rotate(90deg); }
        .tx-slug { font-family: ui-monospace, monospace; font-size: 0.75rem; color: var(--muted); }
    </style>

    <div class="tx">
        <span class="tx-root">Disrupt Cyprus</span>

        <ul class="tx-tree">
            <li>
                <div class="tx-branch">
                    <span class="tx-branch-title">{{ __('admin.tree.sections') }}</span>
                    <span class="tx-help">{{ __('admin.tree.sections_help') }}</span>
                </div>
                <ul class="tx-tree">
                    @foreach ($sections as $section)
                        <li>
                            <a href="{{ $section['url'] }}" class="tx-node">
                                <span class="tx-name">{{ $section['name'] }}</span>
                                <span class="tx-slug">/{{ $section['slug'] }}</span>
                                <span class="tx-meta">
                                    {{ $section['has_articles'] ? trans_choice('admin.tree.articles', $section['articles'], ['count' => $section['articles']]) : __('admin.tree.events_section') }}
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </li>

            <li>
                <div class="tx-branch">
                    <span class="tx-branch-title">{{ __('admin.tree.industries') }}</span>
                    <span class="tx-help">{{ __('admin.tree.industries_help') }}</span>
                </div>
                <ul class="tx-tree">
                    @foreach ($groups as $group)
                        <li>
                            <details class="tx-group" open>
                                <summary>
                                    <span class="tx-node">
                                        <span class="tx-name">{{ $group['name'] }}</span>
                                        <span class="tx-meta">{{ trans_choice('admin.tree.industries_count', $group['industries']->count(), ['count' => $group['industries']->count()]) }}</span>
                                    </span>
                                </summary>
                                <ul class="tx-tree">
                                    @foreach ($group['industries'] as $industry)
                                        <li>
                                            <a href="{{ $industry['url'] }}" class="tx-node">
                                                <span class="tx-dot" style="background: {{ $industry['color'] }}" aria-hidden="true"></span>
                                                <span class="tx-name">{{ $industry['name'] }}</span>
                                                <span class="tx-meta">
                                                    {{ trans_choice('admin.tree.articles', $industry['articles'], ['count' => $industry['articles']]) }}
                                                    · {{ trans_choice('admin.tree.followers', $industry['followers'], ['count' => $industry['followers']]) }}
                                                </span>
                                                @unless ($industry['active'])
                                                    <span class="tx-badge">{{ __('admin.tree.inactive') }}</span>
                                                @endunless
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </details>
                        </li>
                    @endforeach
                </ul>
            </li>
        </ul>
    </div>
</x-filament-panels::page>
