@extends('layouts.base')

@section('title', __('Nostr Login Guide - Decentralized Authentication') . ' – Satscribe')
@section('description', __('Learn how to login to Satscribe using Nostr protocol. Privacy-focused, decentralized authentication without passwords or email.'))

@section('content')
    <section class="px-4 sm:px-6 lg:px-8 py-6 space-y-6">
        <x-page.header title="Login with Nostr" />
        <div class="prose max-w-none space-y-4">
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
                Other compatible extensions like
                <a href="https://github.com/fiatjaf/nos2x" target="_blank">nos2x</a> or
                <a href="https://github.com/t4t5/flamingo" target="_blank">Flamingo</a>
                work similarly.
            </p>

            <h3>Option 2: Private Key Login</h3>
            <p>
                If you prefer, you can log in using your Nostr private key (<code>nsec</code>).
                When prompted, enter your private key and Satscribe will sign the challenge
                for you directly. Keep your private key safe and never share it with anyone.
            </p>

            <h3>Option 3: Generate a Key with Satscribe</h3>
            <p>
                Need a key? You can generate one on Satscribe. The private key is created
                in your browser and stored only in your storage application client (local storage).
                We never store it on our servers. Save the <code>nsec</code> in a password manager,
                then delete it from local storage for safety.
            </p>

            <h2>New to All This?</h2>
            <ol>
                <li><strong>Start with Alby</strong>: Quick and beginner-friendly.</li>
                <li><strong>Explore Nostr</strong>: Visit <a href="https://nostr.how" target="_blank">nostr.how</a> for a simple introduction.</li>
                <li><strong>Use your ID anywhere</strong>: The same login works across many Nostr-enabled platforms.</li>
            </ol>

            <h2>Privacy by Default</h2>
            <p>
                With Nostr, there’s nothing to forget, reset, or compromise. You prove who you are by signing a message with your key—safely stored on your device.
                All the tech behind this is <strong>open source</strong>. You can read the code, understand how it works, and even contribute.
                Curious? Visit our <a href="https://github.com/chemaclass/satscribe" target="_blank">GitHub</a> to explore or get involved.
            </p>
        </div>
    </section>
@endsection
