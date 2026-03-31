<?php
// Shared product form fields — included in both Add and Edit modals
// $categories must be in scope
?>
<div class="row g-3">
    <div class="col-12">
        <label class="form-label text-white-50 small fw-semibold">Product Name *</label>
        <input type="text" name="name" class="form-control-dark" placeholder="e.g. NeoPulse X2" required>
    </div>
    <div class="col-12">
        <label class="form-label text-white-50 small fw-semibold">Description</label>
        <textarea name="description" class="form-control-dark" placeholder="Short product description…" rows="3"></textarea>
    </div>
    <div class="col-sm-4">
        <label class="form-label text-white-50 small fw-semibold">Price ($) *</label>
        <input type="number" name="price" class="form-control-dark" placeholder="0.00" min="0" step="0.01" required>
    </div>
    <div class="col-sm-4">
        <label class="form-label text-white-50 small fw-semibold">Sale Price ($)</label>
        <input type="number" name="sale_price" class="form-control-dark" placeholder="Leave blank if none" min="0" step="0.01">
    </div>
    <div class="col-sm-4">
        <label class="form-label text-white-50 small fw-semibold">Stock</label>
        <input type="number" name="stock" class="form-control-dark" placeholder="0" min="0" value="0">
    </div>
    <div class="col-sm-6">
        <label class="form-label text-white-50 small fw-semibold">Category</label>
        <select name="category_id" class="form-control-dark" style="cursor:pointer;">
            <option value="">— None —</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>"><?= h($cat['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-sm-6 d-flex align-items-end">
        <label class="d-flex align-items-center gap-2 cursor-pointer" style="cursor:pointer;">
            <input type="checkbox" name="featured" class="form-check-input" style="width:18px;height:18px;">
            <span class="text-white-50 small fw-semibold">Mark as Featured</span>
        </label>
    </div>
</div>
