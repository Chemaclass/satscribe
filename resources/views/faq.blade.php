@extends('layouts.base')

@section('content')
    <section class="faq-section px-4 sm:px-6 lg:px-8 py-6" x-data="faqSection()">
        {{-- Header --}}
        <header class="section-header mb-6">
            <div class="flex flex-col">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">Frequently Asked Questions</h1>

                <p class="mt-3 text-base sm:text-lg subtitle leading-relaxed">
                    <strong>Satscribe</strong> is a tool designed to make the Bitcoin blockchain easier to understand—whether you're just getting started or you're knee-deep in block analysis. By entering a transaction ID or block height, you can instantly get an AI-generated explanation in plain English.
                </p>

                <p class="mt-3 text-base sm:text-lg subtitle leading-relaxed">
                    From technical summaries to beginner-friendly overviews, Satscribe bridges the gap between raw blockchain data and human understanding.
                </p>
                <p class="subtitle text-base sm:text-lg text-gray-700 dark:text-gray-400 mt-4">
                    Browse or search by topic to learn more.
                </p>
            </div>
        </header>

        {{-- Search & Filter --}}
        <div class="flex flex-col sm:flex-row gap-4 mb-1">
            <input type="text" placeholder="Search FAQs..." class="form-input w-full sm:w-2/3" x-model="search">
            <select class="form-select sm:w-1/3 cursor-pointer" x-model="category">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ ucfirst(trim($cat)) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Answer Type Tabs --}}
        <div class="flex mt-4 gap-2 mb-4 text-sm items-center">
            <span class="text-xs" title="Choose the level of detail in answers">Answer style:</span>
            @foreach ([
                'tldr' => ['icon' => 'scissors', 'label' => 'TL;DR'],
                'advance' => ['icon' => 'laptop', 'label' => 'Advanced'],
            ] as $level => $meta)
                <button
                    class="answer-level-btn font-medium"
                    :class="globalAnswerLevel === '{{ $level }}'
                        ? 'bg-orange-200 text-orange-900 border-orange-400 dark:bg-orange-200 dark:text-orange-900 dark:border-orange-100'
                        : 'bg-white border-gray-300 hover:bg-gray-100 dark:bg-transparent dark:text-gray-500 dark:border-gray-600 dark:hover:bg-gray-300'"
                    @click="setGlobalAnswerLevel('{{ $level }}')"
                >
                    <i data-lucide="{{ $meta['icon'] }}" class="w-4 h-4"></i>
                    {{ $meta['label'] }}
                </button>
            @endforeach
        </div>

        {{-- FAQ List --}}
        <div class="space-y-6">
            <template x-for="faq in filteredFaqs()" :key="faq.id">
                <div class="faq-card rounded-lg p-4 shadow-sm transition-colors duration-150 hover:bg-orange-50 dark:hover:bg-gray-800"
                     x-data="{
                answerLevel: 'advance',
                init() {
                    window.addEventListener('answer-level-change', (e) => {
                        this.answerLevel = e.detail;
                    });
                }
             }"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <span x-html="faq.highlighted_question"></span>
                            <template x-if="faq.highlight">
                                <span class="faq-highlight ml-2 block sm:inline">★ Highlight</span>
                            </template>
                        </h2>
                        <template x-if="faq.categories">
                            <div class="flex flex-wrap items-center gap-1 sm:text-right">
                                <template x-for="cat in faq.categories.split(',')">
                                    <button
                                        class="category-badge transition-colors duration-150 px-2 py-0.5 text-xs font-medium rounded bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-orange-200 dark:hover:bg-orange-300 flex items-center gap-1 cursor-pointer"
                                        :class="category === cat.trim() ? 'bg-orange-300 text-white dark:bg-orange-400' : ''"
                                        @click="category === cat.trim() ? category = '' : category = cat.trim()"
                                    >
                                        <template x-if="category === cat.trim()">
                                            <span class="ml-1 text-xs font-bold">✕</span>
                                        </template>
                                        <span x-text="cat.trim()"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Replace x-text with x-html for answers -->
                    <p class="faq-answer mb-2" x-show="answerLevel === 'tldr'" x-html="faq.highlighted_tldr"></p>
                    <p class="faq-answer mb-2" x-show="answerLevel === 'advance'" x-html="faq.highlighted_advance"></p>

                    <template x-if="faq.link">
                        <a :href="faq.link" target="_blank"
                           class="text-sm text-orange-600 hover:underline inline-block mt-2 dark:text-orange-400">
                            Learn more
                        </a>
                    </template>
                </div>
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
                globalAnswerLevel: 'advance',
                faqs: @json($faqs),
                setGlobalAnswerLevel(level) {
                    this.globalAnswerLevel = level;
                    window.dispatchEvent(new CustomEvent('answer-level-change', { detail: level }));
                },
                filteredFaqs() {
                    return this.faqs
                        .filter(faq => {
                            const inCategory = this.category === '' || faq.categories.toLowerCase().includes(this.category.toLowerCase());
                            const matchSearch = faq.question.toLowerCase().includes(this.search.toLowerCase()) ||
                                faq.answer_tldr.toLowerCase().includes(this.search.toLowerCase());
                            return inCategory && matchSearch;
                        })
                        .map(faq => ({
                            ...faq,
                            highlighted_question: this.highlightMatch(faq.question, this.search),
                            highlighted_tldr: this.highlightMatch(faq.answer_tldr, this.search),
                            highlighted_advance: this.highlightMatch(faq.answer_advance, this.search),
                        }));
                },
                highlightMatch(text, term) {
                    if (!term) return text;
                    const regex = new RegExp(`(${term})`, 'gi');
                    return text.replace(regex, '<mark>$1</mark>');
                },
            }));
        });
    </script>
@endpush
