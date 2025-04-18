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
            <select class="form-select sm:w-1/3 cursor-pointer" x-model="category">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ ucfirst(trim($cat)) }}</option>
                @endforeach
            </select>
        </div>

        {{-- Global Answer Type Tabs --}}
        <div class="flex gap-2 mb-4 text-sm items-center">
            <span class="text-gray-500 text-xs">Answer style:</span>
            <button
                class="px-2.5 py-0.5 rounded-full text-xs border transition-colors duration-150 cursor-pointer"
                :class="globalAnswerLevel === 'tldr'
            ? 'bg-orange-100 text-orange-800 border-orange-300'
            : 'bg-transparent text-gray-600 border-gray-300 hover:bg-gray-100'"
                @click="setGlobalAnswerLevel('tldr')"
            ><i class="fas fa-scissors text-[11px]"></i> TL;DR</button>

            <button
                class="px-2.5 py-0.5 rounded-full text-xs border transition-colors duration-150 cursor-pointer"
                :class="globalAnswerLevel === 'beginner'
            ? 'bg-orange-100 text-orange-800 border-orange-300'
            : 'bg-transparent text-gray-600 border-gray-300 hover:bg-gray-100'"
                @click="setGlobalAnswerLevel('beginner')"
            ><i class="fas fa-book text-[11px]"></i> Beginner</button>

            <button
                class="px-2.5 py-0.5 rounded-full text-xs border transition-colors duration-150 cursor-pointer"
                :class="globalAnswerLevel === 'advance'
            ? 'bg-orange-100 text-orange-800 border-orange-300'
            : 'bg-transparent text-gray-600 border-gray-300 hover:bg-gray-100'"
                @click="setGlobalAnswerLevel('advance')"
            ><i class="fas fa-laptop-code text-[11px]"></i> Advanced</button>
        </div>

        {{-- FAQ List --}}
        <div class="space-y-6">
            <template x-for="faq in filteredFaqs()" :key="faq.id">
                <div class="rounded-lg p-4 shadow-sm transition-colors duration-150 hover:bg-orange-50"
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
                        <h2 class="text-lg font-semibold">
                            <span x-text="faq.question"></span>
                            <template x-if="faq.highlight">
                                <span class="block sm:inline text-sm text-orange-600 font-bold uppercase mt-1 sm:mt-0">★ Highlight</span>
                            </template>
                        </h2>

                        <template x-if="faq.categories">
                            <div class="flex flex-wrap items-center gap-1 text-sm text-gray-500 sm:text-right">
                                <template x-for="cat in faq.categories.split(',')">
                                    <span class="inline-block bg-gray-100 text-gray-700 px-2 py-0.5 rounded mr-1 mb-1">
                                        <span x-text="cat.trim()"></span>
                                    </span>
                                </template>
                                <div class="relative ml-1">
                                    <!-- Desktop dropdown using Alpine -->
                                    <div x-data="{ open: false }" class="hidden sm:block relative">
                                        <button @click="open = !open"
                                                class="text-xs font-semibold rounded-full px-2 py-0.5 shadow-sm flex items-center gap-1 cursor-pointer">
                                            <template x-if="answerLevel === 'tldr'"><span><i class="fas fa-scissors text-[11px]"></i></span></template>
                                            <template x-if="answerLevel === 'beginner'"><span><i class="fas fa-book text-[11px]"></i></span></template>
                                            <template x-if="answerLevel === 'advance'"><span><i class="fas fa-laptop-code text-[11px]"></i></span></template>
                                        </button>
                                        <div x-show="open" class="absolute right-0 z-10 mt-2 w-36 bg-white border border-gray-200 rounded-md shadow-lg text-sm text-gray-700" @click.away="open = false">
                                            <ul class="py-1">
                                                <li class="px-3 py-1.5 hover:bg-orange-50 cursor-pointer flex items-center gap-2" @click="answerLevel = 'tldr'; open = false"><i class="fas fa-scissors text-[11px]"></i> <span>TL;DR</span></li>
                                                <li class="px-3 py-1.5 hover:bg-orange-50 cursor-pointer flex items-center gap-2" @click="answerLevel = 'beginner'; open = false"><i class="fas fa-book text-[11px]"></i> <span>Beginner</span></li>
                                                <li class="px-3 py-1.5 hover:bg-orange-50 cursor-pointer flex items-center gap-2" @click="answerLevel = 'advance'; open = false"><i class="fas fa-laptop-code text-[11px]"></i> <span>Advanced</span></li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Mobile dropdown -->
                                    <div class="block sm:hidden">
                                        <select x-model="answerLevel"
                                                class="text-xs font-semibold rounded-full px-2 py-0.5 shadow-sm cursor-pointer">
                                            <option value="tldr">✂️ TL;DR</option>
                                            <option value="beginner">📖 Beginner</option>
                                            <option value="advance">💻 Advanced</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </template>
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
