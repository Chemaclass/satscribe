{{-- resources/views/faq/index.blade.php --}}
@extends('layouts.base')

@section('content')
    <section class="faq-section px-4 sm:px-6 lg:px-8 py-6" x-data="faqSection()">
        {{-- Header --}}
        <header class="section-header mb-6">
            <div class="flex flex-col max-w-2xl">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">Frequently Asked Questions</h1>
                <p class="subtitle text-base sm:text-lg text-gray-700 dark:text-gray-400">
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
            <span class="text-gray-500 text-xs dark:text-gray-400">Answer style:</span>
            @foreach ([
                'tldr' => ['icon' => 'scissors', 'label' => 'TL;DR'],
                'beginner' => ['icon' => 'book', 'label' => 'Beginner'],
                'advance' => ['icon' => 'laptop', 'label' => 'Advanced'],
            ] as $level => $meta)
                <button
                    class="answer-level-btn"
                    :class="globalAnswerLevel === '{{ $level }}'
                    ? 'bg-orange-100 text-orange-800 border-orange-300 dark:bg-orange-200 dark:text-orange-900 dark:border-orange-100'
                    : 'bg-transparent text-gray-600 border-gray-300 hover:bg-gray-100 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700'"
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
                answerLevel: 'beginner',
                init() {
                    window.addEventListener('answer-level-change', (e) => {
                        this.answerLevel = e.detail;
                    });
                }
             }"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-2">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <span x-text="faq.question"></span>
                            <template x-if="faq.highlight">
                                <span class="faq-highlight ml-2 block sm:inline">★ Highlight</span>
                            </template>
                        </h2>
                        <template x-if="faq.categories">
                            <div class="flex flex-wrap items-center gap-1 sm:text-right">
                                <template x-for="cat in faq.categories.split(',')">
                                    <span class="category-badge">
                                        <span x-text="cat.trim()"></span>
                                    </span>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Answers -->
                    <p class="faq-answer mb-2" x-show="answerLevel === 'tldr'" x-text="faq.answer_tldr"></p>
                    <p class="faq-answer mb-2" x-show="answerLevel === 'beginner'" x-text="faq.answer_beginner"></p>
                    <p class="faq-answer mb-2" x-show="answerLevel === 'advance'" x-text="faq.answer_advance"></p>

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
                globalAnswerLevel: 'beginner',
                faqs: @json($faqs),
                setGlobalAnswerLevel(level) {
                    this.globalAnswerLevel = level;
                    window.dispatchEvent(new CustomEvent('answer-level-change', { detail: level }));
                },
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
