// Status option click handler
document.querySelectorAll('.status-option').forEach(opt => {
    opt.addEventListener('click', () => {
        document.querySelectorAll('.status-option').forEach(o => o.classList.remove('active'));
        opt.classList.add('active');
        opt.querySelector('input[type="radio"]').checked = true;
    });
});