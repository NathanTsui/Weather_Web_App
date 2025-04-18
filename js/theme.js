// Theme management
const themeToggle = document.getElementById('themeToggle');
const html = document.documentElement;

// Check for saved theme preference
const savedTheme = localStorage.getItem('theme');
if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    html.classList.add('dark');
}

// Theme toggle functionality
themeToggle.addEventListener('click', () => {
    html.classList.toggle('dark');
    localStorage.setItem('theme', html.classList.contains('dark') ? 'dark' : 'light');
}); 