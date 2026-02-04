@extends('layouts.base')

@section('title', __('Chat History - Browse Bitcoin Blockchain Conversations') . ' – Satscribe')
@section('description', __('Explore past conversations about Bitcoin blocks and transactions. Learn from AI-generated explanations of blockchain data.'))

@section('content')
    <section id="history" class="sm:px-6 lg:px-8 px-4 py-6">
        {{-- Header --}}
        <x-page.header
            title="{{ __('History') }}"
            titleClass="text-3xl sm:text-4xl font-bold leading-tight"
        >
            <p class="text-lg mt-2">
                {{ __('From detailed tx investigations to high-level block summaries.') }}
                <br class="hidden sm:block">
                <small>
                    {{ __('Dive into personal insights or') }}
                    <a href="{{ route('history.index', ['all' => 1]) }}">{{ __('browse the archive') }}</a>
                    {{ __('of the community. Discover how others interpret txs and blocks.') }}
                </small>
            </p>
        </x-page.header>

        @if ($chats->isEmpty())
            <x-history.empty/>
        @else
            <ul class="chat-list">
                @foreach($chats as $item)
                    <x-history.item :item="$item" />
                @endforeach
            </ul>
            <x-history.pagination :paginator="$chats"/>
        @endif
    </section>

    <div id="share-toast"
         class="fixed top-6 right-6 bg-orange-400 text-white text-sm px-4 py-2 rounded shadow-lg z-50 opacity-0 transition-opacity"
         style="display: none;">
        {{ __('Link Copied!') }}
    </div>


@endsection
