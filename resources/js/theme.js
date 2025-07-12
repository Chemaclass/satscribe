export default function themeSwitcher() {
    return {
        dark: false,
        init() {
            const savedTheme = localStorage.getItem('theme');
            this.dark = savedTheme === 'dark'; // default to light theme when not set
            this.apply();
            this.$watch('dark', () => this.apply());
        },
        apply() {
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
            document.documentElement.classList.toggle('dark', this.dark);
            document.documentElement.style.colorScheme = this.dark ? 'dark' : 'light';
        },
        toggle() {
            this.dark = !this.dark;
        }
    };
}
