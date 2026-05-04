<script>
    (() => {
        try {
            const theme = localStorage.getItem('theme') || 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
        } catch (error) {}
    })();
</script>
