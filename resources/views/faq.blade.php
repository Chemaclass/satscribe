@extends('layouts.base')

@section('content')
    <section
        class="faq-section px-4 sm:px-6 lg:px-8 py-6"
        x-data="faqSection()"
    >
        {{-- Header Section --}}
        <header class="section-header mb-6">
            <div class="flex flex-col max-w-2xl">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">
                    Frequently Asked Questions
                </h1>
                <p class="subtitle text-base sm:text-lg text-gray-700">
                    Browse or search by topic to learn more.
                </p>
            </div>
        </header>

        {{-- Search and Filters --}}
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

        {{-- FAQ List --}}
        <div class="space-y-6">
            <template x-for="faq in filteredFaqs()" :key="faq.id">
                <div class="border rounded-lg p-4 shadow-sm">
                    <h2 class="text-lg font-semibold mb-1">
                        <span x-text="faq.question"></span>
                        <template x-if="faq.highlight">
                            <span class="ml-2 text-sm text-orange-600 font-bold uppercase">★ Highlighted</span>
                        </template>
                        <template x-if="faq.link">
                            <a :href="faq.link" target="_blank" class="text-sm text-blue-600 hover:underline mt-2 inline-block">
                                Learn more
                            </a>
                        </template>
                    </h2>
                    <p class="text-gray-700" x-text="faq.answer"></p>
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
                        const textMatch = this.search === ''
                            || faq.question.toLowerCase().includes(this.search.toLowerCase())
                            || faq.answer.toLowerCase().includes(this.search.toLowerCase());
                        return inCategory && textMatch;
                    });
                }
            }));
        });
    </script>
@endpush
