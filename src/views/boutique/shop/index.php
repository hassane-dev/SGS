<?php require_once __DIR__ . '/../../layouts/header_able.php'; ?>

<div class="pc-container">
    <div class="pc-content">
        <div class="page-header">
            <div class="page-block">
                <div class="row align-items-center">
                    <div class="col-md-12">
                        <ul class="breadcrumb">
                            <li class="breadcrumb-item"><a href="/"><?= _('Tableau de Bord') ?></a></li>
                            <li class="breadcrumb-item"><a href="/eleves"><?= _('Élèves') ?></a></li>
                            <li class="breadcrumb-item" aria-current="page"><?= _('Boutique Scolaire') ?></li>
                        </ul>
                    </div>
                    <div class="col-md-12">
                        <div class="page-header-title">
                            <h2 class="mb-0"><?= _('Boutique Scolaire') ?></h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Products Grid -->
            <div class="col-lg-8 col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1 me-3">
                            <input type="text" id="shop-search" class="form-control" placeholder="<?= _('Rechercher un produit...') ?>">
                        </div>
                        <div class="d-flex gap-2">
                            <select id="shop-category" class="form-select">
                                <option value=""><?= _('Toutes les catégories') ?></option>
                                <?php
                                $categories = array_unique(array_filter(array_column($articles, 'categorie')));
                                foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="shop-availability" class="form-select">
                                <option value=""><?= _('Tous les stocks') ?></option>
                                <option value="1"><?= _('En stock') ?></option>
                                <option value="0"><?= _('Rupture') ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="products-grid">
                            <?php foreach ($articles as $article): ?>
                                <div class="col-xl-4 col-sm-6 product-card"
                                     data-name="<?= strtolower(htmlspecialchars($article['nom_article'])) ?>"
                                     data-category="<?= strtolower(htmlspecialchars($article['categorie'] ?? '')) ?>"
                                     data-instock="<?= $article['stock'] > 0 ? '1' : '0' ?>">
                                    <div class="card product-card shadow-none border">
                                        <div class="card-img-top position-relative overflow-hidden" style="height: 180px;">
                                            <?php if (!empty($article['image'])): ?>
                                                <img src="<?= htmlspecialchars($article['image']) ?>" alt="" class="img-fluid w-100 h-100 object-fit-cover">
                                            <?php else: ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center h-100">
                                                    <i class="ph-duotone ph-image text-muted" style="font-size: 3rem;"></i>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($article['stock'] <= 0): ?>
                                                <span class="badge bg-danger position-absolute top-0 end-0 m-2"><?= _('Rupture') ?></span>
                                            <?php elseif ($article['stock'] <= 5): ?>
                                                <span class="badge bg-warning position-absolute top-0 end-0 m-2"><?= _('Stock faible') ?>: <?= $article['stock'] ?></span>
                                            <?php endif; ?>

                                            <?php if (!empty($article['ancien_prix']) && $article['ancien_prix'] > $article['prix']): ?>
                                                <span class="badge bg-success position-absolute top-0 start-0 m-2"><?= _('PROMO') ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body p-3">
                                            <h6 class="mb-1 text-truncate"><?= htmlspecialchars($article['nom_article']) ?></h6>
                                            <p class="text-muted small mb-2"><?= htmlspecialchars($article['categorie'] ?? _('Divers')) ?></p>

                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <span class="h5 mb-0"><?= number_format($article['prix'], 0, ',', ' ') ?> FCFA</span>
                                                    <?php if (!empty($article['ancien_prix']) && $article['ancien_prix'] > $article['prix']): ?>
                                                        <br><small class="text-muted text-decoration-line-through"><?= number_format($article['ancien_prix'], 0, ',', ' ') ?> FCFA</small>
                                                    <?php endif; ?>
                                                </div>
                                                <button class="btn btn-primary btn-sm add-to-cart"
                                                        data-id="<?= $article['id_article'] ?>"
                                                        data-name="<?= htmlspecialchars($article['nom_article']) ?>"
                                                        data-price="<?= $article['prix'] ?>"
                                                        data-stock="<?= $article['stock'] ?>"
                                                        <?= ($article['stock'] <= 0) ? 'disabled' : '' ?>>
                                                    <i class="ph-duotone ph-shopping-cart"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cart Sidebar -->
            <div class="col-lg-4 col-md-12">
                <div class="card sticky-top" style="top: 100px;">
                    <div class="card-header">
                        <h5><?= _('Panier de') ?>: <?= htmlspecialchars($eleve['prenom'] . ' ' . $eleve['nom']) ?></h5>
                    </div>
                    <div class="card-body p-0">
                        <div id="cart-items" class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                            <!-- Items populated by JS -->
                            <div class="text-center p-4 empty-cart-msg">
                                <i class="ph-duotone ph-shopping-cart-simple text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-2 text-muted"><?= _('Votre panier est vide') ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"><?= _('Total') ?></h5>
                            <h5 class="mb-0 text-primary" id="cart-total">0 FCFA</h5>
                        </div>
                        <form action="/boutique/achats/store" method="POST" id="checkout-form">
                            <input type="hidden" name="eleve_id" value="<?= $eleve['id_eleve'] ?>">
                            <input type="hidden" name="cart_data" id="cart-data-input">
                            <button type="submit" class="btn btn-primary w-100 py-2" id="btn-checkout" disabled>
                                <i class="ph-duotone ph-check-circle me-2"></i><?= _('Valider l\'achat') ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cart = [];
    const cartContainer = document.getElementById('cart-items');
    const totalDisplay = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('btn-checkout');
    const cartInput = document.getElementById('cart-data-input');
    const searchInput = document.getElementById('shop-search');
    const categorySelect = document.getElementById('shop-category');
    const availabilitySelect = document.getElementById('shop-availability');
    const productCards = document.querySelectorAll('.product-card');

    // Search and Filter
    function filterProducts() {
        const searchTerm = searchInput.value.toLowerCase();
        const selectedCategory = categorySelect.value.toLowerCase();
        const selectedAvailability = availabilitySelect.value;

        productCards.forEach(card => {
            const name = card.dataset.name || '';
            const category = card.dataset.category || '';
            const inStock = card.dataset.instock;

            const matchesSearch = name.includes(searchTerm);
            const matchesCategory = selectedCategory === '' || category === selectedCategory;
            const matchesAvailability = selectedAvailability === '' || inStock === selectedAvailability;

            if (matchesSearch && matchesCategory && matchesAvailability) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterProducts);
    categorySelect.addEventListener('change', filterProducts);
    availabilitySelect.addEventListener('change', filterProducts);

    // Cart Logic
    function updateCartUI() {
        if (cart.length === 0) {
            cartContainer.innerHTML = `
                <div class="text-center p-4 empty-cart-msg">
                    <i class="ph-duotone ph-shopping-cart-simple text-muted" style="font-size: 3rem;"></i>
                    <p class="mt-2 text-muted"><?= _('Votre panier est vide') ?></p>
                </div>`;
            totalDisplay.innerText = '0 FCFA';
            checkoutBtn.disabled = true;
            return;
        }

        cartContainer.innerHTML = '';
        let total = 0;

        cart.forEach((item, index) => {
            total += item.price * item.quantity;
            const itemHtml = `
                <div class="list-group-item p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-0">${item.name}</h6>
                            <small class="text-muted">${new Intl.NumberFormat().format(item.price)} FCFA x ${item.quantity}</small>
                        </div>
                        <div class="text-end">
                            <h6 class="mb-0">${new Intl.NumberFormat().format(item.price * item.quantity)} FCFA</h6>
                            <div class="btn-group btn-group-sm mt-1">
                                <button class="btn btn-outline-secondary px-1 py-0 btn-minus" data-index="${index}"><i class="ph-duotone ph-minus"></i></button>
                                <button class="btn btn-outline-secondary px-1 py-0 btn-plus" data-index="${index}"><i class="ph-duotone ph-plus"></i></button>
                                <button class="btn btn-outline-danger px-1 py-0 btn-remove" data-index="${index}"><i class="ph-duotone ph-trash"></i></button>
                            </div>
                        </div>
                    </div>
                </div>`;
            cartContainer.insertAdjacentHTML('beforeend', itemHtml);
        });

        totalDisplay.innerText = new Intl.NumberFormat().format(total) + ' FCFA';
        checkoutBtn.disabled = false;
        cartInput.value = JSON.stringify(cart);
    }

    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const name = this.dataset.name;
            const price = parseFloat(this.dataset.price);
            const stock = parseInt(this.dataset.stock);

            const existingItem = cart.find(item => item.id === id);
            if (existingItem) {
                if (existingItem.quantity < stock) {
                    existingItem.quantity++;
                } else {
                    alert('<?= _('Stock insuffisant') ?>');
                }
            } else {
                cart.push({ id, name, price, quantity: 1, stock });
            }
            updateCartUI();
        });
    });

    cartContainer.addEventListener('click', function(e) {
        const btn = e.target.closest('button');
        if (!btn) return;

        const index = btn.dataset.index;
        if (btn.classList.contains('btn-plus')) {
            if (cart[index].quantity < cart[index].stock) {
                cart[index].quantity++;
            } else {
                alert('<?= _('Stock insuffisant') ?>');
            }
        } else if (btn.classList.contains('btn-minus')) {
            if (cart[index].quantity > 1) {
                cart[index].quantity--;
            } else {
                cart.splice(index, 1);
            }
        } else if (btn.classList.contains('btn-remove')) {
            cart.splice(index, 1);
        }
        updateCartUI();
    });
});
</script>

<style>
.object-fit-cover {
    object-fit: cover;
}
.product-card:hover {
    transform: translateY(-5px);
    transition: transform 0.3s ease;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
</style>

<?php require_once __DIR__ . '/../../layouts/footer_able.php'; ?>
