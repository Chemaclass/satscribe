<div
    x-data="feedbackModal()"
    x-init="init()"
    x-show="show"
    @keydown.escape.window="closeModal"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 backdrop-blur-sm"
    style="display: none;"
    x-cloak
>
    <div
        class="relative w-full max-w-md p-6 rounded-2xl shadow-2xl bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-700"
        @click.away="closeModal"
        x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200 transform"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <button type="button" class="absolute top-2 right-2" @click="closeModal">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
        <h3 class="text-xl font-bold mb-4">{{ __('Send Feedback') }}</h3>
        <p x-show="thankYou" x-text="thankYou" class="mb-4 text-center text-green-600" x-cloak></p>
        <form x-ref="form" @submit.prevent="submit">
            @csrf
            <div class="mb-4">
                <label for="fb_nickname" class="block text-sm font-medium mb-1">{{ __('Nickname') }}</label>
                <input type="text" id="fb_nickname" name="nickname" required class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label for="fb_email" class="block text-sm font-medium mb-1">{{ __('Email (optional)') }}</label>
                <input type="email" id="fb_email" name="email" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label for="fb_message" class="block text-sm font-medium mb-1">{{ __('Message') }}</label>
                <textarea id="fb_message" name="message" required class="w-full border rounded px-3 py-2"></textarea>
            </div>
            <div class="mb-4" x-data="captcha()" x-init="generate()">
                <label class="block text-sm font-medium mb-1">{{ __('Prove you are human') }}</label>
                <div class="flex items-center gap-2 mt-1">
                    <span x-text="question"></span>
                    <input type="number" x-model="answer" name="captcha_answer" class="border rounded px-2 w-20" required>
                </div>
                <input type="hidden" name="captcha_sum" :value="sum">
            </div>
            <div class="text-right">
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded">{{ __('Send Feedback') }}</button>
            </div>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
function feedbackModal() {
    return {
        show: false,
        thankYou: '',
        init() {
            window.addEventListener('open-feedback', () => {
                this.show = true;
                document.body.classList.add('modal-open');
            });
        },
        submit() {
            const form = this.$refs.form;
            axios.post('{{ route('feedback.store') }}', new FormData(form))
                .then(() => {
                    confetti({ particleCount: 100, spread: 70, origin: { y: 0.6 } });
                    form.reset();
                    this.thankYou = '{{ __('Thanks for your feedback!') }}';
                    setTimeout(() => {
                        this.closeModal();
                        this.thankYou = '';
                    }, 2000);
                })
                .catch(() => {});
        },
        closeModal() {
            this.show = false;
            document.body.classList.remove('modal-open');
        }
    };
}
function captcha() {
    return {
        a: 0,
        b: 0,
        answer: '',
        get sum() { return this.a + this.b; },
        get question() { return `${this.a} + ${this.b} = ?`; },
        generate() {
            this.a = Math.floor(Math.random() * 10) + 1;
            this.b = Math.floor(Math.random() * 10) + 1;
        }
    };
}
</script>
