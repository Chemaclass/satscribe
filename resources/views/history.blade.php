@extends('layouts.base')

@section('title', 'Satscribe History')

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
                @foreach($chats as $chat)
                    <x-history.item
                        :chat="$chat"
                        :owned="($chat->tracking_id === tracking_id())"
                    />
                @endforeach
            </ul>
            <x-history.pagination :paginator="$chats"/>
        @endif
    </section>


@endsection
