export default function themeSwitcher() {
    return {
        dark: false,
        init() {
            const savedTheme = localStorage.getItem('theme');
            this.dark = savedTheme === 'dark';
            document.documentElement.classList.toggle('dark', this.dark);
            this.$watch('dark', val => {
                localStorage.setItem('theme', val ? 'dark' : 'light');
                document.documentElement.classList.toggle('dark', val);
            });
        },
        toggle() {
            this.dark = !this.dark;
        }
    };
}
