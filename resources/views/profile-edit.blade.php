@extends('layouts.base')

@section('title', 'Edit Profile')

@section('content')
    <section id="profile-edit" class="sm:px-6 lg:px-8 px-4 py-6">
        <div class="bg-white mt-2 rounded-lg p-6 shadow space-y-6">
            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-2">
                <h2 class="text-xl font-semibold text-gray-900">{{ __('Edit Nostr Profile') }}</h2>
                <a href="{{ route('profile.index') }}" class="px-2 py-1 rounded border text-sm link">{{ __('Cancel') }}</a>
            </div>
            <form id="nostr-profile-form" class="space-y-4">
                <div>
                    <label for="edit-name" class="block text-sm font-medium text-gray-700">Name</label>
                    <input id="edit-name" type="text" class="w-full p-2 border border-gray-300 rounded" />
                </div>

                <div>
                    <label for="edit-display_name" class="block text-sm font-medium text-gray-700">Display Name</label>
                    <input id="edit-display_name" type="text" class="w-full p-2 border border-gray-300 rounded" />
                </div>

                <div>
                    <label for="edit-about" class="block text-sm font-medium text-gray-700">About</label>
                    <textarea id="edit-about" class="w-full p-2 border border-gray-300 rounded"></textarea>
                </div>

                <div class="space-y-4 sm:space-y-0 sm:grid sm:grid-cols-2 sm:gap-4">
                    <div>
                        <label for="edit-picture" class="block text-sm font-medium text-gray-700">Picture</label>
                        <img id="picture-preview" class="hidden w-full h-32 object-cover rounded mb-2" alt="picture preview" />
                        <input id="edit-picture" type="text" class="w-full p-2 border border-gray-300 rounded" />
                    </div>

                    <div>
                        <label for="edit-banner" class="block text-sm font-medium text-gray-700">Banner</label>
                        <img id="banner-preview" class="hidden w-full h-32 object-cover rounded mb-2" alt="banner preview" />
                        <input id="edit-banner" type="text" class="w-full p-2 border border-gray-300 rounded" />
                    </div>
                </div>

                <div class="space-y-4 sm:space-y-0 sm:grid sm:grid-cols-3 sm:gap-4">
                    <div>
                        <label for="edit-website" class="block text-sm font-medium text-gray-700">Website</label>
                        <input id="edit-website" type="text" class="w-full p-2 border border-gray-300 rounded" />
                    </div>

                    <div>
                        <label for="edit-nip05" class="block text-sm font-medium text-gray-700">NIP-05</label>
                        <input id="edit-nip05" type="text" class="w-full p-2 border border-gray-300 rounded" />
                    </div>

                    <div>
                        <label for="edit-lud16" class="block text-sm font-medium text-gray-700">lud16</label>
                        <input id="edit-lud16" type="text" class="w-full p-2 border border-gray-300 rounded" />
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">{{ __('Save') }}</button>
                </div>
            </form>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.initProfileEdit?.();
        });
    </script>
@endpush
