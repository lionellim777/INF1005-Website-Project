// 1. IMPORTS MUST BE AT THE ABSOLUTE TOP
import * as THREE from 'three';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';

let scene, camera, renderer, controls, model;

// 2. The DOM Content Loaded block moved below imports
document.addEventListener("DOMContentLoaded", function() {
    let currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    navLinks.forEach(link => {
        link.classList.remove('active');
        link.removeAttribute('aria-current');
        let linkPath = link.getAttribute('href');
        if(currentPath === linkPath){
            link.classList.add('active');
            link.setAttribute('aria-current', 'page');
        }
    });
});

function initThree(containerId, modelPath) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Clear container completely
    container.innerHTML = "";

    // If no model path is provided, show a placeholder and exit
    if (!modelPath || modelPath.trim() === "") {
        container.innerHTML = `<div class="d-flex h-100 align-items-center justify-content-center text-white-50 flex-column">
            <i class="bi bi-box fs-1 mb-2"></i>
            <p>No 3D Model Available</p>
        </div>`;
        return;
    }

    scene = new THREE.Scene();
    scene.background = new THREE.Color(0x285f6b);

    const width = container.clientWidth;
    const height = container.clientHeight;

    camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 1000);
    camera.position.set(0, 1, 5);

    renderer = new THREE.WebGLRenderer({ antialias: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(window.devicePixelRatio);
    container.appendChild(renderer.domElement);

    const light = new THREE.HemisphereLight(0xffffff, 0x444444, 2);
    scene.add(light);

    const dirLight = new THREE.DirectionalLight(0xffffff, 1);
    dirLight.position.set(5, 5, 5);
    scene.add(dirLight);

    controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = true;

    new GLTFLoader().load(modelPath, (gltf) => {
        const model  = gltf.scene;
        const box = new THREE.Box3().setFromObject(model);
        const scale = 3.5 / Math.max(...box.getSize(new THREE.Vector3()).toArray());
        const center = box.getCenter(new THREE.Vector3());
 
        model.scale.setScalar(scale);
        model.position.set(-center.x * scale, -center.y * scale, -center.z * scale);
        scene.add(model);
    });

    function animate(){
        // Only keep animating if renderer still exists (stops memory leaks when modal closes)
        if (!renderer) return; 
        requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }
    animate();
}

const productModal = document.getElementById("productModal");
if (productModal) {
    productModal.addEventListener("shown.bs.modal", function(event){
        const button = event.relatedTarget;

        document.getElementById("modalName").textContent = button.dataset.name;
        document.getElementById("modalCategory").textContent = button.dataset.category;
        document.getElementById("modalPrice").textContent = "$" + button.dataset.price;
        document.getElementById("modalDesc").textContent = button.dataset.desc;

        // Initialize 3D Engine
        initThree("modelShowcase", button.dataset.model);
        renderReviews(button.dataset.id);

        const addBtn = productModal.querySelector('.modal-btn');
        // Check if purchase is allowed before assigning click event
        if (button.dataset.canPurchase === '1') {
            addBtn.disabled = false;
            addBtn.innerHTML = '<i class="bi bi-bag me-1"></i> Add to Cart';
            addBtn.classList.replace('btn-secondary', 'btn-dark');
            addBtn.onclick = () => {
                const qty = parseInt(document.getElementById('qtyValue').textContent);
                Cart.add({
                    id: button.dataset.id,
                    name: button.dataset.name,
                    price: parseFloat(button.dataset.price),
                    image: button.dataset.image,
                    qty: qty
                });
                addBtn.textContent = '✓ Added!';
            };
        } else {
            addBtn.disabled = true;
            addBtn.innerHTML = '<i class="bi bi-ban me-1"></i> Cannot Purchase';
            addBtn.classList.replace('btn-dark', 'btn-secondary');
            addBtn.onclick = null;
        }

        document.getElementById('qtyValue').textContent = 1;
        document.getElementById('qtyMinus').onclick = () =>{
            const el = document.getElementById('qtyValue');
            if(parseInt(el.textContent) > 1) el.textContent = parseInt(el.textContent) - 1;
        };
        document.getElementById('qtyPlus').onclick = () =>{
            const el = document.getElementById('qtyValue');
            el.textContent = parseInt(el.textContent) + 1;
        };
    });

    // 3. CLEANUP: Destroy the 3D scene when modal closes
    productModal.addEventListener("hidden.bs.modal", function(){
        if(renderer){
            renderer.dispose();
            renderer = null; 
            scene.clear();
        }
        document.getElementById("modelShowcase").innerHTML = "";
    });
}

// ... The rest of your filtering and review logic stays exactly the same below here
const grid = document.getElementById('productGrid');
const countEl = document.getElementById('productCount');
const sortSelect = document.getElementById('sortSelect');
const filterBtns = document.querySelectorAll('[data-filter]');
let activeFilter = 'all';
const urlParams = new URLSearchParams(window.location.search);
const categoryFromUrl = urlParams.get('category');

if(categoryFromUrl) {
    activeFilter = categoryFromUrl;
    filterBtns.forEach(btn =>{
        if(btn.dataset.filter === activeFilter){
            btn.classList.add('active');
        }else{
            btn.classList.remove('active');
        }
    });
}

if (grid) {
    filterSort();
}
 
function filterSort() {
    if (!grid) return;
    const cards = [...grid.querySelectorAll('.col[data-category]')];
    // Filter
    cards.forEach(card =>{
        card.classList.remove("animated");
        const isVisible = (activeFilter === 'all' || card.dataset.category === activeFilter);
        
        if(isVisible){
            card.style.display = '';
            void card.offsetWidth; 
            card.classList.add('animated');
        }else{
            card.style.display = 'none';
        }
    });
 
    // Sort
    const visible = cards.filter(c => c.style.display !== 'none');
    if(sortSelect.value !== 'default'){
        visible.sort((a, b) => {
            const diff =parseFloat(a.dataset.price)-parseFloat(b.dataset.price);
            return sortSelect.value === 'ascending' ? diff : -diff;
        });
        visible.forEach(c => grid.appendChild(c));
    }
 
    // Update count label
    if (countEl) {
        const count = visible.length;
        countEl.innerHTML = `Showing <strong>${count}</strong> product${count !== 1 ? 's' : ''}` +
            (activeFilter !== 'all' ? ` in <strong>${activeFilter}</strong>` : '');
    }
}

filterBtns.forEach(btn =>btn.addEventListener('click', () =>{
    filterBtns.forEach(b =>b.classList.remove('active'));
    btn.classList.add('active');
    activeFilter = btn.dataset.filter;
    filterSort();
}));
 
if (sortSelect) {
    sortSelect.addEventListener('change', filterSort);
}

function renderReviews(productId){
    const reviewList = document.getElementById("reviewList");
    const reviewSummary = document.getElementById("reviewSummary");
    if (!reviewList || !reviewSummary || typeof ALL_REVIEWS === 'undefined') return;

    const reviews = ALL_REVIEWS.filter(r => r.product_id == productId);
    

    if(reviews.length==0){
        reviewSummary.innerHTML = `<small class="text-muted">No reviews yet</small>`;
        reviewList.innerHTML = "";
        return;
    }else{
        const avg = (reviews.reduce((sum, r) => sum + +r.stars, 0) / reviews.length).toFixed(1);
        reviewSummary.innerHTML = `
            <div class="d-flex align-items-center gap-3 mb-2">
                <span class="fs-2 fw-bold">${avg}</span>
                <small class="text-muted">${reviews.length} reviews</small>
            </div>
        `;
        reviewList.innerHTML = reviews.map(r => `
            <div class="border-bottom pb-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="rounded-circle bg-secondary-subtle d-flex align-items-center justify-content-center fw-semibold"
                        style="width:32px;height:32px;font-size:11px;">
                        ${r.name[0]}
                    </div>
                    <strong class="small">${r.name}</strong>
                    <small class="text-muted ms-auto">${new Date(r.date).toLocaleDateString('en-US', {month:'short', year:'numeric'})}</small>
                </div>
                <div class="text-warning mb-1" style="font-size:11px;">
                    ${'★'.repeat(r.stars)}
                </div>
                <p class="small text-muted mb-0">${r.body}</p>
            </div>
        `).join("");
    }
}