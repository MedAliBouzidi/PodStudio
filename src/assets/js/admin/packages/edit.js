const fields = {
    name: document.querySelector('[name="name"]'),
    price: document.querySelector('[name="price"]'),
    hours: document.querySelector('[name="duration_hours"]'),
    desc: document.querySelector('[name="description"]'),
    eq: document.getElementById('includes_equipment'),
};

function updatePreview() {
    document.getElementById('prev-name').textContent = fields.name.value || 'Package Name';
    document.getElementById('prev-price').textContent = parseFloat(fields.price.value || 0).toFixed(2);
    document.getElementById('prev-hours').textContent = fields.hours.value || '0';
    document.getElementById('prev-desc').textContent = fields.desc.value || 'Description will appear here...';
    document.getElementById('prev-eq').textContent = fields.eq.checked ? '✅ Equipment included' : '❌ No equipment';
}

Object.values(fields).forEach(f => f?.addEventListener('input', updatePreview));
fields.eq?.addEventListener('change', updatePreview);