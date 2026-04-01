const Cart = {
    get(){
        return JSON.parse(localStorage.getItem('cart') || '[]');
    },
    save(cart){
        localStorage.setItem('cart', JSON.stringify(cart));
    },
    add(item){
        const cart = Cart.get();
        const existing = cart.find(i => i.id === item.id);
        if(existing){
            existing.qty += item.qty;
        }else{
            cart.push(item);
        }
        Cart.save(cart);
    },
    remove(id){
        Cart.save(Cart.get().filter(i => i.id !== id));
    },
    updateQty(id, qty){
        const cart = Cart.get();
        const item = cart.find(i => i.id === id);
        if(item){
            item.qty = qty;
        } 
        Cart.save(cart);
    },
};

document.addEventListener('DOMContentLoaded', () =>{
    const container = document.getElementById('cartItems');
    if(!container){
        return;
    }
    function renderCart(){
        const cart = Cart.get();
        const footer = document.getElementById('cartFooter');
        const empty = document.getElementById('cartEmpty');

        if (cart.length === 0) {
            container.innerHTML = '';
            footer.style.display = 'none';
            empty.style.display = 'block';
            return;
        }

        empty.style.display = 'none';
        footer.style.display = 'block';

        container.innerHTML = cart.map(item => `
            <div class="d-flex align-items-center gap-3 border-bottom py-3">
                <img src="${item.image}" style="width:70px;height:70px;object-fit:cover;border-radius:8px;">
                <div class="flex-grow-1">
                    <div class="fw-semibold">${item.name}</div>
                    <div class="text-muted small">$${item.price.toFixed(2)} each</div>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <button class="btn btn-sm btn-outline-secondary px-2 py-0" onclick="changeQty('${item.id}', ${item.qty - 1})">−</button>
                        <span>${item.qty}</span>
                        <button class="btn btn-sm btn-outline-secondary px-2 py-0" onclick="changeQty('${item.id}', ${item.qty + 1})">+</button>
                    </div>
                </div>
                <div class="text-end">
                    <div class="fw-bold">$${(item.price * item.qty).toFixed(2)}</div>
                    <button class="btn btn-sm btn-link text-danger p-0 mt-1" onclick="removeItem('${item.id}')">Remove</button>
                </div>
            </div>
        `).join('');

        const total = cart.reduce((sum, i) => sum + i.price * i.qty, 0);
        document.getElementById('cartTotal').textContent = '$' + total.toFixed(2);
        document.getElementById('checkOut').value = total.toFixed(2);
    }

    window.changeQty = (id, qty)=>{
        if(qty < 1){
            Cart.remove(id);
        } 
        else{
            Cart.updateQty(id, qty);
        } 
        renderCart();
    };

    window.removeItem = (id)=>{
        Cart.remove(id);
        renderCart();
    };

    renderCart();
});