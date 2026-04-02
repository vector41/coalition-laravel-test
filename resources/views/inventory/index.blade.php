@extends('layout.main')

@section('title', 'Product inventory')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
@endpush

@section('content')
    <div class="container py-4">
        <h1 class="h3 mb-4">Product inventory</h1>

        <div id="inventory-alert" class="alert d-none" role="alert"></div>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h2 class="h5 card-title">Add product</h2>
                <form id="product-form" class="row g-3" novalidate>
                    <div class="col-md-4">
                        <label for="product_name" class="form-label">Product name</label>
                        <input type="text" class="form-control" id="product_name" name="product_name" required
                            autocomplete="off">
                    </div>
                    <div class="col-md-4">
                        <label for="quantity" class="form-label">Quantity in stock</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="0" step="1"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label for="price_per_item" class="form-label">Price per item</label>
                        <input type="number" class="form-control" id="price_per_item" name="price_per_item" min="0"
                            step="0.01" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary" id="product-submit">Submit</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h5 card-title">Submitted products</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle mb-0" id="products-table">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">Product name</th>
                                <th scope="col">Quantity in stock</th>
                                <th scope="col">Price per item</th>
                                <th scope="col">Datetime submitted</th>
                                <th scope="col">Total value number</th>
                                <th scope="col" class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="products-tbody"></tbody>
                        <tfoot>
                            <tr class="table-secondary fw-semibold">
                                <td colspan="4" class="text-end">Sum of total values</td>
                                <td id="grand-total-cell">0.00</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script>
        (function () {
            const routes = {
                store: @json(route('inventory.store')),
                update: @json(url('/inventory/items')) + '/',
            };

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            let lastPayload = @json($initialPayload);

            const alertEl = document.getElementById('inventory-alert');
            const tbody = document.getElementById('products-tbody');
            const grandTotalCell = document.getElementById('grand-total-cell');
            const form = document.getElementById('product-form');

            function showAlert(type, message) {
                alertEl.className = 'alert alert-' + type;
                alertEl.textContent = message;
                alertEl.classList.remove('d-none');
            }

            function hideAlert() {
                alertEl.classList.add('d-none');
                alertEl.textContent = '';
            }

            function formatDateTime(iso) {
                if (!iso) return '';
                const d = new Date(iso);
                if (Number.isNaN(d.getTime())) return String(iso);
                return d.toLocaleString();
            }

            function formatMoney(n) {
                const x = Number(n);
                if (Number.isNaN(x)) return '0.00';
                return x.toFixed(2);
            }

            function requestJson(url, options = {}) {
                const headers = Object.assign({
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                }, options.headers || {});

                return fetch(url, Object.assign({}, options, { headers }))
                    .then(async (res) => {
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const err = new Error(data.message || 'Request failed');
                            err.status = res.status;
                            err.data = data;
                            throw err;
                        }
                        return data;
                    });
            }

            function renderTable(payload) {
                lastPayload = payload;
                tbody.replaceChildren();

                const items = payload.items || [];
                items.forEach((item) => {
                    const tr = document.createElement('tr');
                    tr.dataset.id = String(item.id);

                    const nameTd = document.createElement('td');
                    nameTd.className = 'cell-name';
                    nameTd.textContent = String(item.product_name ?? '');

                    const qtyTd = document.createElement('td');
                    qtyTd.className = 'cell-qty';
                    qtyTd.textContent = String(item.quantity ?? '');

                    const priceTd = document.createElement('td');
                    priceTd.className = 'cell-price';
                    priceTd.textContent = formatMoney(item.price_per_item);

                    const dtTd = document.createElement('td');
                    dtTd.className = 'cell-submitted';
                    dtTd.textContent = formatDateTime(String(item.submitted_at ?? ''));

                    const totalTd = document.createElement('td');
                    totalTd.className = 'cell-total';
                    totalTd.textContent = formatMoney(item.total_value);

                    const actionsTd = document.createElement('td');
                    actionsTd.className = 'text-end';
                    const editBtn = document.createElement('button');
                    editBtn.type = 'button';
                    editBtn.className = 'btn btn-sm btn-outline-secondary btn-edit';
                    editBtn.textContent = 'Edit';
                    actionsTd.appendChild(editBtn);

                    tr.append(nameTd, qtyTd, priceTd, dtTd, totalTd, actionsTd);
                    tbody.appendChild(tr);
                });

                grandTotalCell.textContent = formatMoney(payload.grand_total ?? 0);
            }

            function enterEditRow(tr) {
                const id = tr.dataset.id;
                const item = (lastPayload.items || []).find((i) => String(i.id) === id);
                if (!item) return;

                tr.querySelectorAll('.btn-edit').forEach((b) => b.remove());

                const nameTd = tr.querySelector('.cell-name');
                const qtyTd = tr.querySelector('.cell-qty');
                const priceTd = tr.querySelector('.cell-price');
                const totalTd = tr.querySelector('.cell-total');
                const actionsTd = tr.querySelector('td:last-child');

                nameTd.textContent = '';
                qtyTd.textContent = '';
                priceTd.textContent = '';
                totalTd.textContent = '';

                const nameInput = document.createElement('input');
                nameInput.type = 'text';
                nameInput.className = 'form-control form-control-sm';
                nameInput.value = String(item.product_name ?? '');
                nameTd.appendChild(nameInput);

                const qtyInput = document.createElement('input');
                qtyInput.type = 'number';
                qtyInput.min = '0';
                qtyInput.step = '1';
                qtyInput.className = 'form-control form-control-sm';
                qtyInput.value = String(item.quantity ?? '');
                qtyTd.appendChild(qtyInput);

                const priceInput = document.createElement('input');
                priceInput.type = 'number';
                priceInput.min = '0';
                priceInput.step = '0.01';
                priceInput.className = 'form-control form-control-sm';
                priceInput.value = String(item.price_per_item ?? '');
                priceTd.appendChild(priceInput);

                totalTd.textContent = formatMoney(item.total_value);

                const saveBtn = document.createElement('button');
                saveBtn.type = 'button';
                saveBtn.className = 'btn btn-sm btn-primary me-1';
                saveBtn.textContent = 'Save';
                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'btn btn-sm btn-outline-secondary';
                cancelBtn.textContent = 'Cancel';
                actionsTd.replaceChildren(saveBtn, cancelBtn);

                saveBtn.addEventListener('click', () => {
                    hideAlert();
                    const body = {
                        product_name: nameInput.value,
                        quantity: parseInt(qtyInput.value, 10),
                        price_per_item: parseFloat(priceInput.value),
                    };
                    requestJson(routes.update + encodeURIComponent(id), {
                        method: 'PUT',
                        body: JSON.stringify(body),
                    }).then((data) => {
                        renderTable(data);
                    }).catch((err) => {
                        if (err.status === 422 && err.data && err.data.errors) {
                            const msgs = Object.values(err.data.errors).flat().join(' ');
                            showAlert('danger', msgs);
                        } else {
                            showAlert('danger', err.message || 'Could not save.');
                        }
                    });
                });

                cancelBtn.addEventListener('click', () => {
                    hideAlert();
                    renderTable(lastPayload);
                });
            }

            tbody.addEventListener('click', (e) => {
                const btn = e.target.closest('.btn-edit');
                if (!btn) return;
                const tr = btn.closest('tr');
                if (!tr || !tr.dataset.id) return;
                enterEditRow(tr);
            });

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                hideAlert();

                const body = {
                    product_name: document.getElementById('product_name').value,
                    quantity: parseInt(document.getElementById('quantity').value, 10),
                    price_per_item: parseFloat(document.getElementById('price_per_item').value),
                };

                const submitBtn = document.getElementById('product-submit');
                submitBtn.disabled = true;

                requestJson(routes.store, {
                    method: 'POST',
                    body: JSON.stringify(body),
                }).then((data) => {
                    form.reset();
                    renderTable(data);
                }).catch((err) => {
                    if (err.status === 422 && err.data && err.data.errors) {
                        const msgs = Object.values(err.data.errors).flat().join(' ');
                        showAlert('danger', msgs);
                    } else {
                        showAlert('danger', err.message || 'Could not save product.');
                    }
                }).finally(() => {
                    submitBtn.disabled = false;
                });
            });

            renderTable(lastPayload);
        })();
    </script>
@endpush
