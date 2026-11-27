'use strict';
document.addEventListener('submit', event => {
    const message = event.target.dataset.confirm;
    if (message && !window.confirm(message)) event.preventDefault();
});

const rupiah = value => 'Rp' + Math.round(value).toLocaleString('id-ID');
const makeNode = (tag, text, className) => {
    const el = document.createElement(tag);
    if (text !== undefined) el.textContent = text;
    if (className) el.className = className;
    return el;
};

if (document.getElementById('pos-data')) {
    const data = JSON.parse(document.getElementById('pos-data').textContent);
    const products = data.products;
    const productFor = id => products.find(p => p.id === Number(id));
    const cart = (Array.isArray(data.items) ? data.items : Object.values(data.items)).map(item => ({product_id: Number(item.product_id), jumlah: Number(item.jumlah)})).filter(item => productFor(item.product_id));
    const total = () => cart.reduce((sum, item) => sum + item.jumlah * productFor(item.product_id).harga_jual, 0);
    const error = message => {
        const el = document.getElementById('cart-error');
        el.hidden = !message;
        el.textContent = message;
    };
    function payment() {
        const paid = Number(document.getElementById('bayar').value);
        const validCart = cart.length && cart.every(item => Number.isInteger(item.jumlah) && item.jumlah > 0 && item.jumlah <= productFor(item.product_id).stok);
        document.getElementById('cart-change').textContent = rupiah(Math.max(0, paid - total()));
        document.getElementById('checkout').disabled = !validCart || !Number.isInteger(paid) || paid < total();
    }
    function renderCart() {
        const lines = document.getElementById('cart-lines');
        const inputs = document.getElementById('cart-inputs');
        lines.replaceChildren(); inputs.replaceChildren();
        if (!cart.length) lines.append(makeNode('p', 'Keranjang masih kosong. Pilih produk untuk memulai.', 'empty'));
        cart.forEach((item, index) => {
            const product = productFor(item.product_id);
            const row = makeNode('div', undefined, 'cart-line');
            const top = makeNode('div', undefined, 'cart-line-top');
            top.append(makeNode('strong', product.nama), makeNode('strong', rupiah(item.jumlah * product.harga_jual)));
            row.append(top, makeNode('small', rupiah(product.harga_jual) + ' / ' + product.satuan));
            const controls = makeNode('div', undefined, 'qty-controls');
            const quantity = makeNode('input');
            Object.assign(quantity, {type: 'number', min: '1', max: String(product.stok), step: '1', value: item.jumlah});
            quantity.setAttribute('aria-label', 'Jumlah ' + product.nama);
            quantity.addEventListener('change', () => {
                const value = Number(quantity.value);
                if (!Number.isInteger(value) || value < 1 || value > product.stok) {
                    error('Jumlah harus bilangan bulat positif dan tidak melebihi stok tersedia.');
                    quantity.value = item.jumlah;
                    return;
                }
                item.jumlah = value; error(''); renderCart();
            });
            const remove = makeNode('button', 'Hapus'); remove.type = 'button';
            remove.addEventListener('click', () => { cart.splice(index, 1); error(''); renderCart(); });
            controls.append(quantity, makeNode('small', product.satuan), remove);
            row.append(controls); lines.append(row);
            for (const [key, value] of Object.entries(item)) {
                const hidden = makeNode('input');
                Object.assign(hidden, {type: 'hidden', name: `items[${index}][${key}]`, value});
                inputs.append(hidden);
            }
        });
        document.getElementById('cart-count').textContent = cart.length + ' produk';
        document.getElementById('cart-total').textContent = rupiah(total());
        payment();
    }
    function renderProducts() {
        const query = document.getElementById('product-search').value.toLocaleLowerCase('id');
        const filtered = products.filter(p => `${p.nama} ${p.kode} ${p.kategori}`.toLocaleLowerCase('id').includes(query));
        const grid = document.getElementById('product-grid'); grid.replaceChildren();
        document.getElementById('product-empty').hidden = filtered.length > 0;
        filtered.forEach(product => {
            const button = makeNode('button', undefined, 'product-card');
            button.type = 'button'; button.disabled = product.stok === 0;
            const photo = product.foto_url
                ? Object.assign(makeNode('img', undefined, 'product-photo'), {src: product.foto_url, alt: 'Foto ' + product.nama})
                : makeNode('span', '▧', 'product-symbol');
            button.append(photo, makeNode('small', product.kategori), makeNode('strong', product.nama), makeNode('small', product.kode + ' · ' + (product.stok ? `Stok ${product.stok} ${product.satuan}` : 'Stok habis')), makeNode('b', rupiah(product.harga_jual)));
            button.addEventListener('click', () => {
                const item = cart.find(row => row.product_id === product.id);
                if ((item?.jumlah || 0) + 1 > product.stok) { error('Stok ' + product.nama + ' tidak mencukupi.'); return; }
                if (item) item.jumlah++; else cart.push({product_id: product.id, jumlah: 1});
                error(''); renderCart();
            });
            grid.append(button);
        });
    }
    document.getElementById('product-search').addEventListener('input', renderProducts);
    document.getElementById('bayar').addEventListener('input', payment);
    document.getElementById('sale-form').addEventListener('submit', () => { document.getElementById('checkout').disabled = true; });
    renderProducts(); renderCart();
}

if (document.getElementById('incoming-form')) {
    const rows = document.getElementById('incoming-rows');
    const template = rows.firstElementChild.cloneNode(true);
    function updateIncoming() {
        let total = 0;
        Array.from(rows.children).forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/items\[[^\]]+\]/, `items[${index}]`); });
            const subtotal = Number(row.querySelector('[data-quantity]').value) * Number(row.querySelector('[data-price]').value);
            row.querySelector('[data-subtotal]').textContent = rupiah(subtotal);
            total += subtotal;
            row.querySelector('[data-remove-row]').disabled = rows.children.length === 1;
        });
        document.getElementById('incoming-total').textContent = rupiah(total);
    }
    document.getElementById('add-incoming-row').addEventListener('click', () => {
        if (rows.children.length >= 100) return;
        const row = template.cloneNode(true);
        row.querySelector('select').value = '';
        row.querySelector('[data-quantity]').value = 1;
        row.querySelector('[data-price]').value = 0;
        rows.append(row); updateIncoming();
    });
    rows.addEventListener('input', updateIncoming);
    rows.addEventListener('click', event => {
        if (event.target.matches('[data-remove-row]') && rows.children.length > 1) { event.target.closest('tr').remove(); updateIncoming(); }
    });
    updateIncoming();
}
