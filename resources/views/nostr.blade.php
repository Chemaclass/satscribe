@extends('layouts.base')

@section('title', 'Nostr Login Guide')

@section('content')
    <section class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <x-page.header title="Login with Nostr" />
        <div class="prose dark:prose-invert max-w-none space-y-4">
            <p>
                At Satscribe, we believe in privacy and user ownership. That’s why we use
                <a href="https://nostr.com" target="_blank">Nostr</a>—a simple, open protocol for a more private web.
                No passwords. No emails. Just your digital identity, fully under your control.
            </p>

            <h2>What is Nostr?</h2>
            <p>
                Nostr stands for <strong>Notes and Other Stuff Transmitted by Relays</strong>. It’s a lightweight way to communicate and log in online, built around your public key.
            </p>
            <ul>
                <li><strong>You own your identity</strong> — like having your own digital keys.</li>
                <li><strong>Use it anywhere</strong> — your Nostr ID works across many sites and apps.</li>
                <li><strong>No need to share personal info</strong> — privacy comes built-in.</li>
            </ul>

            <h2>Why It’s Great for Logging In</h2>
            <ul>
                <li><strong>No accounts to manage</strong> — we don’t store your data.</li>
                <li><strong>Decentralized</strong> — no central company controls your identity.</li>
                <li><strong>Simple and secure</strong> — just sign a message with your key to log in.</li>
            </ul>

            <h2>How to Get Started</h2>
            <p>
                The easiest method is using a browser extension like <a href="https://getalby.com/products/browser-extension" target="_blank">Alby</a>, which acts as your Nostr wallet.
            </p>

            <h3>Option 1: Browser Extension (Recommended)</h3>
            <ol>
                <li>Install the <a href="https://getalby.com/products/browser-extension" target="_blank">Alby extension</a> (available for Chrome, Brave, or Firefox).</li>
                <li>Follow the setup to create or import your Nostr identity.</li>
                <li>Visit Satscribe and click <strong>Login → Nostr</strong>.</li>
                <li>Approve the login request in the extension.</li>
            </ol>
            <p>
                Other compatible extensions like Flamingo or nos2x work similarly.
            </p>

            <h3>Option 2: Manual Login</h3>
            <p>
                You can also log in using any Nostr-compatible app or wallet:
            </p>
            <ol>
                <li>Click <strong>Login → Nostr</strong> on Satscribe to get a challenge string.</li>
                <li>Sign it as the <code>content</code> of a <code>kind 22242</code> event in your app.</li>
                <li>Copy the resulting signed JSON.</li>
                <li>Paste it back into Satscribe when prompted.</li>
            </ol>
            <p>
                This method is secure but more technical. For most users, browser extensions are easier.
            </p>

            <h2>New to All This?</h2>
            <ol>
                <li><strong>Start with Alby</strong>: Quick and beginner-friendly.</li>
                <li><strong>Explore Nostr</strong>: Visit <a href="https://nostr.how" target="_blank">nostr.how</a> for a simple introduction.</li>
                <li><strong>Use your ID anywhere</strong>: The same login works across many Nostr-enabled platforms.</li>
            </ol>

            <h2>🛡️ Privacy by Default</h2>
            <p>
                With Nostr, there’s nothing to forget, reset, or compromise. You prove who you are by signing a message with your key—safely stored on your device.
                All the tech behind this is <strong>open source</strong>. You can read the code, understand how it works, and even contribute.
                Curious? Visit our <a href="https://github.com/chemaclass/satscribe" target="_blank">GitHub</a> to explore or get involved.
            </p>
        </div>
    </section>
@endsection
