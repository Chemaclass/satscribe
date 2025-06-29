@extends('layouts.base')

@section('title', 'Nostr Login Guide')

@section('content')
    <section class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <x-page.header title="Login with Nostr" />
        <div class="prose dark:prose-invert max-w-none space-y-4">
            <p>Satscribe uses <a href="https://nostr.com">Nostr</a> as its authentication method. Instead of passwords, you prove ownership of a public key by signing a one time challenge.</p>

            <h2>Why Nostr?</h2>
            <ul>
                <li><strong>No account management</strong> &ndash; Satscribe never stores emails or passwords.</li>
                <li><strong>Portable identity</strong> &ndash; use the same Nostr pubkey across different services.</li>
                <li><strong>Works everywhere</strong> &ndash; any wallet or extension that can sign a <code>kind 22242</code> event is supported.</li>
            </ul>

            <h2>Using a Browser Extension (Alby)</h2>
            <ol>
                <li>Install the <a href="https://getalby.com/extension">Alby browser extension</a> for Chrome, Brave or Firefox.</li>
                <li>Create or import your Nostr identity when prompted.</li>
                <li>Visit Satscribe and click <strong>Login &rarr; Nostr</strong>. The extension will ask for permission to share your pubkey and sign the challenge.</li>
                <li>Approve the request and you will be logged in.</li>
            </ol>
            <p>Other extensions like Flamingo or nos2x work in a similar way if you prefer them.</p>

            <h2>Without an Extension</h2>
            <p>If you do not have a browser extension you can still sign in manually:</p>
            <ol>
                <li>Click <strong>Login &rarr; Nostr</strong> – a text challenge will appear.</li>
                <li>Copy that challenge and sign it in any Nostr app (mobile wallet, desktop client, etc.) as the <code>content</code> of a <code>kind 22242</code> event.</li>
                <li>Copy the JSON of the signed event.</li>
                <li>Paste the JSON back into Satscribe when prompted and submit.</li>
                <li>If the signature is valid you will be logged in using your Nostr pubkey.</li>
            </ol>
            <p>This manual method provides the same level of security without needing a browser extension.</p>
        </div>
    </section>
@endsection
