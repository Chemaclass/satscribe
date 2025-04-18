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
        <div class="flex flex-col sm:flex-row gap-4 mb-1">
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

        {{-- FAQ List --}}
        <div class="space-y-6">
            <template x-for="faq in filteredFaqs()" :key="faq.id">
                <div class="rounded-lg p-4 shadow-sm" x-data="{ answerLevel: 'beginner' }">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                        <h2 class="text-lg font-semibold">
                            <span x-text="faq.question"></span>
                            <template x-if="faq.highlight">
                                <span class="ml-2 text-sm text-orange-600 font-bold uppercase">★ Highlight</span>
                            </template>
                        </h2>
                        <template x-if="faq.categories">
                            <div class="text-sm text-gray-500 sm:text-right">
                                <template x-for="cat in faq.categories.split(',')">
                        <span class="inline-block bg-gray-100 text-gray-700 px-2 py-0.5 rounded mr-1 mb-1">
                            <span x-text="cat.trim()"></span>
                        </span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Local Answer Type Tabs -->
                    <div class="flex gap-2 mb-2">
                        <button
                            class="px-2.5 py-0.5 rounded-full text-xs border transition-colors duration-150"
                            :class="answerLevel === 'tldr'
            ? 'bg-orange-100 text-orange-800 border-orange-300'
            : 'bg-transparent text-gray-600 border-gray-300 hover:bg-gray-100'"
                            @click="answerLevel = 'tldr'"
                        >TL;DR</button>

                        <button
                            class="px-2.5 py-0.5 rounded-full text-xs border transition-colors duration-150"
                            :class="answerLevel === 'beginner'
            ? 'bg-orange-100 text-orange-800 border-orange-300'
            : 'bg-transparent text-gray-600 border-gray-300 hover:bg-gray-100'"
                            @click="answerLevel = 'beginner'"
                        >Beginner</button>

                        <button
                            class="px-2.5 py-0.5 rounded-full text-xs border transition-colors duration-150"
                            :class="answerLevel === 'advance'
            ? 'bg-orange-100 text-orange-800 border-orange-300'
            : 'bg-transparent text-gray-600 border-gray-300 hover:bg-gray-100'"
                            @click="answerLevel = 'advance'"
                        >Advanced</button>
                    </div>

                    <!-- Answers -->
                    <p class="text-gray-700 mb-2" x-show="answerLevel === 'tldr'" x-text="faq.answer_tldr"></p>
                    <p class="text-gray-700 mb-2" x-show="answerLevel === 'beginner'" x-text="faq.answer_beginner"></p>
                    <p class="text-gray-700 mb-2" x-show="answerLevel === 'advance'" x-text="faq.answer_advance"></p>

                    <template x-if="faq.link">
                        <a :href="faq.link" target="_blank" class="text-sm text-orange-600 hover:underline inline-block mt-2">
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
