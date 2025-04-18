@extends('layouts.base')

@section('content')
    <section
        class="faq-section px-4 sm:px-6 lg:px-8 py-6"
        x-data="faqSection()"
    >
        {{-- Header --}}
        <header class="section-header mb-6">
            <div class="flex flex-col max-w-2xl">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">Frequently Asked Questions</h1>
                <p class="subtitle text-base sm:text-lg text-gray-700">Browse or search by topic to learn more.</p>
            </div>
        </header>

        {{-- Search & Filter --}}
        <div class="flex flex-col sm:flex-row gap-4 mb-6">
            <input
                type="text"
                placeholder="Search FAQs..."
                class="form-input w-full sm:w-2/3"
                x-model="search"
            >
            <select class="form-select sm:w-1/3" x-model="category">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ ucfirst(trim($cat)) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Answer Type Tabs --}}
        <div class="mb-6">
            <div class="flex gap-4 text-sm font-medium">
                <button
                    class="px-3 py-1 rounded border"
                    :class="answerLevel === 'tldr' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                    @click="answerLevel = 'tldr'"
                >TL;DR</button>
                <button
                    class="px-3 py-1 rounded border"
                    :class="answerLevel === 'beginner' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                    @click="answerLevel = 'beginner'"
                >Beginner</button>
                <button
                    class="px-3 py-1 rounded border"
                    :class="answerLevel === 'advance' ? 'bg-blue-600 text-white' : 'bg-white text-gray-700'"
                    @click="answerLevel = 'advance'"
                >Advanced</button>
            </div>
        </div>

        {{-- FAQ List --}}
        <div class="space-y-6">
            <template x-for="faq in filteredFaqs()" :key="faq.id">
                <div class="border rounded-lg p-4 shadow-sm">
                    <h2 class="text-lg font-semibold mb-2">
                        <span x-text="faq.question"></span>
                        <template x-if="faq.highlight">
                            <span class="ml-2 text-sm text-orange-600 font-bold uppercase">★ Highlighted</span>
                        </template>
                    </h2>

                    <p class="text-gray-700 mb-2" x-show="answerLevel === 'tldr'" x-text="faq.answer_tldr"></p>
                    <p class="text-gray-700 mb-2" x-show="answerLevel === 'beginner'" x-text="faq.answer_beginner"></p>
                    <p class="text-gray-700 mb-2" x-show="answerLevel === 'advance'" x-text="faq.answer_advance"></p>

                    <template x-if="faq.link">
                        <a :href="faq.link" target="_blank" class="text-sm text-blue-600 hover:underline inline-block mt-2">
                            Learn more
                        </a>
                    </template>
                </div>
            </template>

            <template x-if="filteredFaqs().length === 0">
                <p class="text-gray-500">No FAQs found.</p>
            </template>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('faqSection', () => ({
                search: '',
                category: '',
                answerLevel: 'beginner', // 'tldr' | 'beginner' | 'advance'
                faqs: @json($faqs),
                filteredFaqs() {
                    return this.faqs.filter(faq => {
                        const inCategory = this.category === '' || faq.categories.toLowerCase().includes(this.category.toLowerCase());
                        const matchSearch = faq.question.toLowerCase().includes(this.search.toLowerCase());
                        return inCategory && matchSearch;
                    });
                }
            }));
        });
    </script>
@endpush
