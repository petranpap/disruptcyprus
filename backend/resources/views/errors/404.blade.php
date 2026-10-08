{{-- Standalone (no session, no layout data): shown for unknown or unpublished links on both hosts. --}}
<!DOCTYPE html>
<html lang="el">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>404 · Disrupt Cyprus</title>
    <link rel="icon" href="{{ asset('site/favicon.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('site/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('site/site.css') }}">
</head>
<body>
    <main id="main" class="container-reading reading not-found">
        <a href="{{ url('/') }}">@include('site.partials.logo')</a>
        <h1 class="article-title">Η σελίδα δεν βρέθηκε</h1>
        <p class="article-excerpt">Ο σύνδεσμος μπορεί να έχει αλλάξει ή το θέμα να μην είναι πια διαθέσιμο.</p>
        <p class="article-excerpt" lang="en">This page doesn't exist, or the story is no longer available.</p>
        <p class="hero-actions">
            <a href="{{ url('/') }}" class="btn btn-primary">Αρχική · Home</a>
            <a href="{{ rtrim((string) config('app.frontend_url'), '/') }}" class="btn btn-outline">Disrupt Cyprus app</a>
        </p>
    </main>
</body>
</html>
